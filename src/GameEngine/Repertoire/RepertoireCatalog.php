<?php

namespace App\GameEngine\Repertoire;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Les reglages du Repertoire, tels qu'ils sont declares (REP-01).
 *
 * `config/game/repertoire.yaml` — la meme discipline que `factions.yaml`,
 * `combat_branches.yaml` et `found_trees.yaml` : *un reglage de jeu est un bloc
 * de configuration, jamais une constante de classe*.
 *
 * Le fichier ne porte aujourd'hui que le **plafond anti-forcage**. Le bassin
 * des gestes retrouves (REP-02) s'y ajoutera, et c'est deliberement le meme
 * fichier : le savoir du serveur et ce qu'il en tire sont un seul sujet.
 */
class RepertoireCatalog
{
    private ?int $dailyReadings = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/repertoire.yaml';
    }

    /**
     * Le nombre de lectures qu'un joueur verse au Repertoire en un jour.
     *
     * Au-dela, il peut continuer de lire — la lecture reste un geste de son
     * personnage, elle rapporte sa reputation et son Codex. Ce qui s'arrete est
     * la **contribution au souvenir du serveur** : *un geste repete nourrit, un
     * geste ferme ne nourrit plus*.
     */
    public function dailyReadingsPerPlayer(): int
    {
        if ($this->dailyReadings === null) {
            $this->dailyReadings = $this->load($this->defaultFile());
        }

        return $this->dailyReadings;
    }

    /**
     * @throws RepertoireDefinitionException
     */
    public function load(string $path): int
    {
        if (!is_file($path)) {
            throw new RepertoireDefinitionException(sprintf('Repertoire settings not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new RepertoireDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        return $this->normalize(\is_array($raw) ? $raw : [], $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     */
    public function normalize(array $raw, string $source = '<array>'): int
    {
        $cap = $raw['daily_readings_per_player'] ?? null;

        // Refuse plutot que de defaut : un plafond absent ne doit pas se lire
        // comme « pas de plafond », et un plafond a zero fermerait le
        // Repertoire a tout le monde en silence. Les deux erreurs sont muettes
        // en jeu — on ne s'apercoit d'un souvenir qui ne se remplit pas qu'au
        // moment ou un seuil aurait du tomber, c'est-a-dire des mois plus tard.
        if (!\is_int($cap) || $cap <= 0) {
            throw new RepertoireDefinitionException(sprintf('"%s" must declare a positive "daily_readings_per_player".', $source));
        }

        return $cap;
    }
}
