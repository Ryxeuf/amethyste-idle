<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\GameEngine\Fight\QuiverResolver;
use PHPUnit\Framework\TestCase;

/**
 * Le carquois, ressource du registre distance (ARC-04b).
 *
 * GAME_ARCHETYPES § 9 septies : **aucun archetype ne porte un cout recurrent en
 * gils que les autres n'ont pas**. Le carquois est donc une piece durable qui
 * *se vide dans la rencontre et se ramasse apres* — la ressource est
 * intra-rencontre, comme les PM.
 *
 * Ce que ce fichier verrouille, c'est la promesse qui compte pour le joueur :
 * **un carquois vide n'est jamais un mur**.
 */
class QuiverResolverTest extends TestCase
{
    private function spell(int $ammoCost, CombatRegister $register = CombatRegister::Ranged): Spell
    {
        $spell = new Spell();
        $spell->setRegister($register);
        $spell->setAmmoCost($ammoCost);

        return $spell;
    }

    private function player(?int $capacity): Player
    {
        $player = new Player();
        // La reserve est indexee par joueur dans les metadonnees du combat : un
        // personnage en combat est toujours persiste, donc toujours identifie.
        // `Player` n'ayant pas de mutateur d'identifiant, on le pose par
        // reflexion — le meme geste que les autres tests unitaires du depot.
        $id = new \ReflectionProperty(Player::class, 'id');
        $id->setValue($player, 7);

        if (null === $capacity) {
            return $player;
        }

        $quiver = new Item();
        $quiver->setSlug('leather-quiver');
        $quiver->setAmmoCapacity($capacity);

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($quiver);
        $playerItem->setGear(PlayerItem::GEAR_AMMO);

        $inventory = new Inventory();
        $inventory->addItem($playerItem);
        $player->addInventory($inventory);

        return $player;
    }

    /**
     * Le carquois porte ce que la piece declare.
     */
    public function testTheQuiverCarriesWhatThePieceDeclares(): void
    {
        $resolver = new QuiverResolver();

        self::assertSame(20, $resolver->capacityOf($this->player(20)));
        self::assertNull($resolver->capacityOf($this->player(null)));
    }

    /**
     * La reserve se vide dans la rencontre, et pas ailleurs.
     *
     * C'est la decision de modele du jalon : la consommation vit dans le
     * combat, jamais sur l'objet. Un carquois n'a pas d'etat entre deux
     * rencontres — il ne peut donc pas laisser un joueur durablement desarme
     * faute d'avoir fait des courses.
     */
    public function testTheReserveEmptiesWithinTheEncounter(): void
    {
        $resolver = new QuiverResolver();
        $player = $this->player(20);
        $fight = new Fight();

        self::assertSame(20, $resolver->remaining($fight, $player));

        $resolver->consume($fight, $player, $this->spell(3));
        self::assertSame(17, $resolver->remaining($fight, $player));

        $resolver->consume($fight, $player, $this->spell(5));
        self::assertSame(12, $resolver->remaining($fight, $player));

        // La rencontre suivante repart pleine : la reserve vit dans le combat.
        self::assertSame(20, $resolver->remaining(new Fight(), $player));
    }

    /**
     * Un carquois vide refuse le geste de tir, et rien d'autre.
     *
     * *Jamais un mur* : le joueur garde son attaque d'arme (toujours gratuite,
     * regle 10) et tout geste qui ne consomme pas de munition. C'est
     * exactement l'invariant que le plan demande.
     */
    public function testAnEmptyQuiverIsNeverAWall(): void
    {
        $resolver = new QuiverResolver();
        $player = $this->player(4);
        $fight = new Fight();

        $shot = $this->spell(4);
        self::assertTrue($resolver->canAfford($fight, $player, $shot));
        $resolver->consume($fight, $player, $shot);

        // La reserve est vide : le tir est refuse...
        self::assertSame(0, $resolver->remaining($fight, $player));
        self::assertFalse($resolver->canAfford($fight, $player, $shot));

        // ... mais tout geste sans munition passe encore.
        self::assertTrue($resolver->canAfford($fight, $player, $this->spell(0, CombatRegister::Melee)));
        self::assertTrue($resolver->canAfford($fight, $player, $this->spell(0)));
    }

    /**
     * Sans carquois, seuls les gestes de tir sont refuses.
     *
     * Un joueur qui n'en porte pas n'est pas bloque : il ne peut simplement pas
     * jouer les gestes qui en demandent, ce qui est la meme regle qu'une
     * materia non sertie.
     */
    public function testWithoutAQuiverOnlyRangedGesturesAreRefused(): void
    {
        $resolver = new QuiverResolver();
        $player = $this->player(null);
        $fight = new Fight();

        self::assertSame(0, $resolver->remaining($fight, $player));
        self::assertFalse($resolver->canAfford($fight, $player, $this->spell(1)));
        self::assertTrue($resolver->canAfford($fight, $player, $this->spell(0, CombatRegister::Spell)));
    }

    /**
     * Consommer un geste sans munition ne touche pas la reserve.
     *
     * Sinon un sort viderait le carquois d'un archer, et la ressource cesserait
     * d'appartenir a son registre.
     */
    public function testAGestureWithoutAmmunitionLeavesTheReserveAlone(): void
    {
        $resolver = new QuiverResolver();
        $player = $this->player(10);
        $fight = new Fight();

        self::assertSame(0, $resolver->consume($fight, $player, $this->spell(0, CombatRegister::Spell)));
        self::assertSame(10, $resolver->remaining($fight, $player));
    }

    /**
     * Le carquois est une piece durable, jamais un consommable.
     *
     * La capacite ne passe pas par `nbUsages`, qui detruit l'objet a zero : un
     * carquois vide se ramasse, il ne se casse pas.
     */
    public function testAQuiverIsDurableRatherThanConsumable(): void
    {
        $quiver = new Item();
        $quiver->setAmmoCapacity(20);

        self::assertTrue((new QuiverResolver())->isQuiver($quiver));
        self::assertFalse((new QuiverResolver())->isQuiver(new Item()));
    }
}
