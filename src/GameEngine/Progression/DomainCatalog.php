<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Domain;
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
    }
}
