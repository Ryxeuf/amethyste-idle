<?php

namespace App\GameEngine\Repertoire;

use App\Enum\Element;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Les reglages du Repertoire, tels qu'ils sont declares (REP-01).
 *
 * `config/game/repertoire.yaml` — la meme discipline que `factions.yaml`,
 * `combat_branches.yaml` et `found_trees.yaml` : *un reglage de jeu est un bloc
 * de configuration, jamais une constante de classe*.
 *
 * Le fichier porte deux choses : le **plafond anti-forcage** (REP-01) et le
 * **bassin des gestes retrouves** (REP-02). C'est deliberement le meme fichier :
 * le savoir du serveur et ce qu'il en tire sont un seul sujet.
 *
 * **Ce que le chargeur refuse du bassin, et pourquoi** :
 *
 *  - un geste sans **materia** — il ne produirait rien, et un contenu qui ne
 *    produit rien se decouvre au moment ou un serveur le retrouve, c'est-a-dire
 *    des mois trop tard ;
 *  - un geste sans **element** — l'element est l'axe de premier rang de la
 *    dominante (§ 12.3 b) : un geste sans element ne pourrait etre tire par
 *    aucun serveur, quel qu'ait ete son vecu ;
 *  - une **condition inconnue** — elle rendrait son geste inatteignable **en
 *    silence**, ce qui est le pire etat pour un contenu rare : indiscernable
 *    d'un contenu qu'on n'a pas encore merite ;
 *  - une **cle inconnue**, tout simplement. C'est la que la regle laterale tient
 *    : il n'existe aucun champ ou ecrire une statistique, un sort, un
 *    multiplicateur ou un palier, et une cle en trop est refusee plutot
 *    qu'ignoree — *ce qu'on ne peut pas ecrire ne peut pas deriver*.
 */
class RepertoireCatalog
{
    private ?int $dailyReadings = null;

    /**
     * @var array<string, array{awakens: string, elements: list<string>, provenances: list<string>, places: list<string>, condition: ?string, revelation: string, revelation_en: string}>|null
     */
    private ?array $gestures = null;

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
     * Le bassin des gestes retrouves (REP-02).
     *
     * @return array<string, array{awakens: string, elements: list<string>, provenances: list<string>, places: list<string>, condition: ?string, revelation: string, revelation_en: string}>
     */
    public function foundGestures(): array
    {
        if ($this->gestures === null) {
            $this->gestures = $this->normalizeGestures($this->raw($this->defaultFile()), $this->defaultFile());
        }

        return $this->gestures;
    }

    /**
     * Les conditions rares admises, et elles seules (REP-02).
     *
     * Fermee pour la meme raison que `CombatLever` ou `FactionRewardForm` : une
     * condition mal orthographiee ne doit pas produire un geste que personne ne
     * retrouvera jamais sans que personne ne s'en apercoive.
     *
     * REP-03 les **evalue** ; ce catalogue ne fait que les admettre.
     *
     * @var list<string>
     */
    public const RARE_CONDITIONS = ['metropolis_exists', 'readers_doctrine', 'every_element_read'];

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{awakens: string, elements: list<string>, provenances: list<string>, places: list<string>, condition: ?string, revelation: string, revelation_en: string}>
     */
    public function normalizeGestures(array $raw, string $source = '<array>'): array
    {
        $gestures = $raw['found_gestures'] ?? null;
        if (!\is_array($gestures) || $gestures === []) {
            throw new RepertoireDefinitionException(sprintf('"%s" must declare at least one found gesture.', $source));
        }

        $allowed = ['awakens', 'elements', 'provenances', 'places', 'condition', 'revelation', 'revelation_en'];

        $normalized = [];
        foreach ($gestures as $key => $gesture) {
            if (!\is_string($key) || trim($key) === '') {
                throw new RepertoireDefinitionException(sprintf('Found-gesture keys must be slugs in "%s".', $source));
            }

            if (!\is_array($gesture)) {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" must be a mapping in "%s".', $key, $source));
            }

            $unknown = array_diff(array_keys($gesture), $allowed);
            if ($unknown !== []) {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" declares unknown keys (%s) in "%s": a found gesture gives an option, never a value.', $key, implode(', ', $unknown), $source));
            }

            $awakens = $gesture['awakens'] ?? null;
            if (!\is_string($awakens) || trim($awakens) === '') {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" must name the materia it awakens in "%s".', $key, $source));
            }

            $elements = $this->slugList($gesture['elements'] ?? null, $key, 'elements', $source);
            if ($elements === []) {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" declares no element in "%s": no server could ever draw it.', $key, $source));
            }
            foreach ($elements as $element) {
                if (Element::tryFrom($element) === null) {
                    throw new RepertoireDefinitionException(sprintf('Found gesture "%s" names an unknown element "%s" in "%s".', $key, $element, $source));
                }
            }

            $condition = $gesture['condition'] ?? null;
            if ($condition !== null && !\in_array($condition, self::RARE_CONDITIONS, true)) {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" declares an unknown rare condition "%s" in "%s": admitted conditions are %s.', $key, \is_scalar($condition) ? (string) $condition : \gettype($condition), $source, implode(', ', self::RARE_CONDITIONS)));
            }

            foreach (['revelation', 'revelation_en'] as $field) {
                if (!\is_string($gesture[$field] ?? null) || trim((string) $gesture[$field]) === '') {
                    throw new RepertoireDefinitionException(sprintf('Found gesture "%s" needs a %s in "%s": the world journal announces it, or no one knows it happened.', $key, $field, $source));
                }
            }

            $normalized[$key] = [
                'awakens' => $awakens,
                'elements' => $elements,
                'provenances' => $this->slugList($gesture['provenances'] ?? null, $key, 'provenances', $source),
                'places' => $this->slugList($gesture['places'] ?? null, $key, 'places', $source),
                'condition' => \is_string($condition) ? $condition : null,
                'revelation' => $gesture['revelation'],
                'revelation_en' => $gesture['revelation_en'],
            ];
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function slugList(mixed $value, string $key, string $field, string $source): array
    {
        if ($value === null) {
            return [];
        }

        if (!\is_array($value)) {
            throw new RepertoireDefinitionException(sprintf('Found gesture "%s" must declare "%s" as a list in "%s".', $key, $field, $source));
        }

        $slugs = [];
        foreach ($value as $slug) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new RepertoireDefinitionException(sprintf('Found gesture "%s" has an empty entry in "%s" in "%s".', $key, $field, $source));
            }
            $slugs[] = $slug;
        }

        return $slugs;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function raw(string $path): array
    {
        if (!is_file($path)) {
            throw new RepertoireDefinitionException(sprintf('Repertoire settings not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new RepertoireDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        return \is_array($raw) ? $raw : [];
    }

    /**
     * @throws RepertoireDefinitionException
     */
    public function load(string $path): int
    {
        return $this->normalize($this->raw($path), $path);
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
