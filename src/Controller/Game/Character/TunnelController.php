<?php

namespace App\Controller\Game\Character;

use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\User;
use App\Enum\CreationStep;
use App\Form\CharacterCreateType;
use App\GameEngine\Onboarding\CharacterDraft;
use App\GameEngine\Race\RaceCapability;
use App\Helper\PlayerHelper;
use App\Service\Avatar\AvatarCatalogProvider;
use App\Service\ForbiddenNameChecker;
use App\Service\PlayerFactory;
use App\Service\PlayerNameNormalizer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le tunnel d'entree en quatre pas (ONB-05).
 *
 * GAME_ONBOARDING § 3 : **compte → nom → peuple → visage**, un ecran par pas,
 * une decision par ecran, une phrase de fiction par ecran.
 *
 * Ce qu'il remplace : deux formulaires administratifs d'affilee. Le second
 * demandait le nom, le peuple et l'apparence **sur le meme ecran** — trois
 * decisions de nature differente presentees comme une seule corvee, ou la seule
 * qui porte quelque chose (le peuple) se noyait entre un champ texte et une
 * palette de couleurs de cheveux.
 *
 * **Le tunnel sert le premier personnage ; l'ecran unique reste la voie du
 * second.** Quelqu'un qui en cree un deuxieme sait deja ce qu'est un peuple :
 * lui refaire la visite guidee serait le ralentir pour rien, et le canon le dit
 * explicitement.
 */
#[Route('/game/character')]
#[IsGranted('ROLE_USER')]
class TunnelController extends AbstractController
{
    public function __construct(
        private readonly PlayerFactory $playerFactory,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly ForbiddenNameChecker $forbiddenNameChecker,
        private readonly PlayerNameNormalizer $playerNameNormalizer,
        private readonly AvatarCatalogProvider $avatarCatalogProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Entree du tunnel : reprend la ou le joueur s'etait arrete.
     *
     * Un tunnel qui recommencerait a zero apres un onglet ferme perdrait
     * exactement ce qu'il pretend proteger. Le brouillon vit en session, donc
     * la reprise ne coute rien de plus qu'une redirection.
     */
    #[Route('/tunnel', name: 'app_character_tunnel', methods: ['GET'])]
    public function enter(Request $request): Response
    {
        $draft = $this->draft($request);
        $step = $draft->firstIncompleteStep() ?? CreationStep::Face;

        return $this->redirectToRoute('app_character_tunnel_step', ['step' => $step->value]);
    }

    #[Route('/tunnel/{step}', name: 'app_character_tunnel_step', requirements: ['step' => 'name|people|face'], methods: ['GET', 'POST'])]
    public function step(string $step, Request $request): Response
    {
        $current = CreationStep::from($step);
        $draft = $this->draft($request);

        // On ne saute pas un pas : arriver au visage sans nom afficherait un
        // ecran qui ne mene nulle part, et le joueur ne saurait pas pourquoi.
        $missing = $draft->firstIncompleteStep();
        if (null !== $missing && $missing->position() < $current->position()) {
            return $this->redirectToRoute('app_character_tunnel_step', ['step' => $missing->value]);
        }

        if ($request->isMethod('POST')) {
            $error = $this->apply($current, $draft, $request);
            $this->store($request, $draft);

            if (null !== $error) {
                $this->addFlash('error', $error);

                return $this->redirectToRoute('app_character_tunnel_step', ['step' => $current->value]);
            }

            if (CreationStep::Face === $current) {
                return $this->finish($request, $draft);
            }

            return $this->redirectToRoute('app_character_tunnel_step', ['step' => $current->next()?->value]);
        }

        return $this->render('game/character/tunnel/' . $current->value . '.html.twig', [
            'step' => $current,
            'total' => CreationStep::total(),
            'draft' => $draft,
            'races' => CreationStep::People === $current ? $this->races() : [],
            'capabilities' => CreationStep::People === $current ? $this->raceCapabilities() : [],
            'choices' => CreationStep::Face === $current ? $this->avatarCatalogProvider->getCreationChoices() : [],
            'hairColors' => CharacterCreateType::HAIR_COLORS,
        ]);
    }

    /**
     * L'eveil : un paragraphe, **un bouton**, vers l'ecran de zone.
     *
     * Jamais le hub. Le premier ecran d'un joueur doit etre un lieu ou il peut
     * agir, pas un tableau de bord qui n'a encore rien a lui raconter (A4).
     */
    #[Route('/awakening', name: 'app_character_awakening', methods: ['GET'])]
    public function awakening(): Response
    {
        if (null === $this->playerHelper->getPlayer()) {
            return $this->redirectToRoute('app_character_tunnel');
        }

        return $this->render('game/character/tunnel/awakening.html.twig');
    }

    /**
     * Applique un pas au brouillon, ou rend le motif du refus.
     */
    private function apply(CreationStep $step, CharacterDraft $draft, Request $request): ?string
    {
        if (CreationStep::Name === $step) {
            $name = trim((string) $request->request->get('name', ''));

            if (mb_strlen($name) < 3 || mb_strlen($name) > 16 || !preg_match('/^[\p{L}\s\-]+$/u', $name)) {
                return $this->translator->trans('game.character.tunnel.name.invalid');
            }
            if ($this->forbiddenNameChecker->isForbidden($name)) {
                return $this->translator->trans('game.character.tunnel.name.forbidden');
            }
            if ($this->isNameTaken($name)) {
                return $this->translator->trans('game.character.tunnel.name.taken');
            }

            $draft->name = $name;

            return null;
        }

        if (CreationStep::People === $step) {
            $slug = trim((string) $request->request->get('race', ''));
            if (null === $this->race($slug)) {
                return $this->translator->trans('game.character.tunnel.people.unknown');
            }

            $draft->raceSlug = $slug;

            return null;
        }

        // Le visage n'a pas de refus : toute absence est un defaut acceptable,
        // et bloquer la creation sur une couleur de cheveux serait absurde.
        $draft->body = $this->stringOrNull($request->request->get('body'));
        $draft->hair = $this->stringOrNull($request->request->get('hair'));
        $draft->hairColor = $this->stringOrNull($request->request->get('hairColor'));

        return null;
    }

    private function finish(Request $request, CharacterDraft $draft): Response
    {
        if (!$draft->isReady()) {
            return $this->redirectToRoute('app_character_tunnel');
        }

        /** @var User $user */
        $user = $this->getUser();
        $race = $this->race((string) $draft->raceSlug);

        if (null === $race) {
            return $this->redirectToRoute('app_character_tunnel_step', ['step' => CreationStep::People->value]);
        }

        try {
            $player = $this->playerFactory->createPlayer($user, (string) $draft->name, $race, [
                'body' => $draft->body,
                'hair' => $draft->hair,
                'hairColor' => $draft->hairColor,
            ]);
        } catch (UniqueConstraintViolationException) {
            // ONB-06 : seul l'index tranche. Le joueur repart au pas du nom
            // avec son peuple et son visage intacts — c'est precisement ce que
            // le brouillon existe pour garantir.
            $draft->name = null;
            $this->store($request, $draft);
            $this->addFlash('error', $this->translator->trans('game.character.tunnel.name.just_taken'));

            return $this->redirectToRoute('app_character_tunnel_step', ['step' => CreationStep::Name->value]);
        }

        $this->playerHelper->setActivePlayer($player);
        $request->getSession()->remove(CharacterDraft::SESSION_KEY);

        return $this->redirectToRoute('app_character_awakening');
    }

    private function draft(Request $request): CharacterDraft
    {
        $stored = $request->getSession()->get(CharacterDraft::SESSION_KEY, []);

        return CharacterDraft::fromArray(\is_array($stored) ? $stored : []);
    }

    private function store(Request $request, CharacterDraft $draft): void
    {
        $request->getSession()->set(CharacterDraft::SESSION_KEY, $draft->toArray());
    }

    /**
     * @return list<Race>
     */
    private function races(): array
    {
        return $this->entityManager->getRepository(Race::class)
            ->findBy(['availableAtCreation' => true], ['name' => 'ASC']);
    }

    private function race(string $slug): ?Race
    {
        if ('' === $slug) {
            return null;
        }

        $race = $this->entityManager->getRepository(Race::class)->findOneBy(['slug' => $slug]);

        return $race instanceof Race && $race->isAvailableAtCreation() ? $race : null;
    }

    /**
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

    private function isNameTaken(string $name): bool
    {
        return $this->entityManager->getRepository(Player::class)
            ->findOneBy(['normalizedName' => $this->playerNameNormalizer->normalize($name)]) !== null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) && '' !== trim($value) ? trim($value) : null;
    }
}
