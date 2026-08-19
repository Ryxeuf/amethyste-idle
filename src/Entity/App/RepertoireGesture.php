<?php

namespace App\Entity\App;

use App\Repository\RepertoireGestureRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un geste que ce monde a retrouve (REP-03).
 *
 * GAME_WORLD § 12.3 b : *« a des seuils donnes, le serveur **retrouve un geste
 * perdu** »*. Une ligne par geste, et **rien d'autre que le fait qu'il soit
 * retrouve** : ni compteur, ni date d'expiration, ni etat.
 *
 * **Cumulatif et sans retrait.** Le savoir n'est jamais borne (GAME_DOMAINS §1)
 * : un geste retrouve ne se re-perd pas, et il n'existe aucun champ pour le
 * dire. Le seul moyen d'en retirer un serait de supprimer sa ligne, ce
 * qu'aucun code du jalon ne fait — *ce qu'on ne peut pas ecrire ne peut pas
 * deriver*.
 *
 * **La cle est celle du bassin**, jamais une copie de ce que le bassin dit.
 * Recopier ici la materia, les tags ou le texte de revelation ferait diverger
 * la donnee et sa description a la premiere retouche du fichier : le bassin est
 * la source, cette table ne retient que *lequel*.
 */
#[ORM\Entity(repositoryClass: RepertoireGestureRepository::class)]
#[ORM\Table(name: 'repertoire_gesture')]
#[ORM\UniqueConstraint(name: 'uq_repertoire_gesture_key', columns: ['gesture_key'])]
class RepertoireGesture
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'gesture_key', type: 'string', length: 64)]
    private string $gestureKey;

    /**
     * Le rang de decouverte : 1 pour le premier geste du monde.
     *
     * Il sert au **seuil suivant** — le n-ieme geste coute n crans — et il dit
     * au joueur ou en est son monde. Le deriver d'un `count()` marcherait
     * aujourd'hui et cesserait de marcher le jour ou l'on voudrait relire
     * l'histoire dans l'ordre.
     */
    #[ORM\Column(name: 'discovery_rank', type: 'integer')]
    private int $discoveryRank;

    public function __construct(string $gestureKey, int $discoveryRank)
    {
        $this->gestureKey = $gestureKey;
        $this->discoveryRank = $discoveryRank;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGestureKey(): string
    {
        return $this->gestureKey;
    }

    public function getDiscoveryRank(): int
    {
        return $this->discoveryRank;
    }
}
