<?php

namespace App\GameEngine\Progression;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Les arbres retrouves, tels qu'ils sont declares (DOM-10).
 *
 * Ajouter un arbre retrouve, c'est ajouter un bloc dans
 * `config/game/found_trees.yaml` — jamais toucher une classe. La meme
 * discipline que `combat_branches.yaml` (ARC-14a) et `equipment_ports.yaml`
 * (ONB-20b), et pour la meme raison : *une couche de contenu est un bloc de
 * configuration, jamais une ligne de code*.
 *
 * **Ce que le chargeur refuse, et pourquoi** :
 *
 *  - un arbre sans **accomplissement** — la condition serait alors un tirage,
 *    ce que la loi 4 interdit : on ne trouve pas un arbre par chance ;
 *  - un arbre sans **parchemin** — il n'aurait aucun moyen de s'ouvrir, et un
 *    arbre inatteignable est pire qu'un arbre absent ;
 *  - un parchemin **sans nom ni description**, parce qu'un objet qu'aucun ecran
 *    ne sait presenter est un objet qu'on ne peut pas donner.
 *
 * Il n'y a **aucun champ** pour une date, une fenetre, un quota ou une chance :
 * la loi « cumulatif, jamais manque » est tenue par la forme du fichier, et pas
 * seulement par un test. *Ce qu'on ne peut pas ecrire ne peut pas deriver.*
 */
class FoundTreeCatalog
{
    /**
     * @var array<string, array{label: string, earned_by: string, parchment: array{slug: string, name: string, name_en: string, description: string, description_en: string}}>|null
     */
    private ?array $trees = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/found_trees.yaml';
    }

    /**
     * @return array<string, array{label: string, earned_by: string, parchment: array{slug: string, name: string, name_en: string, description: string, description_en: string}}>
     */
    public function trees(): array
    {
        if ($this->trees === null) {
            $this->trees = $this->load($this->defaultFile());
        }

        return $this->trees;
    }

    /**
     * Les cles des arbres retrouves.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->trees());
    }

    /**
     * L'arbre que cet accomplissement fait rencontrer, s'il en fait rencontrer un.
     *
     * C'est le point d'entree du granter : *un nœud appris, une question, une
     * reponse*. Rendre la cle plutot que l'arbre garde le catalogue ignorant de
     * la base — il decrit une couche de contenu, il ne la charge pas.
     */
    public function treeEarnedBy(string $skillSlug): ?string
    {
        foreach ($this->trees() as $key => $tree) {
            if ($tree['earned_by'] === $skillSlug) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, earned_by: string, parchment: array{slug: string, name: string, name_en: string, description: string, description_en: string}}>
     *
     * @throws FoundTreeDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FoundTreeDefinitionException(sprintf('Found-tree catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FoundTreeDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FoundTreeDefinitionException(sprintf('Found-tree catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{label: string, earned_by: string, parchment: array{slug: string, name: string, name_en: string, description: string, description_en: string}}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $trees = $raw['trees'] ?? null;
        if (!\is_array($trees) || $trees === []) {
            throw new FoundTreeDefinitionException(sprintf('"%s" must declare at least one found tree.', $source));
        }

        $normalized = [];
        foreach ($trees as $key => $tree) {
            if (!\is_string($key) || trim($key) === '') {
                throw new FoundTreeDefinitionException(sprintf('Found-tree keys must be slugs in "%s".', $source));
            }

            if (!\is_array($tree) || !\is_string($tree['label'] ?? null)) {
                throw new FoundTreeDefinitionException(sprintf('Found tree "%s" needs a label in "%s".', $key, $source));
            }

            $earnedBy = $tree['earned_by'] ?? null;
            if (!\is_string($earnedBy) || trim($earnedBy) === '') {
                throw new FoundTreeDefinitionException(sprintf('Found tree "%s" must name the accomplishment that earns it in "%s": a found tree is never a draw.', $key, $source));
            }

            $parchment = $tree['parchment'] ?? null;
            if (!\is_array($parchment)) {
                throw new FoundTreeDefinitionException(sprintf('Found tree "%s" needs a parchment in "%s": without one it could never be opened.', $key, $source));
            }

            foreach (['slug', 'name', 'name_en', 'description', 'description_en'] as $field) {
                if (!\is_string($parchment[$field] ?? null) || trim((string) $parchment[$field]) === '') {
                    throw new FoundTreeDefinitionException(sprintf('The parchment of "%s" needs a %s in "%s".', $key, $field, $source));
                }
            }

            $normalized[$key] = [
                'label' => $tree['label'],
                'earned_by' => $earnedBy,
                'parchment' => [
                    'slug' => $parchment['slug'],
                    'name' => $parchment['name'],
                    'name_en' => $parchment['name_en'],
                    'description' => $parchment['description'],
                    'description_en' => $parchment['description_en'],
                ],
            ];
        }

        return $normalized;
    }
}
