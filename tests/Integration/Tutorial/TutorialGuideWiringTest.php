<?php

namespace App\Tests\Integration\Tutorial;

use App\GameEngine\Tutorial\TutorialGuide;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le guide ne peut pas emettre un lien mort.
 *
 * **Pourquoi ce test existe.** Le defaut qu'on repare etait exactement de cette
 * nature : un lien qui ne menait nulle part, et que rien ne signalait. Le
 * remede a lui-meme faille du meme cote pendant son ecriture — le premier jet
 * visait `app_game_inventory`, qui n'existe pas (la route s'appelle
 * `app_game_inventory_equipment_list`). Un nom de route est une chaine : le
 * compilateur ne la lit pas, PHPStan non plus, et l'erreur ne se voit qu'au
 * clic d'un joueur.
 *
 * Deux verifications, une par facon d'echouer en silence : **la route existe**
 * (sinon `path()` leve en plein rendu de page), et **le libelle est traduit**
 * (sinon le joueur lit `game.onboarding.hint.action.talk` sous son bandeau).
 */
class TutorialGuideWiringTest extends KernelTestCase
{
    public function testEveryRouteTheGuideCanEmitExists(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');
        $known = $router->getRouteCollection();

        foreach (TutorialGuide::emittableRoutes() as $route) {
            self::assertNotNull(
                $known->get($route),
                sprintf('Le guide du tutoriel peut envoyer vers « %s », qui n\'est pas une route du jeu.', $route),
            );
        }
    }

    public function testEveryLabelTheGuideCanEmitIsTranslated(): void
    {
        self::bootKernel();

        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);

        foreach (['fr', 'en'] as $locale) {
            foreach (TutorialGuide::emittableActionKeys() as $actionKey) {
                $id = 'game.onboarding.hint.action.' . $actionKey;
                self::assertNotSame(
                    $id,
                    $translator->trans($id, [], null, $locale),
                    sprintf('Le libelle « %s » n\'est pas traduit en %s : le joueur lirait sa clef.', $id, $locale),
                );
            }

            foreach (['game.onboarding.hint.where', 'game.onboarding.training.action', 'game.onboarding.training.heading', 'game.onboarding.training.hint'] as $id) {
                self::assertNotSame($id, $translator->trans($id, [], null, $locale), sprintf('« %s » n\'est pas traduit en %s.', $id, $locale));
            }
        }
    }
}
