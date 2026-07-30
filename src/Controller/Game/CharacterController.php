<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\User;
use App\Form\CharacterCreateType;
use App\Form\CharacterCustomizeType;
use App\GameEngine\Race\RaceCapability;
use App\Helper\PlayerHelper;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\ForbiddenNameChecker;
use App\Service\PlayerFactory;
use App\Service\PlayerNameNormalizer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/character')]
#[IsGranted('ROLE_USER')]
class CharacterController extends AbstractController
{
    public function __construct(
        private readonly PlayerFactory $playerFactory,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly ForbiddenNameChecker $forbiddenNameChecker,
        private readonly PlayerNameNormalizer $playerNameNormalizer,
        private readonly AvatarHashRecalculator $avatarHashRecalculator,
        #[Autowire('%app.max_players_per_user%')] private readonly int $maxPlayersPerUser,
    ) {
    }

    #[Route('/create', name: 'app_character_create')]
    public function create(Request $request): Response
    {
        $maxPlayersPerUser = $this->maxPlayersPerUser;
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayers()->count() >= $maxPlayersPerUser) {
            return $this->render('game/character/limit_reached.html.twig', [
                'maxPlayers' => $maxPlayersPerUser,
            ]);
        }

        // ONB-05 : le tunnel en quatre pas sert le **premier** personnage.
        // L'ecran unique reste la voie du second : quelqu'un qui en cree un
        // deuxieme sait deja ce qu'est un peuple, et lui refaire la visite
        // guidee serait le ralentir pour rien.
        if ($user->getPlayers()->isEmpty()) {
            return $this->redirectToRoute('app_character_tunnel');
        }

        $form = $this->createForm(CharacterCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $name */
            $name = trim((string) $form->get('name')->getData());
            /** @var \App\Entity\Game\Race $race */
            $race = $form->get('race')->getData();

            if ($this->forbiddenNameChecker->isForbidden($name)) {
                $this->addFlash('error', 'Ce nom de personnage n\'est pas autorisé.');

                return $this->render('game/character/create.html.twig', [
                    'form' => $form->createView(),
                    'race_capabilities' => $this->raceCapabilities(),
                ]);
            }

            if ($this->isNameTaken($name)) {
                $this->addFlash('error', 'Ce nom de personnage est déjà pris.');

                return $this->render('game/character/create.html.twig', [
                    'form' => $form->createView(),
                    'race_capabilities' => $this->raceCapabilities(),
                ]);
            }

            $appearance = [
                'body' => $this->stringOrNull($form->get('body')->getData()),
                'hair' => $this->stringOrNull($form->get('hair')->getData()),
                'hairColor' => $this->stringOrNull($form->get('hairColor')->getData()),
            ];

            try {
                $player = $this->playerFactory->createPlayer($user, $name, $race, $appearance);
            } catch (UniqueConstraintViolationException) {
                // ONB-06 : deux creations simultanees passent toutes deux la
                // verification avant qu'aucune n'ait ecrit. Seul l'index
                // tranche — la verification ci-dessus est un confort, pas une
                // garantie.
                //
                // On redirige plutot que de re-rendre : Doctrine ferme le
                // gestionnaire d'entites apres une violation de contrainte, et
                // le formulaire a besoin de lire les peuples pour s'afficher.
                $this->addFlash('error', 'Ce nom de personnage vient d\'etre pris.');

                return $this->redirectToRoute('app_character_create');
            }

            $this->playerHelper->setActivePlayer($player);

            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/character/create.html.twig', [
            'form' => $form->createView(),
            'race_capabilities' => $this->raceCapabilities(),
        ]);
    }

    /**
     * ONB-07 — ce que chaque peuple laisse voir, indexe par son slug.
     *
     * Le gabarit ne derive rien lui-meme : c'est ici, et nulle part ailleurs,
     * qu'on decide ce qu'un peuple apporte.
     *
     * @return array<string, array{name: string, description: string}>
     */
    private function raceCapabilities(): array
    {
        $capabilities = [];
        foreach (RaceCapability::cases() as $capability) {
            $capabilities[$capability->raceSlug()] = [
                'name' => $capability->nameKey(),
                'description' => $capability->descriptionKey(),
            ];
        }

        return $capabilities;
    }

    /**
     * ONB-06 — « libre / pris », et rien de plus.
     *
     * Repondre au fil de la frappe evite de perdre un formulaire entier sur un
     * nom deja pris. La reponse ne dit jamais **qui** porte le nom : ce serait
     * un annuaire de personnages ouvert a tous.
     */
    #[Route('/name-available', name: 'app_character_name_available', methods: ['GET'])]
    public function nameAvailable(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));

        if (mb_strlen($name) < 3) {
            return new JsonResponse(['available' => false, 'reason' => 'too_short']);
        }

        if ($this->forbiddenNameChecker->isForbidden($name)) {
            return new JsonResponse(['available' => false, 'reason' => 'forbidden']);
        }

        return new JsonResponse(['available' => !$this->isNameTaken($name)]);
    }

    /**
     * L'unicite se juge sur la forme normalisee : « Claire », « claire » et
     * « Clairе » (avec un « е » cyrillique) sont le meme nom.
     */
    private function isNameTaken(string $name): bool
    {
        $normalized = $this->playerNameNormalizer->normalize($name);

        return $this->entityManager->getRepository(Player::class)
            ->findOneBy(['normalizedName' => $normalized]) !== null;
    }

    #[Route('/select', name: 'app_character_select')]
    public function select(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $players = $user->getPlayers();

        if ($players->count() <= 1) {
            return $this->redirectToRoute('app_game');
        }

        if ($request->isMethod('POST')) {
            $playerId = $request->request->getInt('player_id');
            $player = $this->entityManager->getRepository(Player::class)->find($playerId);

            if ($player instanceof Player && $player->getUser() === $user) {
                $this->playerHelper->setActivePlayer($player);

                return $this->redirectToRoute('app_game');
            }

            $this->addFlash('error', 'Personnage invalide.');
        }

        return $this->render('game/character/select.html.twig', [
            'players' => $players,
        ]);
    }

    #[Route('/customize', name: 'app_character_customize')]
    public function customize(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player instanceof Player) {
            return $this->redirectToRoute('app_game');
        }

        $existing = $player->getAvatarAppearance() ?? [];
        $form = $this->createForm(CharacterCustomizeType::class, [
            'body' => $existing['body'] ?? null,
            'hair' => $existing['hair'] ?? null,
            'hairColor' => $existing['hairColor'] ?? null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appearance = ['body' => (string) $form->get('body')->getData()];

            $hair = $this->stringOrNull($form->get('hair')->getData());
            if ($hair !== null) {
                $appearance['hair'] = $hair;
            }

            $hairColor = $this->stringOrNull($form->get('hairColor')->getData());
            if ($hairColor !== null) {
                $appearance['hairColor'] = $hairColor;
            }

            $player->setAvatarAppearance($appearance);
            $this->entityManager->flush();

            $this->avatarHashRecalculator->recalculate($player);

            $this->addFlash('success', 'Apparence mise a jour.');

            return $this->redirectToRoute('app_character_customize');
        }

        return $this->render('game/character/customize.html.twig', [
            'form' => $form->createView(),
            'player' => $player,
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
