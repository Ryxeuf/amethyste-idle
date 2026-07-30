<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\GameEngine\Item\ItemEffectEncoder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Le point unique d'ou l'on demande « quels arbres existe-t-il, et lequel porte
 * ce slug ? » (ONB-08).
 *
 * `Domain` n'a **pas** de colonne `slug` : `Domain::getSlug()` derive
 * l'identifiant du titre affiche, accents compris (« Pêcheur » donne `pêcheur`),
 * et c'est deja la convention que suivent les commissions hebdomadaires
 * (`WeeklyCommissionDomainTest`). Un `findOneBy(['slug' => ...])` est donc
 * impossible : il faut charger la table et rejouer la derivation.
 *
 * La table compte quelques dizaines de lignes et ne bouge pas en cours de
 * requete — un cache d'instance evite d'en refaire la lecture a chaque
 * parchemin lu, sans jamais devenir une source de verite concurrente.
 */
class DomainCatalog implements ResetInterface
{
    /** @var array<string, Domain>|null */
    private ?array $bySlug = null;

    /** @var array<string, Item>|null Parchemins indexes par slug de domaine ouvert */
    private ?array $parchments = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return Domain[]
     */
    public function all(): array
    {
        return array_values($this->indexed());
    }

    /**
     * Le parchemin qui ouvre cet arbre, ou `null` si aucun ne le fait (ONB-09).
     *
     * Resolu par l'**effet** de l'objet, jamais par une table de correspondance
     * parallele : un parchemin renomme, reprice ou deplace suit tout seul, et
     * le catalogue ne peut donc pas mentir sur son prix. Un arbre sans
     * parchemin est un arbre inatteignable — le `null` est ce que
     * `DomainCatalogContractTest` interdit.
     */
    public function parchmentFor(Domain $domain): ?Item
    {
        return $this->parchments()[self::normalize($domain->getSlug())] ?? null;
    }

    /**
     * @return array<string, Item>
     */
    private function parchments(): array
    {
        if ($this->parchments !== null) {
            return $this->parchments;
        }

        $indexed = [];
        foreach ($this->entityManager->getRepository(Item::class)->findAll() as $item) {
            $effect = $item->getEffect();
            if ($effect === null || !str_contains($effect, ItemEffectEncoder::ACTION_OPEN_DOMAIN)) {
                continue;
            }

            $decoded = json_decode($effect, true);
            if (!\is_array($decoded) || ($decoded['action'] ?? null) !== ItemEffectEncoder::ACTION_OPEN_DOMAIN) {
                continue;
            }

            $slug = $decoded['slug'] ?? null;
            if (\is_string($slug) && $slug !== '') {
                $indexed[self::normalize($slug)] = $item;
            }
        }

        return $this->parchments = $indexed;
    }

    public function findBySlug(string $slug): ?Domain
    {
        return $this->indexed()[self::normalize($slug)] ?? null;
    }

    /**
     * `Domain::getSlug()` passe par `strtolower()`, qui ne touche que l'ASCII.
     * Les deux cotes de la comparaison sont donc ramenes a la meme forme ici,
     * faute de quoi un titre a majuscule accentuee produirait une cle que la
     * recherche ne retrouverait jamais — silencieusement.
     */
    private static function normalize(string $slug): string
    {
        return mb_strtolower(trim($slug));
    }

    /**
     * @return array<string, Domain>
     */
    private function indexed(): array
    {
        if ($this->bySlug !== null) {
            return $this->bySlug;
        }

        $indexed = [];
        foreach ($this->entityManager->getRepository(Domain::class)->findAll() as $domain) {
            $indexed[self::normalize($domain->getSlug())] = $domain;
        }

        return $this->bySlug = $indexed;
    }

    public function reset(): void
    {
        $this->bySlug = null;
        $this->parchments = null;
    }
}
