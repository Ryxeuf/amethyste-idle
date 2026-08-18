<?php

namespace App\DataFixtures\Game;

use App\DataFixtures\DomainFixtures;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\EquipmentPortCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Les cinq statistiques plates qu'un nœud peut porter.
     *
     * Nommees ici parce qu'une loi les vise en bloc : un echelon de port n'en
     * porte aucune (voir `rewireWeaponPortLadders()`).
     */
    private const COMBAT_STATS = ['damage', 'heal', 'hit', 'critical', 'life'];

    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $skillsData = $this->getSkillsData();

        // Premiere passe : creation des competences
        foreach ($skillsData as $reference => $data) {
            $skill = new Skill();

            if (isset($data['slug'])) {
                $skill->setSlug((string) $data['slug']);
            }

            if (isset($data['title'])) {
                $skill->setTitle((string) $data['title']);
            }

            if (isset($data['description'])) {
                $skill->setDescription((string) $data['description']);
            }

            if (isset($data['requiredPoints'])) {
                $skill->setRequiredPoints((int) $data['requiredPoints']);
            }

            if (isset($data['domain'])) {
                $domains = is_array($data['domain']) ? $data['domain'] : [$data['domain']];
                foreach ($domains as $domainRef) {
                    $skill->addDomain($this->getReference($domainRef, Domain::class));
                }
            }

            if (isset($data['actions'])) {
                $skill->setActions($data['actions']);
            }

            if (isset($data['damage'])) {
                $skill->setDamage((int) $data['damage']);
            }
            if (isset($data['heal'])) {
                $skill->setHeal((int) $data['heal']);
            }
            if (isset($data['hit'])) {
                $skill->setHit((int) $data['hit']);
            }
            if (isset($data['critical'])) {
                $skill->setCritical((int) $data['critical']);
            }
            if (isset($data['life'])) {
                $skill->setLife((int) $data['life']);
            }

            // DOM-07 : le nœud pose et pas encore ouvert.
            if (isset($data['dormant'])) {
                $skill->setDormant((bool) $data['dormant']);
            }

            // ARC-07 — les leviers. La colonne existe depuis ARC-03a et
            // naissait vide partout ; c'est ici qu'un arbre ecrit au gabarit
            // cesse de porter des statistiques plates. Le format est celui que
            // `SkillLeverReader` sait refuser — une liste de
            // `{lever, points, condition?}` —, et rien n'est valide ici : le
            // point de passage unique doit rester unique.
            if (isset($data['levers'])) {
                $skill->setLevers($data['levers']);
            }

            $manager->persist($skill);

            $this->addReference($reference, $skill);
        }

        // Deuxieme passe : definition des prerequis
        foreach ($skillsData as $reference => $data) {
            if (!isset($data['requirements'])) {
                continue;
            }

            /** @var Skill $skill */
            $skill = $this->getReference($reference, Skill::class);

            foreach ($data['requirements'] as $requirementRef) {
                $skill->addRequirement($this->getReference($requirementRef, Skill::class));
            }
        }

        $manager->flush();
    }

    private function getSkillsData(): array
    {
        return $this->rewireWeaponPortLadders($this->declaredSkills());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function declaredSkills(): array
    {
        return array_merge(
            $this->getPyromancySkills(),
            $this->getBerserkerSkills(),
            $this->getArtificerSkills(),
            $this->getHydromancerSkills(),
            $this->getHealerSkills(),
            $this->getTidecallerSkills(),
            $this->getSoldierSkills(),
            $this->getKnightSkills(),
            $this->getEngineerSkills(),
            $this->getGeomancerSkills(),
            $this->getDefenderSkills(),
            $this->getGuardianSkills(),
            $this->getNecromancerSkills(),
            $this->getDruidSkills(),
            $this->getHunterSkills(),
            $this->getTamerSkills(),
            $this->getStormcallerSkills(),
            $this->getArcherSkills(),
            $this->getWandererSkills(),
            $this->getPaladinSkills(),
            $this->getPriestSkills(),
            $this->getInquisitorSkills(),
            $this->getAssassinSkills(),
            $this->getWarlockSkills(),
            $this->getMinerSkills(),
            $this->getHerbalistSkills(),
            $this->getFishermanSkills(),
            $this->getSkinnerSkills(),
            $this->getLumberjackSkills(),
            $this->getCookSkills(),
            $this->getCarpenterSkills(),
            $this->getTailorSkills(),
            $this->getBlacksmithSkills(),
            $this->getLeatherworkerSkills(),
            $this->getAlchimistSkills(),
            $this->getJewellerSkills(),
            $this->getSharedSkills(),
            $this->getDormantHybridAccords(),
            $this->getWeaponPortRungs(),
            $this->getArmorPortUpperRungs(),
        );
    }

    /**
     * Les echelons 1 de l'echelle de port, generes depuis leur declaration
     * (ONB-20b).
     *
     * Un nœud par **famille d'arme**, gratuit, rattache a **tous** les arbres
     * qui l'enseignent : `Skill::domains` est un ManyToMany, et « en ouvrir un
     * seul suffit ». Les ecrire arbre par arbre aurait recree exactement le
     * defaut qu'ONB-20b repare — un port different selon la porte par laquelle
     * on entre.
     *
     * Zero point de domaine, et aucune statistique : l'echelon 1 est le nœud
     * d'entree, pas une recompense. Le cout reel est le parchemin.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getWeaponPortRungs(): array
    {
        $rungs = [];
        foreach ($this->portCatalog->families() as $family) {
            $rungs[$family['rung1']['reference']] = [
                'title' => $family['rung1']['title'],
                'slug' => $family['rung1']['slug'],
                'description' => sprintf('Permet de tenir une %s de palier 1.', mb_strtolower($family['label'])),
                'requiredPoints' => 0,
                'domain' => $family['taught_by'],
            ];
        }

        return $rungs;
    }

    /**
     * Les echelons 2 et 3 des lignes d'armure, generes (ONB-20b-b).
     *
     * Les armes reutilisent des nœuds historiques (`*_weapon_t2/t3`) ; les
     * armures n'en avaient aucun — aucun arbre de combat ne portait un nœud de
     * port d'armure. Leurs echelons superieurs se generent donc depuis la
     * declaration, aux memes points que ceux des armes (10 / 25), et
     * `rewireWeaponPortLadders()` les cable comme les autres : domaines de la
     * famille, chaine sur l'echelon precedent, aucune statistique.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getArmorPortUpperRungs(): array
    {
        $rungs = [];
        foreach ($this->portCatalog->families() as $family) {
            if ('armor' !== $family['line']) {
                continue;
            }

            foreach (['rung2' => ['tier' => 2, 'points' => 10], 'rung3' => ['tier' => 3, 'points' => 25]] as $rung => $spec) {
                $reference = $family[$rung];
                $rungs[$reference] = [
                    'title' => sprintf('%s (T%d)', $family['rung1']['title'], $spec['tier']),
                    'slug' => str_replace('_', '-', $reference),
                    'description' => sprintf('Permet d\'equiper les pieces de %s de palier %d.', mb_strtolower($family['label']), $spec['tier']),
                    'requiredPoints' => $spec['points'],
                    'domain' => $family['taught_by'],
                ];
            }
        }

        return $rungs;
    }

    /**
     * Recable les echelons 2 et 3 sur leur famille (ONB-20b).
     *
     * Deux corrections, et la seconde est celle qui compte :
     *
     * 1. **Les arbres** — `berserk_weapon_t2` (« Maitrise de la hache ») vivait
     *    dans le seul arbre du berserker, feu x melee. Porter une hache
     *    imposait donc le feu. L'echelon rejoint tous les arbres qui enseignent
     *    la famille.
     * 2. **Le prerequis** — il exigeait `berserk_apprenti_1`, un nœud propre au
     *    berserker. Un soldat qui voulait la hache T2 devait donc entrer dans
     *    l'arbre du berserker : « en ouvrir un seul suffit » etait faux. Un
     *    echelon exige desormais **l'echelon precedent de sa famille**, ce que
     *    le canon demande (*l'arc a poulie exige l'arc*).
     *
     * **Troisieme correction (GAME_TREE_ANATOMY § 10, ecart n° 5) — un echelon
     * ne porte aucune statistique.** Les douze echelons livres en portaient une
     * (`critical +1/+2` pour la dague, `damage +1/+1` pour la hache et l'epee,
     * `life +3/+5` pour la lance, `hit +1/+2` pour l'arc, `heal +1/+1` pour le
     * baton), et la correction 1 ci-dessus les a rendus **partages** : depuis,
     * `CombatSkillResolver` appliquant un passif des qu'**un** des domaines du
     * nœud occupe la case de l'action, l'echelon de la dague donnait son critique
     * dans les quatre arbres qui l'enseignent, et celui du baton son `heal` dans
     * **dix** arbres — dont neuf de sorts, ou soigner n'est meme pas dans la
     * palette. Une fuite de budget hors de tout arbre, du meme genre que les
     * `DomainSynergy`.
     *
     * La loi est celle que `getWeaponPortRungs()` applique deja a l'echelon 1 et
     * que le commentaire d'`equipment_ports.yaml` annonce : **un echelon est une
     * porte, jamais une recompense** (GAME_ARCHETYPES § 6.1 — un acces de port
     * vaut 0 point de budget). Elle est tenue ici plutot qu'au point de
     * declaration, pour qu'un echelon ajoute plus tard ne puisse pas la
     * contourner en silence.
     *
     * @param array<string, array<string, mixed>> $skills
     *
     * @return array<string, array<string, mixed>>
     */
    private function rewireWeaponPortLadders(array $skills): array
    {
        foreach ($this->portCatalog->families() as $family) {
            $chain = [$family['rung1']['reference'], $family['rung2'], $family['rung3']];

            foreach ([1, 2] as $index) {
                $reference = $chain[$index];
                if (!isset($skills[$reference])) {
                    continue;
                }

                $skills[$reference]['domain'] = $family['taught_by'];
                $skills[$reference]['requirements'] = [$chain[$index - 1]];

                foreach (self::COMBAT_STATS as $stat) {
                    unset($skills[$reference][$stat]);
                }
            }
        }

        return $skills;
    }

    /**
     * L'accord d'hybride reserve de chaque arbre de combat (DOM-07).
     *
     * GAME_DOMAINS § 8 : « chaque arbre de combat porte un nœud d'accord
     * reserve, inactif au lancement, qui s'activera quand la fusion ouvrira.
     * Poser le nœud maintenant coute une ligne de donnees et evite un refactor
     * d'arbre le jour venu. »
     *
     * **Genere plutot qu'ecrit vingt-quatre fois**, et c'est la forme la plus
     * fidele a l'intention : le nœud est *le meme* dans les vingt-quatre arbres,
     * a l'element pres. Le copier-coller aurait laisse vingt-quatre occasions de
     * diverger sur un nœud dont tout l'interet est d'etre uniforme.
     *
     * **L'hybride n'est pas nomme**, et c'est delibere. Le canon ne nomme que
     * Magma et Inferno, pour le feu ; inventer les sept autres couples serait
     * decider de la fusion avant qu'elle n'existe. Le nœud declare son element
     * parent — la seule chose que la doctrine fixe — et l'enum `Element`
     * n'accueillera ses composes qu'au jalon qui les rendra jouables.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getDormantHybridAccords(): array
    {
        // Les vingt-quatre arbres de combat, avec l'element dont leur hybride
        // sera issu. La recolte et l'artisanat n'en portent pas : la fusion est
        // une affaire de materia, et les metiers n'en ont pas (regle 9).
        $combatDomains = [
            'pyromancy' => 'fire', 'berserker' => 'fire', 'artificer' => 'fire',
            'hydromancer' => 'water', 'healer' => 'water', 'tidecaller' => 'water',
            'stormcaller' => 'air', 'archer' => 'air', 'wanderer' => 'air',
            'geomancer' => 'earth', 'defender' => 'earth', 'guardian' => 'earth',
            'soldier' => 'metal', 'knight' => 'metal', 'engineer' => 'metal',
            'hunter' => 'beast', 'tamer' => 'beast', 'druid' => 'beast',
            'paladin' => 'light', 'priest' => 'light', 'inquisitor' => 'light',
            'assassin' => 'dark', 'necromancer' => 'dark', 'warlock' => 'dark',
        ];

        $accords = [];
        foreach ($combatDomains as $domain => $element) {
            $accords[$domain . '_hybrid_accord'] = [
                'slug' => str_replace('_', '-', $domain) . '-hybrid-accord',
                'title' => 'Accord d\'hybride',
                'description' => 'Un accord reserve, pose et pas encore ouvert : il attend que la fusion des elements existe.',
                'actions' => [['action' => 'materia.hybrid', 'element' => $element]],
                // Au-dessus du sommet, et pas d'un cran arbitraire : le capstone
                // vaut 100 points (echelle 0/10/25/50/100), l'accord dormant en
                // vaut **150** — GAME_ARCHETYPES § 6.1. Il portait 200, une
                // valeur heritee de l'echelle d'avant le gabarit, ou le rang 5
                // culminait a 150 ; l'ecart n'avait aucun effet puisque le nœud
                // n'est pas apprenable, mais il aurait ete lu comme une decision
                // le jour ou la fusion ouvrira.
                'requiredPoints' => 150,
                'domain' => $domain,
                'dormant' => true,
            ];
        }

        return $accords;
    }

    // =========================================================================
    // PYROMANCIE (feu) — 15 skills, domaine modele complet
    // =========================================================================
    /**
     * Le Pyromancien — feu x sorts x assaut, « le Foyer » (ARC-07a).
     *
     * GAME_ARCHETYPES § 9.1. Le **premier des quatre arbres patrons**, et le
     * premier arbre du jeu dont les passifs ne sont plus des statistiques
     * plates : *`damage: +1` vaut +50 % sur un geste a 2 degats et +8 % sur un
     * geste a 12*, donc ineequilibrable. Ils deviennent des leviers en
     * pourcentage, payes en points de budget.
     *
     * **Ce que l'arbre depense**, et il tombe sur ses 50 pb par branche :
     *
     * | | Braise | Eclat |
     * |---|---:|---:|
     * | `power` 3 + capstone 14 | 17 | 17 |
     * | `critical` 3 + 6 | 9 | 9 |
     * | `critical_power` 6 (+9 Braise) | 15 | 6 |
     * | `grip` *(teinte)* | 9 | — |
     * | `pierce` / `tempo` | — | 9 + 9 |
     * | **Total** | **50** | **50** |
     *
     * Palette d'assaut : `power` (principal) + `critical`, `critical_power`,
     * `pierce`, `tempo`. La Braise depense **41 pb dans sa palette** et 9 hors
     * — la teinte `grip`, sur **un seul** levier, sous le plafond de 10. Aucun
     * plafond de levier n'est atteint : `power` 17 <= 20, `critical` 9 <= 12,
     * `critical_power` 15 <= 15.
     *
     * **La fourche ouvre un geste par branche** (§ 6.1 bis, regle 5), et c'est
     * elle qui decide si le choix est reel : *deux branches qui ne different
     * que par leurs passifs produisent le meme combat, au tour pres.* Le
     * Brasier **reste** sur le terrain et `grip` l'allonge ; la Nova tombe tout
     * de suite. La teinte `grip` ne vit que dans la Braise, ce qui fait que les
     * deux branches ne sont pas deux dosages du meme arbre.
     *
     * **Le capstone est atteignable au tour 2 avec le seul kit d'entree** :
     * Flammeche applique la Brulure, la marque du feu, et l'accord est gratuit
     * (GAME_MATERIA § 3). `target_marked` est donc une condition **frequente**
     * (x1,4), pas une condition rare — l'ecart n° 11 que le canon a tranche.
     *
     * **Ce que ce jalon ne fait pas, et c'est nomme** : ramener l'arbre a ses
     * 6 accords. Sept accords surnumeraires restent (Toucher brulant, Flamme du
     * phenix, Feu, Inferno, Souffle du dragon, Explosion solaire, Eruption
     * volcanique) ; les retirer supprimerait leur **materia**, qui se derive de
     * l'unlock (MAT-03), et trois fichiers les nomment par reference —
     * `PlayerItemFixtures`, `MateriaFusionManager`, `world_1.yaml`. C'est une
     * chirurgie de catalogue, pas une ecriture d'arbre : **ARC-07b**.
     */
    private function getPyromancySkills(): array
    {
        $d = 'pyromancy';

        return [
            // --- Entree (0 pt) : les deux accords du jour 1 ------------------
            // GAME_MATERIA § 3 : exactement deux accords gratuits par arbre.
            // L'un des deux **applique la marque** (§ 1.1) — sans quoi le
            // capstone serait conditionne a un geste que le joueur n'a pas.
            'pyro_apprenti_1' => [
                'title' => 'Materia : Boule de feu',
                'slug' => 'pyro-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Boule de feu',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-ball']],
            ],
            'pyro_apprenti_2' => [
                'title' => 'Materia : Flammeche',
                'slug' => 'pyro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Flammeche',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame']],
            ],

            // --- Palier 1 (10 pts) : 2 passifs a 3 pb + 1 accord -------------
            // Les passifs du palier 1 ne sont **jamais conditionnels**
            // (§ 6.1) : au jour 1, un joueur n'a pas encore de tenue a
            // arbitrer, et un bonus qui ne s'allume pas se lit comme un bug.
            'pyro_rang2_1' => [
                'title' => 'Points faibles',
                'slug' => 'pyro-rang2-1',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 3]],
                'requirements' => ['pyro_apprenti_1'],
            ],
            'pyro_rang2_2' => [
                'title' => 'Souffle d\'attisage',
                'slug' => 'pyro-rang2-2',
                'description' => 'Augmente les degats de pyromancie',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 3]],
                'requirements' => ['pyro_apprenti_1'],
            ],
            'pyro_rang2_3' => [
                'title' => 'Materia : Mur de feu',
                'slug' => 'pyro-rang2-3',
                'description' => 'Permet d\'utiliser la materia Mur de feu',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-wall']],
                'requirements' => ['pyro_apprenti_2'],
            ],

            // --- Palier 2 (25 pts) : 2 passifs a 6 pb + 1 accord -------------
            'pyro_rang3_3' => [
                'title' => 'Cœur de braise',
                'slug' => 'pyro-rang3-3',
                'description' => 'Un critique de feu fait plus mal quand il tombe',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 6]],
                'requirements' => ['pyro_rang2_1'],
            ],
            // Le premier passif **conditionnel** de l'arbre (§ 4.3) : c'est ce
            // qui fait de l'equipement un build plutot qu'un total. Le budget
            // compte l'effet **moyen** (6 pb), l'ecran affiche l'effet obtenu.
            'pyro_rang3_5' => [
                'title' => 'Chaleur seche',
                'slug' => 'pyro-rang3-5',
                'description' => 'Le feu porte plus juste quand rien de lourd ne l\'etouffe',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 6, 'condition' => 'armor:cloth']],
                'requirements' => ['pyro_rang2_2'],
            ],
            'pyro_rang3_1' => [
                'title' => 'Materia : Pluie de flammes',
                'slug' => 'pyro-rang3-1',
                'description' => 'Permet d\'utiliser la materia Pluie de flammes (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame-rain']],
                'requirements' => ['pyro_rang2_1', 'pyro_rang2_2'],
            ],

            // --- Palier 3 (50 pts) : la fourche ------------------------------
            // Deux branches de deux passifs **et d'un accord chacune**, dont on
            // n'apprend qu'une : l'arbre ecrit 60 pb, le personnage en porte
            // 50. Les prerequis ne traversent jamais la fourche (§ 6.6).
            //
            // *La Braise* tient le feu qui **dure**, *l'Eclat* le feu qui
            // **passe**. Elles ne partagent aucun levier.
            'pyro_ember_1' => [
                'title' => 'Braise durable',
                'slug' => 'pyro-ember-1',
                'description' => 'Ce que le feu laisse s\'accroche plus longtemps',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'grip', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'ember']],
                'requirements' => ['pyro_rang3_1'],
            ],
            'pyro_ember_2' => [
                'title' => 'Souffle de forge',
                'slug' => 'pyro-ember-2',
                'description' => 'Quand le critique tombe, il fait le trou',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'ember']],
                'requirements' => ['pyro_rang3_1'],
            ],
            'pyro_flare_1' => [
                'title' => 'Fonte des ecailles',
                'slug' => 'pyro-flare-1',
                'description' => 'Le feu ronge la resistance avant de la traverser',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'pierce', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'flare']],
                'requirements' => ['pyro_rang3_1'],
            ],
            'pyro_flare_2' => [
                'title' => 'Depart de feu',
                'slug' => 'pyro-flare-2',
                'description' => 'Prendre le tour avant l\'autre',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'flare']],
                'requirements' => ['pyro_rang3_1'],
            ],
            // L'accord de chaque branche — la regle 5 de § 6.1 bis, et celle
            // qui decide si la fourche est un choix ou une decoration.
            'pyro_ember_accord' => [
                'title' => 'Materia : Brasier',
                'slug' => 'pyro-ember-accord',
                'description' => 'Permet d\'utiliser la materia Brasier — le feu qui reste',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'brasier'],
                    ['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'ember'],
                ],
                'requirements' => ['pyro_rang3_1'],
            ],
            'pyro_rang3_2' => [
                'title' => 'Materia : Nova de feu',
                'slug' => 'pyro-rang3-2',
                'description' => 'Permet d\'utiliser la materia Nova de feu — le geste de pointe, tout de suite',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'fire-nova'],
                    ['action' => 'specialization.branch', 'domain' => 'pyromancien', 'branch' => 'flare'],
                ],
                'requirements' => ['pyro_rang3_1'],
            ],

            // --- Capstone (100 pts) ------------------------------------------
            // Un seul passif, **conditionnel**, 14 pb sur le levier principal.
            // Sa condition est atteignable au tour 2 avec le seul kit d'entree
            // (Flammeche applique la Brulure), donc **frequente** : x1,4 et non
            // x2,0 — la correction de l'ecart n° 11 (§ 7, decision 23).
            'pyro_capstone' => [
                'title' => 'Foyer entretenu',
                'slug' => 'pyro-capstone',
                'description' => 'Le feu s\'acharne sur ce qui brule deja',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 14, 'condition' => 'target_marked']],
                'requirements' => ['pyro_rang3_1'],
            ],

            // --- Les deux accords que l'arithmetique reclame -----------------
            //
            // Le gabarit compte **quatre** nœuds au palier 1 et quatre au
            // palier 2, dont un echelon de port ; mais les echelons sont
            // **generes** hors du corps de l'arbre (ONB-20b), si bien que le
            // compte des 390 points ne tombe juste que si le corps en declare
            // quatre. Ces deux accords sont donc ce qui fait tenir
            // `4x10 + 4x25 + 6x50 + 100 = 540`, dont un personnage paie 390 en
            // n'apprenant qu'une branche.
            //
            // C'est un ecart entre la **table du § 9.1** (qui compte l'echelon
            // de port parmi les quatre) et l'**arithmetique du § 6.1** : il se
            // referme le jour ou DOM-09 bornera les nœuds partages, un arbre
            // de sorts heritant aujourd'hui de **six** echelons generes (baton,
            // baguette, tissu) la ou le gabarit en veut deux.
            'pyro_rang2_4' => [
                'title' => 'Materia : Toucher brulant',
                'slug' => 'pyro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Toucher brulant',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'burning-touch']],
                'requirements' => ['pyro_apprenti_2'],
            ],
            'pyro_rang3_4' => [
                'title' => 'Materia : Flamme du phenix',
                'slug' => 'pyro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Flamme du phenix (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'phoenix-flame']],
                'requirements' => ['pyro_rang3_1'],
            ],
            // Son prerequis suit les nœuds retires : il pointait vers deux
            // accords que ce jalon a supprimes, et **une reference morte fait
            // tomber tout le chargement des fixtures**, pas seulement ce nœud.
            // Un seul parent, comme le § 6.6 le demande.
            'pyro_rang5_1' => [
                'title' => 'Materia : Eruption volcanique',
                'slug' => 'pyro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Eruption volcanique',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'volcanic-eruption']],
                'requirements' => ['pyro_rang3_4'],
            ],
        ];
    }

    private function getBerserkerSkills(): array
    {
        $d = 'berserker';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'berserk_apprenti_1' => [
                'title' => 'Materia : Flamme de rage',
                'slug' => 'berserk-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Flamme de rage',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rage-flame']],
            ],
            'berserk_apprenti_2' => [
                'title' => 'Materia : Toucher brulant',
                'slug' => 'berserk-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher brulant',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'burning-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'berserk_rang2_1' => [
                'title' => 'Brutalite',
                'slug' => 'berserk-rang2-1',
                'description' => 'Augmente les degats physiques',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['berserk_apprenti_1'],
            ],
            'berserk_rang2_2' => [
                'title' => 'Coups sauvages',
                'slug' => 'berserk-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['berserk_apprenti_1'],
            ],
            'berserk_rang2_3' => [
                'title' => 'Materia : Vague de chaleur',
                'slug' => 'berserk-rang2-3',
                'description' => 'Permet d\'utiliser la materia Vague de chaleur',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'heat-wave']],
                'requirements' => ['berserk_apprenti_2'],
            ],
            'berserk_rang2_4' => [
                'title' => 'Peau epaisse',
                'slug' => 'berserk-rang2-4',
                'description' => 'Augmente les points de vie',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['berserk_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'berserk_rang3_1' => [
                'title' => 'Materia : Charge enflammee',
                'slug' => 'berserk-rang3-1',
                'description' => 'Permet d\'utiliser la materia Charge enflammee',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'berserk-charge']],
                'requirements' => ['berserk_rang2_1', 'berserk_rang2_2'],
            ],
            'berserk_rang3_2' => [
                'title' => 'Materia : Combustion',
                'slug' => 'berserk-rang3-2',
                'description' => 'Permet d\'utiliser la materia Combustion',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'combustion']],
                'requirements' => ['berserk_rang2_3'],
            ],
            'berserk_rang3_3' => [
                'title' => 'Rage interieure',
                'slug' => 'berserk-rang3-3',
                'description' => 'Augmente les degats et le critique',
                'requiredPoints' => 25,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['berserk_rang2_4'],
            ],
            'berserk_rang3_4' => [
                'title' => 'Materia : Fouet de feu',
                'slug' => 'berserk-rang3-4',
                'description' => 'Permet d\'utiliser la materia Fouet de feu',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-whip']],
                'requirements' => ['berserk_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'berserk_rang4_1' => [
                'title' => 'Materia : Frappe de furie',
                'slug' => 'berserk-rang4-1',
                'description' => 'Permet d\'utiliser la materia Frappe de furie — degats devastateurs',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fury-strike']],
                'requirements' => ['berserk_rang3_1', 'berserk_rang3_2'],
            ],
            'berserk_rang4_2' => [
                'title' => 'Materia : Frappe meteorique',
                'slug' => 'berserk-rang4-2',
                'description' => 'Permet d\'utiliser la materia Frappe meteorique',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'meteor-strike']],
                'requirements' => ['berserk_rang3_3', 'berserk_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'berserk_rang5_1' => [
                'title' => 'Materia : Furie sanguinaire',
                'slug' => 'berserk-rang5-1',
                'description' => 'Permet d\'utiliser la materia Furie sanguinaire',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blood-fury']],
                'requirements' => ['berserk_rang4_1', 'berserk_rang4_2'],
            ],

            // Maitrise des armes (haches)
            'berserk_weapon_t2' => [
                'title' => 'Maitrise de la hache (T2)',
                'slug' => 'berserk-weapon-t2',
                'description' => 'Permet d\'equiper les haches de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['berserk_apprenti_1'],
            ],
            'berserk_weapon_t3' => [
                'title' => 'Maitrise de la hache (T3)',
                'slug' => 'berserk-weapon-t3',
                'description' => 'Permet d\'equiper les haches de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['berserk_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // ARTIFICIER — feu x distance x controle, « Meche courte » / « Reserve »
    // (ARC-08c)
    // =========================================================================
    /**
     * Le troisieme arbre au gabarit, et la case `control x ranged`.
     *
     * GAME_TREE_ANATOMY § 13 le deroule en entier, et le choisit pour une
     * raison precise : il croise le **test du voisin sur trois axes a la fois**
     * — element et marque avec le Pyromancien, fonction avec le Necromancien,
     * registre avec l'Archer. *Trois arbres, une seule marque, trois verbes :
     * frapper dessus, la faire durer, la depenser.*
     *
     * **Sa tension d'archetype est la seule du jeu** (§ 13.4) : sa fonction
     * allonge les combats, et sa ressource se vide avec leur longueur. C'est ce
     * qui fait de sa teinte — `wind`, qui recupere de la munition — une
     * reparation plutot qu'un assaisonnement, et ce qui donne son sens a la
     * fourche : *finir vite* contre *pouvoir durer*.
     *
     * **Le capstone applique le corollaire 2 a la lettre** (§ 7.1), et mieux
     * que le document ne l'annoncait : `grip` plafonne a 20 pb, le sommet en
     * consomme 14, et le palier 1 prend les 6 qui restent — le levier principal
     * est donc **absent des deux branches**. C'est la forme forte de *le levier
     * principal d'un arbre est presque absent de sa propre fourche*.
     */
    private function getArtificerSkills(): array
    {
        $d = 'artificer';

        return [
            // --- Entree (0 pt) : les deux accords du jour 1 ------------------
            // Le Piege incendiaire porte **Brulure**, la marque du feu
            // (ARC-13b-a) : c'est lui qui rend le capstone atteignable des la
            // premiere rencontre, puisqu'il ne coute aucun point.
            'artif_apprenti_1' => [
                'title' => 'Materia : Piege incendiaire',
                'slug' => 'artif-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Piege incendiaire — ce qui prend feu et ne s\'arrete plus',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-trap']],
            ],
            // Le plan B du jour 1, et la loi 1 du § 5.1 : un arbre de controle
            // qui ne saurait que poser des entraves ne finirait jamais un
            // combat — celui-ci ouvre un degat **et** de quoi tenir.
            'artif_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'etincelles',
                'slug' => 'artif-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'etincelles',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ember-shield']],
            ],

            // --- Palier 1 (10 pts) : 2 passifs a 3 pb + 1 accord + 1 port ----
            // `grip` est le levier principal du controle : il entre ici, puis
            // au palier 2, puis dans une seule branche — 3 + 6 + 9 = 18, sous
            // son plafond de 20. Le capstone, lui, vise `thrift`.
            'artif_rang2_1' => [
                'title' => 'Meche calibree',
                'slug' => 'artif-rang2-1',
                'description' => 'Ce qui prend feu au bon moment brule plus longtemps',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'grip', 'points' => 3]],
                'requirements' => ['artif_apprenti_1'],
            ],
            'artif_rang2_2' => [
                'title' => 'Lunette',
                'slug' => 'artif-rang2-2',
                'description' => 'Viser prend un instant, et le rend au tir suivant',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'hit', 'points' => 3]],
                'requirements' => ['artif_apprenti_2'],
            ],
            'artif_rang2_3' => [
                'title' => 'Materia : Bombe flash',
                'slug' => 'artif-rang2-3',
                'description' => 'Permet d\'utiliser la materia Bombe flash — ce qui arrete une ligne entiere',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flash-bomb']],
                'requirements' => ['artif_apprenti_1'],
            ],

            // --- Palier 2 (25 pts) : 2 passifs a 6 pb + 1 accord + 1 port ----
            'artif_rang3_1' => [
                'title' => 'Charges mesurees',
                'slug' => 'artif-rang3-1',
                'description' => 'Doser la poudre, c\'est en avoir encore au dixieme tir',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 6]],
                'requirements' => ['artif_rang2_1'],
            ],
            // Le premier passif **conditionnel** de l'arbre (§ 4.3) : c'est lui
            // qui fait de l'equipement un build plutot qu'un total.
            'artif_rang3_2' => [
                'title' => 'Ligne de tir',
                'slug' => 'artif-rang3-2',
                'description' => 'Les deux mains sur l\'arme, et rien ne devie',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'hit', 'points' => 6, 'condition' => 'weapon:crossbow']],
                'requirements' => ['artif_rang2_2'],
            ],
            // Le nœud charniere : la fourche et le capstone en dependent tous.
            'artif_rang3_3' => [
                'title' => 'Materia : Nappe de poix',
                'slug' => 'artif-rang3-3',
                'description' => 'Permet d\'utiliser la materia Nappe de poix — le terrain reste, et qui y passe brule',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'pitch-slick']],
                'requirements' => ['artif_rang2_1', 'artif_rang2_2'],
            ],

            // --- Palier 3 (50 pts) : la fourche ------------------------------
            // *Meche courte* depense ce qui brule ; *Reserve* fait durer et
            // recupere. Aucun levier commun — {`grip`, `tempo`} contre
            // {`wind`, `pierce`} —, et l'opposition est celle du § 13.4 : la
            // fonction allonge les combats, la ressource se vide avec eux.
            'artif_fuse_1' => [
                'title' => 'Pointes durcies',
                'slug' => 'artif-fuse-1',
                'description' => 'Ce qui perce n\'a pas besoin de bruler longtemps',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'pierce', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'short_fuse']],
                'requirements' => ['artif_rang3_3'],
            ],
            'artif_fuse_2' => [
                'title' => 'Deux crans d\'avance',
                'slug' => 'artif-fuse-2',
                'description' => 'Armer avant que l\'autre ne bouge',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'short_fuse']],
                'requirements' => ['artif_rang3_3'],
            ],
            // La teinte de l'arbre, et elle ne vit que dans cette branche :
            // `wind` rend de la munition, c'est-a-dire exactement ce que la
            // fonction consomme.
            'artif_reserve_1' => [
                'title' => 'Rien ne se perd',
                'slug' => 'artif-reserve-1',
                'description' => 'Ce qui n\'a pas servi se ramasse et resservira',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'wind', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'reserve']],
                'requirements' => ['artif_rang3_3'],
            ],
            'artif_reserve_2' => [
                'title' => 'Longue campagne',
                'slug' => 'artif-reserve-2',
                'description' => 'Tenir la ligne plus longtemps que le carquois ne le permet',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'reserve']],
                'requirements' => ['artif_rang3_3'],
            ],
            // L'accord de chaque branche — la regle 5 du § 6.1 bis.
            'artif_fuse_accord' => [
                'title' => 'Materia : Barrage d\'artillerie',
                'slug' => 'artif-fuse-accord',
                'description' => 'Permet d\'utiliser la materia Barrage d\'artillerie — tout, et tout de suite',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'artillery-barrage'],
                    ['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'short_fuse'],
                ],
                'requirements' => ['artif_rang3_3'],
            ],
            'artif_reserve_accord' => [
                'title' => 'Materia : Tir couvrant',
                'slug' => 'artif-reserve-accord',
                'description' => 'Permet d\'utiliser la materia Tir couvrant — personne n\'avance tant que la ligne tient',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'covering-fire'],
                    ['action' => 'specialization.branch', 'domain' => 'artificier', 'branch' => 'reserve'],
                ],
                'requirements' => ['artif_rang3_3'],
            ],

            // --- Capstone (100 pts) ------------------------------------------
            // **`grip`, et pas `thrift`.** GAME_TREE_ANATOMY § 13.2 pose ici
            // « Economie de guerre », un sommet sur `thrift` — mais le canon
            // (§ 7.1, table de la decision 22) ne range `thrift` parmi les
            // sommets du controle que **pour l'arbre a pacte**, et celui-ci n'en
            // porte aucun. Le sommet revient donc au levier canonique de sa
            // fonction.
            //
            // Et le corollaire 2 s'y montre **mieux** que dans le document : 14
            // pb sur un plafond de 20 laissent 6 pb, que le palier 1 consomme
            // entierement — `grip` est donc **absent des deux branches**, ce qui
            // est la forme forte de *le levier principal d'un arbre est presque
            // absent de sa propre fourche*.
            //
            // Sa condition — une cible qui brule — est posee des le tour 1 par
            // un accord gratuit, donc **frequente** : x1,4 et non x2,0 (§ 7,
            // decision 23).
            'artif_capstone' => [
                'title' => 'Ce qui ne s\'eteint pas',
                'slug' => 'artif-capstone',
                'description' => 'Ce qui brule deja n\'a pas besoin qu\'on y remette',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'grip', 'points' => 14, 'condition' => 'target_marked']],
                'requirements' => ['artif_rang3_3'],
            ],

            // --- Les echelons de port (0 pb) ---------------------------------
            // *Un echelon est une porte, jamais une recompense.*
            'artificer_weapon_t2' => [
                'title' => 'Maitrise de l\'arbalete (T2)',
                'slug' => 'artificer-weapon-t2',
                'description' => 'Permet d\'equiper les arbaletes de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['artif_apprenti_1'],
            ],
            'artificer_weapon_t3' => [
                'title' => 'Maitrise de l\'arbalete (T3)',
                'slug' => 'artificer-weapon-t3',
                'description' => 'Permet d\'equiper les arbaletes de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['artificer_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // SOLDAT (metal) — 18 skills, DPS CaC combos d'armes
    // =========================================================================
    /**
     * Le Soldat — metal x melee x encaisse, « la Ligne » (ARC-07c).
     *
     * GAME_ARCHETYPES § 9.3. Le troisieme patron, et **le premier arbre de
     * melee ecrit au gabarit**. Le canon previent que cet archetype *n'existe
     * pas sans la decision 1* : sans materia de technique, l'arbre du Soldat
     * est un jeu de passifs bornes au registre melee qui ne qualifient **aucune
     * action**, l'attaque de base ne les lisant pas. ARC-02 a livre le
     * registre ; ce jalon ecrit l'arbre qui s'en sert.
     *
     * **Ce que l'arbre depense**, et les deux branches tombent sur 50 pb :
     *
     * | | Le Mur | La Ligne mobile |
     * |---|---:|---:|
     * | `guard` (capstone) | 14 | 14 |
     * | `hit` 3 + 6 | 9 | 9 |
     * | `ward` 3 (+9 Mur) | 12 | 3 |
     * | `life` 6 (+9 Mur) | 15 | 6 |
     * | `dodge` / `power` *(teinte)* | — | 9 + 9 |
     * | **Total** | **50** | **50** |
     *
     * `guard` plafonne a **15** et non 20 (§ 5) : son efficacite est
     * hyperbolique, et *la mitigation d'un tank vient de son armure, pas de son
     * arbre*. Consequence assumee — le capstone en consommant 14, il ne reste
     * **1 pb**, c'est-a-dire rien : un arbre d'encaisse achete `guard` a son
     * sommet ou jamais, et repartit ses quatre autres leviers. C'est la palette
     * effective du § 5.0, et elle produit toute seule la meilleure fourche
     * possible pour cette fonction — **eviter ou absorber**.
     *
     * **La fourche est la plus parlante des quatre** : *ce n'est pas offensif
     * contre defensif, c'est en groupe contre seul*. Le Mur encaisse et donne
     * (PV, sang-froid, et le Rempart qu'on vient chercher en donjon) ; la Ligne
     * mobile evite et finit (esquive, et une Charge qui regle un combat seul).
     * Les deux tiennent la fonction *encaisse*.
     *
     * **Le capstone dit ce qu'est l'archetype** : le Soldat est le seul dont la
     * condition ne se *provoque* pas — elle lui arrive. *Il ne decide pas du
     * combat, il refuse de le perdre.* Et parce qu'« avoir encaisse au tour
     * precedent » est vrai presque tous les tours au contact, elle est payee
     * au tarif d'une condition **frequente** (x1,4) — la correction du § 9 bis
     * que le § 4.3 a rendue generale.
     *
     * **Un defaut trouve en ecrivant l'arbre, et il preexiste au jalon** :
     * l'accord d'entree qui portait l'Entaille etait `magnetic-pull`, de
     * registre **`Spell`**. Un arbre de melee dont le geste marque est un sort
     * ne qualifie pas ses propres passifs (l'invariant 7 d'ARC-02b), et le
     * § 9.3 veut que **tous** les accords du Soldat soient des techniques. La
     * marque passe donc sur `sharp-blade`, qui est en melee, blesse, et ne
     * portait aucun effet — le meme choix qu'ARC-13b-a a fait vingt fois.
     * `magnetic-pull` reste ouvert par l'Ingenieur, a qui son registre convient.
     */
    private function getSoldierSkills(): array
    {
        $d = 'soldier';

        return [
            // --- Entree (0 pt) : deux techniques ------------------------------
            'soldier_apprenti_1' => [
                'title' => 'Materia : Frappe appuyee',
                'slug' => 'soldier-apprenti-1',
                'description' => 'Permet d\'utiliser la technique Frappe appuyee — elle laisse une Entaille',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sharp-blade']],
            ],
            // **Le plan B du jour 1**, et la mise en place du capstone : un tour
            // paye, trois tours couverts. Le § 9 bis a montre qu'une garde qui
            // ne couvre que le tour ou elle est jouee **punit l'encaisse de se
            // defendre** — il perd en degats exactement ce qu'il gagne en
            // survie. D'ou la loi du depot etendue a toute protection (ARC-11b).
            'soldier_apprenti_2' => [
                'title' => 'Materia : Garde haute',
                'slug' => 'soldier-apprenti-2',
                'description' => 'Permet d\'utiliser la technique Garde haute — un tour paye, trois tours couverts',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'garde-haute']],
            ],

            // --- Palier 1 (10 pts) --------------------------------------------
            'soldier_rang2_1' => [
                'title' => 'Œil du drill',
                'slug' => 'soldier-rang2-1',
                'description' => 'On ne rate pas ce qu\'on a appris a viser',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'hit', 'points' => 3]],
                'requirements' => ['soldier_apprenti_1'],
            ],
            'soldier_rang2_2' => [
                'title' => 'Discipline',
                'slug' => 'soldier-rang2-2',
                'description' => 'Ce qu\'on tente de vous imposer prend moins souvent',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'ward', 'points' => 3]],
                'requirements' => ['soldier_apprenti_1'],
            ],
            'soldier_rang2_3' => [
                'title' => 'Materia : Estoc brisant',
                'slug' => 'soldier-rang2-3',
                'description' => 'Permet d\'utiliser la technique Estoc brisant — le geste qui perce l\'armure',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-fist']],
                'requirements' => ['soldier_apprenti_2'],
            ],

            // --- Palier 2 (25 pts) --------------------------------------------
            'soldier_rang3_1' => [
                'title' => 'Endurance de marche',
                'slug' => 'soldier-rang3-1',
                'description' => 'On tient plus longtemps parce qu\'on a marche plus loin',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'life', 'points' => 6]],
                'requirements' => ['soldier_rang2_1'],
            ],
            'soldier_rang3_3' => [
                'title' => 'Garde travaillee',
                'slug' => 'soldier-rang3-3',
                'description' => 'Le bouclier ne sert pas qu\'a encaisser : il ouvre la ligne',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'hit', 'points' => 6, 'condition' => 'shield']],
                'requirements' => ['soldier_rang2_2'],
            ],
            // **Le depot de l'encaisseur.** Une rencontre a PV partages ne se
            // « prend » pas — elle s'amortit (§ 7 bis) : ce qui agit sur un
            // **etat** se multiplie par le nombre d'allies, ce qui agit sur une
            // **action** ne se multiplie pas. Une absorption posee sur chaque
            // corps est donc ce que l'encaisse apporte a un groupe, et son
            // attaque ne l'est pas.
            'soldier_rang3_2' => [
                'title' => 'Materia : Mur de boucliers',
                'slug' => 'soldier-rang3-2',
                'description' => 'Permet d\'utiliser la technique Mur de boucliers — une absorption sur chaque allie',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mur-de-boucliers']],
                'requirements' => ['soldier_rang2_3'],
            ],

            // --- Palier 3 (50 pts) : la fourche --------------------------------
            'soldier_wall_1' => [
                'title' => 'Carcasse',
                'slug' => 'soldier-wall-1',
                'description' => 'Il y a simplement plus a traverser',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'life', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'wall']],
                'requirements' => ['soldier_rang3_2'],
            ],
            'soldier_wall_2' => [
                'title' => 'Pied ferme',
                'slug' => 'soldier-wall-2',
                'description' => 'On ne vous deplace pas, et on ne vous impose rien',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'ward', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'wall']],
                'requirements' => ['soldier_rang3_2'],
            ],
            'soldier_skirmish_1' => [
                'title' => 'Jeu de jambes',
                'slug' => 'soldier-skirmish-1',
                'description' => 'Ce qu\'on evite n\'est pas reduit : il n\'a pas lieu',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'dodge', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'skirmish']],
                'requirements' => ['soldier_rang3_2'],
            ],
            'soldier_skirmish_2' => [
                'title' => 'Bras d\'acier',
                'slug' => 'soldier-skirmish-2',
                'description' => 'Il faut bien tuer, aussi',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'skirmish']],
                'requirements' => ['soldier_rang3_2'],
            ],
            'soldier_wall_accord' => [
                'title' => 'Materia : Rempart',
                'slug' => 'soldier-wall-accord',
                'description' => 'Permet d\'utiliser la technique Rempart — ce qu\'on vient chercher en donjon',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'rempart'],
                    ['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'wall'],
                ],
                'requirements' => ['soldier_rang3_2'],
            ],
            'soldier_skirmish_accord' => [
                'title' => 'Materia : Charge d\'acier',
                'slug' => 'soldier-skirmish-accord',
                'description' => 'Permet d\'utiliser la technique Charge d\'acier — de quoi finir un combat seul',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'crushing-weight'],
                    ['action' => 'specialization.branch', 'domain' => 'soldat', 'branch' => 'skirmish'],
                ],
                'requirements' => ['soldier_rang3_2'],
            ],

            // --- Capstone (100 pts) --------------------------------------------
            'soldier_capstone' => [
                'title' => 'Tenir la ligne',
                'slug' => 'soldier-capstone',
                'description' => 'Le coup d\'apres fait moins mal que celui d\'avant',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'guard', 'points' => 14, 'condition' => 'took_hit_last_turn']],
                'requirements' => ['soldier_rang3_2'],
            ],

            // Le nœud au cout du dormant : hors du total des 390 (§ 6.1).
            'soldier_rang5_1' => [
                'title' => 'Materia : Vierge de fer',
                'slug' => 'soldier-rang5-1',
                'description' => 'Permet d\'utiliser la technique Vierge de fer',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-maiden']],
                'requirements' => ['soldier_rang3_2'],
            ],

            // --- Les deux echelons de port ------------------------------------
            // Ils sont declares **dans** le corps de l'arbre — ce sont ceux de
            // l'epee, que `equipment_ports.yaml` rattache ensuite aux six arbres
            // qui l'enseignent (`rewireWeaponPortLadders()`). C'est ce qui fait
            // tomber le compte des 390 sans le nœud surnumeraire que le
            // Pyromancien a du garder : ses echelons a lui sont **generes** hors
            // du corps, parce qu'un arbre de sorts en herite de six.
            //
            // Ils ne portent **aucune statistique** : *un echelon est une porte,
            // jamais une recompense* (ecart 5 de GAME_TREE_ANATOMY).
            'soldier_weapon_t2' => [
                'title' => 'Maitrise de l\'epee (T2)',
                'slug' => 'soldier-weapon-t2',
                'description' => 'Permet d\'equiper les epees de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['soldier_apprenti_1'],
            ],
            'soldier_weapon_t3' => [
                'title' => 'Maitrise de l\'epee (T3)',
                'slug' => 'soldier-weapon-t3',
                'description' => 'Permet d\'equiper les epees de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['soldier_weapon_t2'],
            ],
        ];
    }

    private function getKnightSkills(): array
    {
        $d = 'knight';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'knight_apprenti_1' => [
                'title' => 'Materia : Provocation',
                'slug' => 'knight-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Provocation',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'provocation']],
            ],
            'knight_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'acier',
                'slug' => 'knight-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'acier',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'knight_rang2_1' => [
                'title' => 'Constitution de fer',
                'slug' => 'knight-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['knight_apprenti_1'],
            ],
            'knight_rang2_2' => [
                'title' => 'Materia : Riposte',
                'slug' => 'knight-rang2-2',
                'description' => 'Permet d\'utiliser la materia Riposte',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'riposte']],
                'requirements' => ['knight_apprenti_1'],
            ],
            'knight_rang2_3' => [
                'title' => 'Materia : Peau metallique',
                'slug' => 'knight-rang2-3',
                'description' => 'Permet d\'utiliser la materia Peau metallique',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metal-skin']],
                'requirements' => ['knight_apprenti_2'],
            ],
            'knight_rang2_4' => [
                'title' => 'Endurance du chevalier',
                'slug' => 'knight-rang2-4',
                'description' => 'Augmente la puissance des soins recus',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['knight_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'knight_rang3_1' => [
                'title' => 'Materia : Barriere de lames',
                'slug' => 'knight-rang3-1',
                'description' => 'Permet d\'utiliser la materia Barriere de lames (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blade-barrier']],
                'requirements' => ['knight_rang2_1', 'knight_rang2_2'],
            ],
            'knight_rang3_2' => [
                'title' => 'Materia : Regeneration metallique',
                'slug' => 'knight-rang3-2',
                'description' => 'Permet d\'utiliser la materia Regeneration metallique',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metallic-regeneration']],
                'requirements' => ['knight_rang2_3'],
            ],
            'knight_rang3_3' => [
                'title' => 'Armure epaisse',
                'slug' => 'knight-rang3-3',
                'description' => 'Augmente les points de vie et la precision',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 5,
                'hit' => 1,
                'requirements' => ['knight_rang2_4'],
            ],
            'knight_rang3_4' => [
                'title' => 'Materia : Chaine d\'eclairs',
                'slug' => 'knight-rang3-4',
                'description' => 'Permet d\'utiliser la materia Chaine d\'eclairs',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'chain-lightning']],
                'requirements' => ['knight_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'knight_rang4_1' => [
                'title' => 'Materia : Poids ecrasant',
                'slug' => 'knight-rang4-1',
                'description' => 'Permet d\'utiliser la materia Poids ecrasant — ecrasement brutal',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crushing-weight']],
                'requirements' => ['knight_rang3_1', 'knight_rang3_2'],
            ],
            'knight_rang4_2' => [
                'title' => 'Materia : Vierge de fer',
                'slug' => 'knight-rang4-2',
                'description' => 'Permet d\'utiliser la materia Vierge de fer',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-maiden']],
                'requirements' => ['knight_rang3_3', 'knight_rang3_4'],
            ],

            // Rang 5 (100-150 pts) — 2 skills
            'knight_t3_orichalcum' => [
                'title' => 'Materia : Lame d\'orichalque',
                'slug' => 'knight-t3-orichalcum',
                'description' => 'Permet d\'utiliser la materia Lame d\'orichalque — tranche les defenses',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'orichalcum-blade']],
                'requirements' => ['knight_rang4_1', 'knight_rang4_2'],
            ],
            'knight_rang5_1' => [
                'title' => 'Materia : Forteresse d\'acier',
                'slug' => 'knight-rang5-1',
                'description' => 'Permet d\'utiliser la materia Forteresse d\'acier',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-fortress']],
                'requirements' => ['knight_rang4_1', 'knight_rang4_2'],
            ],

            // Maitrise des armes (lances)
            'knight_weapon_t2' => [
                'title' => 'Maitrise de la lance (T2)',
                'slug' => 'knight-weapon-t2',
                'description' => 'Permet d\'equiper les lances de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['knight_apprenti_1'],
            ],
            'knight_weapon_t3' => [
                'title' => 'Maitrise de la lance (T3)',
                'slug' => 'knight-weapon-t3',
                'description' => 'Permet d\'equiper les lances de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['knight_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // INGENIEUR (metal) — 15 skills, support technique constructions
    // =========================================================================
    private function getEngineerSkills(): array
    {
        $d = 'engineer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'engi_apprenti_1' => [
                'title' => 'Materia : Attraction magnetique',
                'slug' => 'engi-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Attraction magnetique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'magnetic-pull']],
            ],
            'engi_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'acier',
                'slug' => 'engi-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'acier',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'engi_rang2_1' => [
                'title' => 'Precision mecanique',
                'slug' => 'engi-rang2-1',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['engi_apprenti_1'],
            ],
            'engi_rang2_2' => [
                'title' => 'Materia : Tourelle',
                'slug' => 'engi-rang2-2',
                'description' => 'Permet d\'utiliser la materia Tourelle',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'turret']],
                'requirements' => ['engi_apprenti_1'],
            ],
            'engi_rang2_3' => [
                'title' => 'Materia : Explosion d\'eclats',
                'slug' => 'engi-rang2-3',
                'description' => 'Permet d\'utiliser la materia Explosion d\'eclats',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shrapnel-burst']],
                'requirements' => ['engi_apprenti_2'],
            ],
            'engi_rang2_4' => [
                'title' => 'Blindage renforce',
                'slug' => 'engi-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['engi_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'engi_rang3_1' => [
                'title' => 'Materia : Automate reparateur',
                'slug' => 'engi-rang3-1',
                'description' => 'Permet d\'utiliser la materia Automate reparateur',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'repair-bot']],
                'requirements' => ['engi_rang2_1', 'engi_rang2_2'],
            ],
            'engi_rang3_2' => [
                'title' => 'Materia : Barriere de lames',
                'slug' => 'engi-rang3-2',
                'description' => 'Permet d\'utiliser la materia Barriere de lames (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blade-barrier']],
                'requirements' => ['engi_rang2_3'],
            ],
            'engi_rang3_3' => [
                'title' => 'Ameliorations mecaniques',
                'slug' => 'engi-rang3-3',
                'description' => 'Augmente les degats et les soins',
                'requiredPoints' => 25,
                'domain' => $d,
                'damage' => 1,
                'heal' => 1,
                'requirements' => ['engi_rang2_4'],
            ],
            'engi_rang3_4' => [
                'title' => 'Materia : Chaine d\'eclairs',
                'slug' => 'engi-rang3-4',
                'description' => 'Permet d\'utiliser la materia Chaine d\'eclairs',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'chain-lightning']],
                'requirements' => ['engi_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'engi_rang4_1' => [
                'title' => 'Materia : Tempete metallique',
                'slug' => 'engi-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete metallique',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metal-storm']],
                'requirements' => ['engi_rang3_1', 'engi_rang3_2'],
            ],
            'engi_rang4_2' => [
                'title' => 'Materia : Regeneration metallique',
                'slug' => 'engi-rang4-2',
                'description' => 'Permet d\'utiliser la materia Regeneration metallique',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metallic-regeneration']],
                'requirements' => ['engi_rang3_3', 'engi_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'engi_rang5_1' => [
                'title' => 'Materia : Engin de siege',
                'slug' => 'engi-rang5-1',
                'description' => 'Permet d\'utiliser la materia Engin de siege',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'siege-engine']],
                'requirements' => ['engi_rang4_1', 'engi_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // HYDROMANCIEN (eau) — 13 skills, mage offensif eau/glace
    // =========================================================================
    private function getHydromancerSkills(): array
    {
        $d = 'hydromancer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'hydro_apprenti_1' => [
                'title' => 'Materia : Jet d\'eau',
                'slug' => 'hydro-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Jet d\'eau',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-jet']],
            ],
            'hydro_apprenti_2' => [
                'title' => 'Materia : Toucher glace',
                'slug' => 'hydro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher glace',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frozen-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'hydro_rang2_1' => [
                'title' => 'Efficacite de l\'eau',
                'slug' => 'hydro-rang2-1',
                'description' => 'Augmente les degats des sorts d\'eau',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['hydro_apprenti_1'],
            ],
            'hydro_rang2_2' => [
                'title' => 'Points faibles',
                'slug' => 'hydro-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['hydro_apprenti_1'],
            ],
            'hydro_rang2_3' => [
                'title' => 'Materia : Trait de givre',
                'slug' => 'hydro-rang2-3',
                'description' => 'Permet d\'utiliser la materia Trait de givre (paralysie)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frost-bolt']],
                'requirements' => ['hydro_apprenti_2'],
            ],
            'hydro_rang2_4' => [
                'title' => 'Materia : Lance de glace',
                'slug' => 'hydro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Lance de glace',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-lance']],
                'requirements' => ['hydro_apprenti_2'],
            ],
            'hydro_materia_t2' => [
                'title' => 'Materia : Brume glaciale',
                'slug' => 'hydro-materia-t2',
                'description' => 'Permet d\'utiliser la materia Brume glaciale (paralysie)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frost-mist']],
                'requirements' => ['hydro_apprenti_1'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'hydro_rang3_1' => [
                'title' => 'Materia : Tempete de glace',
                'slug' => 'hydro-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tempete de glace (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-storm']],
                'requirements' => ['hydro_rang2_1', 'hydro_rang2_2'],
            ],
            'hydro_rang3_2' => [
                'title' => 'Materia : Prison d\'eau',
                'slug' => 'hydro-rang3-2',
                'description' => 'Permet d\'utiliser la materia Prison d\'eau (paralysie)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-prison']],
                'requirements' => ['hydro_rang2_3'],
            ],
            'hydro_rang3_3' => [
                'title' => 'Precision glaciale',
                'slug' => 'hydro-rang3-3',
                'description' => 'Augmente la precision des sorts d\'eau',
                'requiredPoints' => 25,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['hydro_rang2_4'],
            ],
            'hydro_rang3_4' => [
                'title' => 'Materia : Raz-de-maree',
                'slug' => 'hydro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Raz-de-maree (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tidal-wave']],
                'requirements' => ['hydro_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'hydro_rang4_1' => [
                'title' => 'Materia : Maelstrom',
                'slug' => 'hydro-rang4-1',
                'description' => 'Permet d\'utiliser la materia Maelstrom — tourbillon devastateur',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'maelstrom']],
                'requirements' => ['hydro_rang3_1', 'hydro_rang3_2'],
            ],
            'hydro_rang4_2' => [
                'title' => 'Materia : Bulle protectrice',
                'slug' => 'hydro-rang4-2',
                'description' => 'Permet d\'utiliser la materia Bulle protectrice',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bubble-shield']],
                'requirements' => ['hydro_rang3_3', 'hydro_rang3_4'],
            ],

            // Rang 5 (100-150 pts) — 2 skills
            'hydro_t3_maelstrom' => [
                'title' => 'Materia : Maelstrom glacial',
                'slug' => 'hydro-t3-maelstrom',
                'description' => 'Permet d\'utiliser la materia Maelstrom glacial — tourbillon qui gele',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frost-maelstrom']],
                'requirements' => ['hydro_rang4_1', 'hydro_rang4_2'],
            ],
            'hydro_rang5_1' => [
                'title' => 'Materia : Tsunami',
                'slug' => 'hydro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Tsunami',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tsunami']],
                'requirements' => ['hydro_rang4_1', 'hydro_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // GUERISSEUR (eau) — 18 skills, soigneur complet
    // =========================================================================
    /**
     * Le Guerisseur — eau x sorts x entretien, « le Ressac » (ARC-07b).
     *
     * GAME_ARCHETYPES § 9.2. Le second des quatre patrons, et **le premier
     * arbre du jeu qui depose** : le § 7 bis le dit en une phrase — *le
     * soigneur ne soigne pas, il provisionne*. Le combat de groupe etant
     * semi-synchrone (un joueur actif a la fois, le tour d'un absent resolu
     * tout seul), un soin **reactif** y est une mecanique morte : il n'est pas
     * la quand l'allie tombe. Ses gestes collectifs posent donc une **duree**
     * qui court que son lanceur soit connecte ou non.
     *
     * **Ce que l'arbre depense**, et les deux branches tombent sur 50 pb :
     *
     * | | Ressac | Maree |
     * |---|---:|---:|
     * | `mending` 3 + capstone 14 | 17 | 17 |
     * | `thrift` 3 (+9 Maree) | 3 | 12 |
     * | `wind` 6 | 6 | 6 |
     * | `ward` 6 (+9 Maree) | 6 | 15 |
     * | `recovery` / `guard` *(teinte)* | 9 + 9 | — |
     * | **Total** | **50** | **50** |
     *
     * Palette d'entretien : `mending` (principal) + `recovery`, `wind`,
     * `thrift`, `ward`. Le Ressac depense **41 pb dans sa palette** et 9 hors —
     * la teinte `guard`, l'eau qui amortit, et **elle ne vit que dans cette
     * branche** : le soigneur de donjon n'a pas de main gauche a donner a un
     * bouclier. Aucun plafond atteint (`mending` 17 <= 20, `ward` 15 <= 15,
     * `thrift` 12 <= 15, `recovery` 9 <= 12).
     *
     * **La Maree est au palier 2, donc les deux branches l'ont** : un
     * guerisseur sert son groupe quel que soit son choix, et la fourche decide
     * seulement *jusqu'ou*. C'est ce qui empeche l'archetype de se scinder en
     * « celui qui sert » et « celui qui ne sert pas ».
     *
     * **Deux ecarts au canon, nommes plutot que maquilles** :
     *
     *  1. Le § 9.2 donne a la branche Ressac l'accord **Dissipation** —
     *     *retirer un statut*. Aucune mecanique de dissipation n'existe dans le
     *     moteur, et en inventer une serait une decision de conception que ce
     *     jalon n'a pas a prendre. La branche ouvre donc une **protection**
     *     (`bubble-shield`), qui tient la meme place dans la palette et la meme
     *     promesse — *tenir seul ce qui tombe maintenant*. La dissipation
     *     revient avec ARC-18, qui ecrit les formes de geste.
     *  2. Le capstone se declenche *sous 40 % des PV* au canon ; le vocabulaire
     *     ferme de `SkillCondition` connait `target_below_quarter_life`. On
     *     prend celui-la : le § 0.2 range ce seuil parmi les nombres qu'ARC-17
     *     recalculera, et une condition inventee serait refusee a la lecture.
     */
    private function getHealerSkills(): array
    {
        $d = 'healer';

        return [
            // --- Entree (0 pt) ------------------------------------------------
            'healer_materia_1' => [
                'title' => 'Materia : Soin',
                'slug' => 'healer-materia-1',
                'description' => 'Permet d\'utiliser la materia Soin — le geste d\'urgence',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-heal']],
            ],
            // **Le second accord d'entree blesse, et c'est le cœur du jalon.**
            // Il portait un soin ; ARC-13b-a avait donc range le Guerisseur
            // parmi les quatre arbres incapables de poser leur marque, faute
            // d'un geste d'entree qui blesse (§ 1.1). Le Jet d'eau applique
            // **Trempe**, la marque de l'eau — donc la condition du capstone
            // devient atteignable au tour 2, avec un accord gratuit.
            //
            // Et il tient l'autre loi du § 5.1 : *sans un geste offensif, un
            // combat ne finit jamais*, et l'archetype est injouable seul.
            'healer_apprenti_2' => [
                'title' => 'Materia : Jet d\'eau',
                'slug' => 'healer-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Jet d\'eau — le geste offensif modeste, qui laisse la cible trempee',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-jet']],
            ],

            // --- Palier 1 (10 pts) --------------------------------------------
            'healer_heal_1' => [
                'title' => 'Main sure',
                'slug' => 'healer-heal-1',
                'description' => 'Ce qu\'on rend, on le rend mieux',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'mending', 'points' => 3]],
                'requirements' => ['healer_materia_1'],
            ],
            'healer_rang2_2' => [
                'title' => 'Geste econome',
                'slug' => 'healer-rang2-2',
                'description' => 'Le meme soin, pour moins de mana',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 3]],
                'requirements' => ['healer_materia_1'],
            ],
            // La **provision personnelle** : moins par tour qu'un soin direct,
            // mais elle court quand on ne joue pas. C'est le premier des deux
            // temps de l'archetype — *le direct est l'urgence, le depot est la
            // provision* (§ 7 bis.2 bis).
            'healer_rang2_3' => [
                'title' => 'Materia : Rosee',
                'slug' => 'healer-rang2-3',
                'description' => 'Permet d\'utiliser la materia Rosee — la provision qui court sans vous',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rejuvenation']],
                'requirements' => ['healer_apprenti_2'],
            ],
            'healer_rang2_4' => [
                'title' => 'Materia : Vague de soin',
                'slug' => 'healer-rang2-4',
                'description' => 'Permet d\'utiliser la materia Vague de soin',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-wave']],
                'requirements' => ['healer_apprenti_2'],
            ],

            // --- Palier 2 (25 pts) --------------------------------------------
            'healer_rang3_2' => [
                'title' => 'Seconde respiration',
                'slug' => 'healer-rang3-2',
                'description' => 'Le mana revient de lui-meme, tour apres tour',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'wind', 'points' => 6]],
                'requirements' => ['healer_heal_1'],
            ],
            'healer_rang3_3' => [
                'title' => 'Sang-froid',
                'slug' => 'healer-rang3-3',
                'description' => 'Ce qu\'on tente de vous imposer prend moins souvent',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'ward', 'points' => 6]],
                'requirements' => ['healer_rang2_2'],
            ],
            // **Le premier geste de portee `le groupe` du jeu.** Il est au
            // palier 2 et non dans une branche : les deux fourches l'ont, donc
            // un guerisseur sert son groupe quel que soit son choix. Sans lui,
            // la loi du depot (ARC-11b) n'aurait toujours aucun geste a
            // regir — elle etait ecrite, opposable, et sans objet.
            'healer_materia_2' => [
                'title' => 'Materia : Maree',
                'slug' => 'healer-materia-2',
                'description' => 'Permet d\'utiliser la materia Maree — une provision posee sur tout le groupe',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'maree']],
                'requirements' => ['healer_rang2_3'],
            ],
            'healer_rang3_4' => [
                'title' => 'Materia : Voile de brume',
                'slug' => 'healer-rang3-4',
                'description' => 'Permet d\'utiliser la materia Voile de brume',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mist-veil']],
                'requirements' => ['healer_rang2_4'],
            ],

            // --- Palier 3 (50 pts) : la fourche --------------------------------
            // *Le Ressac* tient **seul** — il se regenere et amortit, c'est le
            // soigneur qui n'a besoin de personne. *La Maree* tient **le
            // groupe** — elle depose plus souvent (`thrift`) et ne se laisse pas
            // interrompre (`ward`). La fourche oppose donc **deux contextes**,
            // pas deux dosages (§ 6.1 bis, regle 6).
            'healer_undertow_1' => [
                'title' => 'Sourdre',
                'slug' => 'healer-undertow-1',
                'description' => 'La vie revient d\'elle-meme, a chaque fin de tour',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'recovery', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'undertow']],
                'requirements' => ['healer_materia_2'],
            ],
            'healer_undertow_2' => [
                'title' => 'Ecume',
                'slug' => 'healer-undertow-2',
                'description' => 'L\'eau amortit ce qui vous frappe, pour qui garde une main libre',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'guard', 'points' => 9, 'condition' => 'shield']],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'undertow']],
                'requirements' => ['healer_materia_2'],
            ],
            'healer_tide_1' => [
                'title' => 'Litanie',
                'slug' => 'healer-tide-1',
                'description' => 'Deposer plus souvent, pour le meme mana',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'tide']],
                'requirements' => ['healer_materia_2'],
            ],
            'healer_tide_2' => [
                'title' => 'Eaux calmes',
                'slug' => 'healer-tide-2',
                'description' => 'On ne vous interrompt pas',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'ward', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'tide']],
                'requirements' => ['healer_materia_2'],
            ],
            // L'accord de chaque branche (regle 5). Cote Ressac, une
            // **protection** et non la Dissipation du canon : la dissipation
            // n'existe pas dans le moteur (voir l'en-tete).
            'healer_undertow_accord' => [
                'title' => 'Materia : Bulle protectrice',
                'slug' => 'healer-undertow-accord',
                'description' => 'Permet d\'utiliser la materia Bulle protectrice — tenir seul ce qui tombe maintenant',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'bubble-shield'],
                    ['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'undertow'],
                ],
                'requirements' => ['healer_materia_2'],
            ],
            'healer_tide_accord' => [
                'title' => 'Materia : Grande Maree',
                'slug' => 'healer-tide-accord',
                'description' => 'Permet d\'utiliser la materia Grande Maree — le depot qui couvre une rencontre entiere',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'grande-maree'],
                    ['action' => 'specialization.branch', 'domain' => 'guérisseur', 'branch' => 'tide'],
                ],
                'requirements' => ['healer_materia_2'],
            ],

            // --- Capstone (100 pts) --------------------------------------------
            // L'entretien est **la seule fonction dont le capstone garde x2,0**
            // (§ 7, decision 23) : sa condition peut reellement manquer — il
            // faut qu'un allie soit deja en danger.
            'healer_capstone' => [
                'title' => 'Ressac',
                'slug' => 'healer-capstone',
                'description' => 'Plus la cible est basse, plus la vague est haute',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'mending', 'points' => 14, 'condition' => 'target_below_quarter_life']],
                'requirements' => ['healer_materia_2'],
            ],

            // Le nœud au cout du dormant : hors du total des 390 (§ 6.1).
            'healer_rang5_1' => [
                'title' => 'Materia : Benediction celeste',
                'slug' => 'healer-rang5-1',
                'description' => 'Permet d\'utiliser la materia Benediction celeste',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'celestial-blessing']],
                'requirements' => ['healer_rang3_4'],
            ],
        ];
    }

    private function getTidecallerSkills(): array
    {
        $d = 'tidecaller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'tide_apprenti_1' => [
                'title' => 'Materia : Maree montante',
                'slug' => 'tide-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Maree montante',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rising-tide']],
            ],
            'tide_apprenti_2' => [
                'title' => 'Materia : Bouclier aquatique',
                'slug' => 'tide-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier aquatique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'aqua-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'tide_rang2_1' => [
                'title' => 'Force des marees',
                'slug' => 'tide-rang2-1',
                'description' => 'Augmente les degats des sorts d\'eau',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['tide_apprenti_1'],
            ],
            'tide_rang2_2' => [
                'title' => 'Materia : Torrent',
                'slug' => 'tide-rang2-2',
                'description' => 'Permet d\'utiliser la materia Torrent',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'torrent']],
                'requirements' => ['tide_apprenti_1'],
            ],
            'tide_rang2_3' => [
                'title' => 'Materia : Soin aquatique',
                'slug' => 'tide-rang2-3',
                'description' => 'Permet d\'utiliser la materia Soin aquatique',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-heal']],
                'requirements' => ['tide_apprenti_2'],
            ],
            'tide_rang2_4' => [
                'title' => 'Resilience marine',
                'slug' => 'tide-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['tide_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'tide_rang3_1' => [
                'title' => 'Materia : Raz-de-maree',
                'slug' => 'tide-rang3-1',
                'description' => 'Permet d\'utiliser la materia Raz-de-maree (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tidal-wave']],
                'requirements' => ['tide_rang2_1', 'tide_rang2_2'],
            ],
            'tide_rang3_2' => [
                'title' => 'Materia : Voile de brume',
                'slug' => 'tide-rang3-2',
                'description' => 'Permet d\'utiliser la materia Voile de brume (soin + bouclier)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mist-veil']],
                'requirements' => ['tide_rang2_3'],
            ],
            'tide_rang3_3' => [
                'title' => 'Guerison des eaux',
                'slug' => 'tide-rang3-3',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 25,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['tide_rang2_4'],
            ],
            'tide_rang3_4' => [
                'title' => 'Materia : Prison d\'eau',
                'slug' => 'tide-rang3-4',
                'description' => 'Permet d\'utiliser la materia Prison d\'eau (paralysie)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-prison']],
                'requirements' => ['tide_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'tide_rang4_1' => [
                'title' => 'Materia : Tempete de glace',
                'slug' => 'tide-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete de glace (AoE)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-storm']],
                'requirements' => ['tide_rang3_1', 'tide_rang3_2'],
            ],
            'tide_rang4_2' => [
                'title' => 'Materia : Benediction de l\'ocean',
                'slug' => 'tide-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction de l\'ocean (regeneration)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ocean-blessing']],
                'requirements' => ['tide_rang3_3', 'tide_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'tide_rang5_1' => [
                'title' => 'Materia : Maelstrom',
                'slug' => 'tide-rang5-1',
                'description' => 'Permet d\'utiliser la materia Maelstrom',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'maelstrom']],
                'requirements' => ['tide_rang4_1', 'tide_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // GEOMANCIEN (terre) — 15 skills, DPS magique terre, degats de zone
    // =========================================================================
    private function getGeomancerSkills(): array
    {
        $d = 'geomancer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'geo_apprenti_1' => [
                'title' => 'Materia : Jet de cailloux',
                'slug' => 'geo-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Jet de cailloux',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-throw']],
            ],
            'geo_apprenti_2' => [
                'title' => 'Materia : Sables mouvants',
                'slug' => 'geo-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Sables mouvants',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'quicksand']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'geo_rang2_1' => [
                'title' => 'Force tellurique',
                'slug' => 'geo-rang2-1',
                'description' => 'Augmente les degats des sorts de terre',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['geo_apprenti_1'],
            ],
            'geo_rang2_2' => [
                'title' => 'Fissures precises',
                'slug' => 'geo-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['geo_apprenti_1'],
            ],
            'geo_rang2_3' => [
                'title' => 'Materia : Pic de terre',
                'slug' => 'geo-rang2-3',
                'description' => 'Permet d\'utiliser la materia Pic de terre',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-spike']],
                'requirements' => ['geo_apprenti_2'],
            ],
            'geo_rang2_4' => [
                'title' => 'Materia : Pics de pierre',
                'slug' => 'geo-rang2-4',
                'description' => 'Permet d\'utiliser la materia Pics de pierre',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-spikes']],
                'requirements' => ['geo_apprenti_2'],
            ],
            'geo_materia_t2' => [
                'title' => 'Materia : Mur de pierre',
                'slug' => 'geo-materia-t2',
                'description' => 'Permet d\'utiliser la materia Mur de pierre (protection)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-shield']],
                'requirements' => ['geo_apprenti_1'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'geo_rang3_1' => [
                'title' => 'Materia : Tremblement de terre',
                'slug' => 'geo-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tremblement de terre (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earthquake']],
                'requirements' => ['geo_rang2_1', 'geo_rang2_2'],
            ],
            'geo_rang3_2' => [
                'title' => 'Materia : Glissement de terrain',
                'slug' => 'geo-rang3-2',
                'description' => 'Permet d\'utiliser la materia Glissement de terrain',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'landslide']],
                'requirements' => ['geo_rang2_3'],
            ],
            'geo_rang3_3' => [
                'title' => 'Precision minerale',
                'slug' => 'geo-rang3-3',
                'description' => 'Augmente la precision des sorts de terre',
                'requiredPoints' => 25,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['geo_rang2_4'],
            ],
            'geo_rang3_4' => [
                'title' => 'Materia : Lancer de rocher',
                'slug' => 'geo-rang3-4',
                'description' => 'Permet d\'utiliser la materia Lancer de rocher',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'boulder-throw']],
                'requirements' => ['geo_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'geo_rang4_1' => [
                'title' => 'Materia : Petrification',
                'slug' => 'geo-rang4-1',
                'description' => 'Permet d\'utiliser la materia Petrification — paralyse la cible',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'petrification']],
                'requirements' => ['geo_rang3_1', 'geo_rang3_2'],
            ],
            'geo_rang4_2' => [
                'title' => 'Materia : Lance de cristal',
                'slug' => 'geo-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lance de cristal',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-spear']],
                'requirements' => ['geo_rang3_3', 'geo_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'geo_rang5_1' => [
                'title' => 'Materia : Deplacement tectonique',
                'slug' => 'geo-rang5-1',
                'description' => 'Permet d\'utiliser la materia Deplacement tectonique',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tectonic-shift']],
                'requirements' => ['geo_rang4_1', 'geo_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // DEFENSEUR (terre) — 18 skills, tank absorption et murs
    // =========================================================================
    private function getDefenderSkills(): array
    {
        $d = 'defender';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'defender_apprenti_1' => [
                'title' => 'Materia : Parade',
                'slug' => 'defender-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Parade',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rock-armor']],
            ],
            'defender_apprenti_2' => [
                'title' => 'Materia : Bouclier terreux',
                'slug' => 'defender-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier terreux',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'defender_rang2_1' => [
                'title' => 'Constitution',
                'slug' => 'defender-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['defender_apprenti_1'],
            ],
            'defender_rang2_2' => [
                'title' => 'Riposte',
                'slug' => 'defender-rang2-2',
                'description' => 'Augmente les degats de contre-attaque',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['defender_apprenti_1'],
            ],
            'defender_rang2_3' => [
                'title' => 'Materia : Peau de pierre',
                'slug' => 'defender-rang2-3',
                'description' => 'Permet d\'utiliser la materia Peau de pierre — protection renforcee',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-skin']],
                'requirements' => ['defender_apprenti_2'],
            ],
            'defender_rang2_4' => [
                'title' => 'Materia : Pics de pierre',
                'slug' => 'defender-rang2-4',
                'description' => 'Permet d\'utiliser la materia Pics de pierre — riposte epineuse',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-spikes']],
                'requirements' => ['defender_apprenti_2'],
            ],

            'defender_rang2_5' => [
                'title' => 'Resistance naturelle',
                'slug' => 'defender-rang2-5',
                'description' => 'Renforce la constitution naturelle du defenseur',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 3,
                'hit' => 1,
                'requirements' => ['defender_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 5 skills
            'defender_rang3_1' => [
                'title' => 'Materia : Mur de fer',
                'slug' => 'defender-rang3-1',
                'description' => 'Permet d\'utiliser la materia Mur de fer — defense ultime',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-wall']],
                'requirements' => ['defender_rang2_1', 'defender_rang2_2'],
            ],
            'defender_rang3_2' => [
                'title' => 'Materia : Force de la montagne',
                'slug' => 'defender-rang3-2',
                'description' => 'Permet d\'utiliser la materia Force de la montagne (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mountain-strength']],
                'requirements' => ['defender_rang2_3'],
            ],
            'defender_rang3_3' => [
                'title' => 'Endurance de fer',
                'slug' => 'defender-rang3-3',
                'description' => 'Augmente les points de vie et la precision',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 5,
                'hit' => 1,
                'requirements' => ['defender_rang2_4'],
            ],
            'defender_rang3_4' => [
                'title' => 'Materia : Croissance cristalline',
                'slug' => 'defender-rang3-4',
                'description' => 'Permet d\'utiliser la materia Croissance cristalline (armure)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-growth']],
                'requirements' => ['defender_rang3_1'],
            ],

            'defender_t2_stonewall' => [
                'title' => 'Materia : Rempart de pierre',
                'slug' => 'defender-t2-stonewall',
                'description' => 'Permet d\'utiliser la materia Rempart de pierre — mur defensif puissant',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stonewall']],
                'requirements' => ['defender_rang2_3', 'defender_rang2_5'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'defender_rang4_1' => [
                'title' => 'Materia : Tremblement de terre',
                'slug' => 'defender-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tremblement de terre — repousse les ennemis',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earthquake']],
                'requirements' => ['defender_rang3_1', 'defender_rang3_2'],
            ],
            'defender_rang4_2' => [
                'title' => 'Materia : Lancer de rocher',
                'slug' => 'defender-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lancer de rocher — projectile lourd',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'boulder-throw']],
                'requirements' => ['defender_rang3_3', 'defender_rang3_4'],
            ],

            'defender_t2_fissure' => [
                'title' => 'Materia : Fissure',
                'slug' => 'defender-t2-fissure',
                'description' => 'Permet d\'utiliser la materia Fissure — faille devastatrice dans le sol',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fissure']],
                'requirements' => ['defender_rang3_1', 'defender_rang3_3'],
            ],

            // Rang 5 (100-150 pts) — 3 skills
            'defender_t3_quake' => [
                'title' => 'Materia : Seisme cristallin',
                'slug' => 'defender-t3-quake',
                'description' => 'Permet d\'utiliser la materia Seisme cristallin — onde de choc puissante',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-quake']],
                'requirements' => ['defender_rang4_1', 'defender_t2_fissure'],
            ],
            'defender_t3_obsidian' => [
                'title' => 'Materia : Lance d\'obsidienne',
                'slug' => 'defender-t3-obsidian',
                'description' => 'Permet d\'utiliser la materia Lance d\'obsidienne — projectile perforant',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'obsidian-lance']],
                'requirements' => ['defender_rang4_2', 'defender_t3_quake'],
            ],
            'defender_rang5_1' => [
                'title' => 'Materia : Petrification',
                'slug' => 'defender-rang5-1',
                'description' => 'Permet d\'utiliser la materia Petrification — defense absolue',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'petrification']],
                'requirements' => ['defender_t3_obsidian'],
            ],
        ];
    }

    // =========================================================================
    // GARDIEN (terre) — 15 skills, tank/support, protection de groupe
    // =========================================================================
    private function getGuardianSkills(): array
    {
        $d = 'guardian';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'guardian_apprenti_1' => [
                'title' => 'Materia : Bouclier partage',
                'slug' => 'guardian-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Bouclier partage',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shared-shield']],
            ],
            'guardian_apprenti_2' => [
                'title' => 'Materia : Parade',
                'slug' => 'guardian-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Parade',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rock-armor']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'guardian_rang2_1' => [
                'title' => 'Robustesse',
                'slug' => 'guardian-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['guardian_apprenti_1'],
            ],
            'guardian_rang2_2' => [
                'title' => 'Materia : Benediction de la terre',
                'slug' => 'guardian-rang2-2',
                'description' => 'Permet d\'utiliser la materia Benediction de la terre (soin)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-blessing']],
                'requirements' => ['guardian_apprenti_1'],
            ],
            'guardian_rang2_3' => [
                'title' => 'Materia : Bouclier terreux',
                'slug' => 'guardian-rang2-3',
                'description' => 'Permet d\'utiliser la materia Bouclier terreux',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-shield']],
                'requirements' => ['guardian_apprenti_2'],
            ],
            'guardian_rang2_4' => [
                'title' => 'Vigilance',
                'slug' => 'guardian-rang2-4',
                'description' => 'Augmente la precision des protections',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['guardian_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'guardian_rang3_1' => [
                'title' => 'Materia : Peau de pierre',
                'slug' => 'guardian-rang3-1',
                'description' => 'Permet d\'utiliser la materia Peau de pierre — armure renforcee',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-skin']],
                'requirements' => ['guardian_rang2_1', 'guardian_rang2_2'],
            ],
            'guardian_rang3_2' => [
                'title' => 'Materia : Force de la montagne',
                'slug' => 'guardian-rang3-2',
                'description' => 'Permet d\'utiliser la materia Force de la montagne',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mountain-strength']],
                'requirements' => ['guardian_rang2_3'],
            ],
            'guardian_rang3_3' => [
                'title' => 'Protection innebreanlable',
                'slug' => 'guardian-rang3-3',
                'description' => 'Augmente les points de vie et le soin',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 5,
                'heal' => 1,
                'requirements' => ['guardian_rang2_4'],
            ],
            'guardian_rang3_4' => [
                'title' => 'Materia : Mur de fer',
                'slug' => 'guardian-rang3-4',
                'description' => 'Permet d\'utiliser la materia Mur de fer (bouclier puissant)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-wall']],
                'requirements' => ['guardian_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'guardian_rang4_1' => [
                'title' => 'Materia : Croissance cristalline',
                'slug' => 'guardian-rang4-1',
                'description' => 'Permet d\'utiliser la materia Croissance cristalline — armure de cristal',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-growth']],
                'requirements' => ['guardian_rang3_1', 'guardian_rang3_2'],
            ],
            'guardian_rang4_2' => [
                'title' => 'Materia : Lance de cristal',
                'slug' => 'guardian-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lance de cristal — represailles',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-spear']],
                'requirements' => ['guardian_rang3_3', 'guardian_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'guardian_rang5_1' => [
                'title' => 'Materia : Bastion',
                'slug' => 'guardian-rang5-1',
                'description' => 'Permet d\'utiliser la materia Bastion — protection ultime du groupe',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bastion']],
                'requirements' => ['guardian_rang4_1', 'guardian_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // NECROMANCIEN (tenebres) — 18 nœuds au gabarit, le premier arbre de controle
    // =========================================================================
    /**
     * Le Necromancien — tenebres x sorts x controle, « la Veillee » (ARC-08a).
     *
     * GAME_TREE_ANATOMY § 12. **Le premier arbre de la fonction controle**, et
     * c'est ce qui le rend urgent : les quatre patrons d'ARC-07 couvrent
     * l'assaut deux fois, l'entretien et l'encaisse — le controle, qui compte
     * pourtant **sept arbres sur vingt-quatre**, n'en avait aucun. Le
     * simulateur d'ARC-17 doit generer *un build de reference par fonction* et
     * tenir le seuil « aucune fonction dominante dans les deux colonnes » : il
     * ne pouvait ni l'un ni l'autre tant que le controle n'existait qu'en
     * document.
     *
     * **Le test du voisin dans sa forme la plus dure** (§ 12.0) : il partage
     * l'element **et la marque** de l'Assassin, et absolument rien d'autre —
     * registre, fonction, ressource, levier principal, teinte, profil temporel.
     * L'Assassin **consomme** Aveugle, le Necromancien la **prolonge**.
     *
     * **Ce que l'arbre depense**, et il tombe sur ses 50 pb par branche :
     *
     * | | Linceul | Veillee |
     * |---|---:|---:|
     * | `grip` 6 + capstone 14 | 20 | 20 |
     * | `hit` 3 | 3 | 3 |
     * | `tempo` 3 (+9 Linceul) | 12 | 3 |
     * | `thrift` 6 (+9 Veillee) | 6 | 15 |
     * | `pierce` | 9 | — |
     * | `mending` *(teinte)* | — | 9 |
     * | **Total** | **50** | **50** |
     *
     * Palette de controle : `grip` (principal) + `hit`, `thrift`, `tempo`,
     * `pierce`. Le Linceul depense ses **50 pb dans sa palette** — le seul des
     * cinq arbres ecrits a n'avoir aucune teinte —, la Veillee 41 dedans et 9
     * hors, sur `mending` et lui seul. Trois plafonds sont **atteints pile** :
     * `grip` 20/20, `tempo` 12/12 (Linceul), `thrift` 15/15 (Veillee).
     *
     * **Le plafond a ecrit la fourche, comme chez le Soldat et l'Archer.**
     * `grip` est le levier principal du controle, donc le candidat naturel de
     * sa fourche — et il est impossible : le capstone en consomme 14, un nœud
     * de palier 3 en vaut 9, et le plafond est 20 (§ 7.1, corollaire 2, que cet
     * arbre a produit). *Le levier principal d'un arbre est presque absent de
     * sa propre fourche*, qui est donc faite de ses leviers secondaires.
     *
     * **La fourche oppose le solo au donjon** (§ 12.3) et non deux dosages : le
     * Linceul tient **l'ennemi** et le tient seul (`pierce`, `tempo`, une
     * entrave longue) ; la Veillee tient **la duree** et sert le groupe
     * (`mending`, `thrift`, une chose qui frappe apres son tour). Elles ne
     * partagent aucun levier, et la teinte `mending` ne vit que dans la
     * Veillee : *le necromancien qui joue seul ne se soigne pas, il empeche.*
     *
     * **Le capstone est atteignable au tour 1**, et mieux que chez les patrons :
     * le Voile de cendre **est** un accord d'entree, donc gratuit
     * (GAME_MATERIA § 3), et il ne fait que marquer. `target_marked` est donc
     * une condition **frequente** (x1,4) — l'ecart n° 11 tranche par le canon.
     *
     * **Ce que ce jalon ne fait pas, et c'est nomme** : le Serviteur d'ossements
     * est ecrit comme un geste qui laisse quelque chose derriere lui, pas comme
     * un **familier**. La forme n'existe pas — `DepositLaw` ne depose que la
     * portee `Group` et la protection —, et c'est ARC-18 qui l'ouvrira.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getNecromancerSkills(): array
    {
        $d = 'necromancer';

        return [
            // --- Entree (0 pt) : les deux accords du jour 1 ------------------
            // GAME_MATERIA § 3 : exactement deux accords gratuits par arbre.
            // L'un des deux **applique la marque** (§ 1.1). Ici c'est le Voile,
            // et il ne blesse pas — le premier du jeu dans ce cas. La loi
            // d'ARC-13a l'autorise par son second membre : Aveugle dure deux
            // tours, donc le tour n'a pas ete echange contre rien.
            'necro_apprenti_1' => [
                'title' => 'Materia : Voile de cendre',
                'slug' => 'necro-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Voile de cendre — la cendre qui aveugle',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ash-veil']],
            ],
            // Le plan B offensif, et la loi 1 du § 5.1 : un arbre qui n'ouvre
            // aucun geste de degat ne finit jamais un combat. Le Drain le fait
            // et rend des PV — la seule facon dont cet arbre survit avant sa
            // teinte.
            'necro_apprenti_2' => [
                'title' => 'Materia : Drain de vie',
                'slug' => 'necro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Drain de vie',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-drain']],
            ],

            // --- Palier 1 (10 pts) : 2 passifs a 3 pb + 2 accords ------------
            // Les passifs du palier 1 ne sont **jamais conditionnels** (§ 6.1) :
            // au jour 1 un joueur n'a pas de tenue a arbitrer, et un bonus qui
            // ne s'allume pas se lit comme un bug.
            'necro_rang2_1' => [
                'title' => 'Œil mort',
                'slug' => 'necro-rang2-1',
                'description' => 'Ce qu\'on vise dans le noir, on l\'atteint quand meme',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'hit', 'points' => 3]],
                'requirements' => ['necro_apprenti_1'],
            ],
            'necro_rang2_2' => [
                'title' => 'Souffle court',
                'slug' => 'necro-rang2-2',
                'description' => 'Prendre le tour avant celui qui respire encore',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 3]],
                'requirements' => ['necro_apprenti_2'],
            ],
            'necro_rang2_3' => [
                'title' => 'Materia : Malediction',
                'slug' => 'necro-rang2-3',
                'description' => 'Permet d\'utiliser la materia Malediction — ce qui ronge sans qu\'on y touche',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'plague-strike']],
                'requirements' => ['necro_apprenti_1'],
            ],
            'necro_rang2_4' => [
                'title' => 'Materia : Sangsue vitale',
                'slug' => 'necro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Sangsue vitale (drain)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-leech']],
                'requirements' => ['necro_apprenti_2'],
            ],

            // --- Palier 2 (25 pts) : 2 passifs a 6 pb + 2 accords ------------
            // `grip` entre ici et nulle part ailleurs avant le sommet : 6 + 14
            // sature son plafond de 20. C'est la contrainte qui a ecrit la
            // fourche de cet arbre (voir l'en-tete).
            'necro_rang3_1' => [
                'title' => 'Ce qui s\'accroche',
                'slug' => 'necro-rang3-1',
                'description' => 'Ce qu\'on pose sur une cible y reste plus longtemps',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'grip', 'points' => 6]],
                'requirements' => ['necro_rang2_1'],
            ],
            // Le premier passif **conditionnel** de l'arbre (§ 4.3) : c'est ce
            // qui fait de l'equipement un build plutot qu'un total. Le budget
            // compte l'effet **moyen** (6 pb), l'ecran affiche l'effet obtenu.
            'necro_rang3_2' => [
                'title' => 'Economie du geste',
                'slug' => 'necro-rang3-2',
                'description' => 'Rien de lourd sur les epaules : le geste coute moins a porter',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 6, 'condition' => 'armor:cloth']],
                'requirements' => ['necro_rang2_2'],
            ],
            // Le nœud charniere de l'arbre : toute la fourche et le capstone en
            // dependent, comme le Pyromancien fait dependre les siens de la
            // Pluie de flammes. Un seul parent au-dela (§ 6.6).
            'necro_rang3_3' => [
                'title' => 'Materia : Pulsation cauchemardesque',
                'slug' => 'necro-rang3-3',
                'description' => 'Permet d\'utiliser la materia Pulsation cauchemardesque — la terreur en zone',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nightmare-pulse']],
                'requirements' => ['necro_rang2_1', 'necro_rang2_2'],
            ],
            'necro_rang3_4' => [
                'title' => 'Materia : Eclair d\'ombre',
                'slug' => 'necro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Eclair d\'ombre',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-bolt']],
                'requirements' => ['necro_rang2_4'],
            ],

            // --- Palier 3 (50 pts) : la fourche ------------------------------
            // Deux branches de deux passifs **et d'un accord chacune**, dont on
            // n'apprend qu'une : l'arbre ecrit 60 pb, le personnage en porte
            // 50. Les prerequis ne traversent jamais la fourche (§ 6.6).
            //
            // *Le Linceul* tient l'**ennemi**, *la Veillee* tient la **duree**.
            // Elles ne partagent aucun levier — {`pierce`, `tempo`} contre
            // {`mending`, `thrift`} — et c'est la forme forte de la regle 6 :
            // une branche jouable seul, une branche qui sert le groupe.
            'necro_shroud_1' => [
                'title' => 'Rien ne passe',
                'slug' => 'necro-shroud-1',
                'description' => 'L\'ombre traverse ce qui pretend l\'arreter',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'pierce', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'shroud']],
                'requirements' => ['necro_rang3_3'],
            ],
            'necro_shroud_2' => [
                'title' => 'Devancer',
                'slug' => 'necro-shroud-2',
                'description' => 'Poser l\'entrave avant que l\'autre ne joue',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'shroud']],
                'requirements' => ['necro_rang3_3'],
            ],
            // La teinte de l'arbre, et elle ne vit que dans cette branche : le
            // seul levier hors palette des deux cotes, sur 9 des 10 pb que la
            // regle des 80/20 autorise.
            'necro_vigil_1' => [
                'title' => 'Ce qu\'il prend',
                'slug' => 'necro-vigil-1',
                'description' => 'La main gauche libre, ce qu\'on prend a l\'autre revient plus plein',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'mending', 'points' => 9, 'condition' => 'offhand_free']],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'vigil']],
                'requirements' => ['necro_rang3_3'],
            ],
            'necro_vigil_2' => [
                'title' => 'Longue patience',
                'slug' => 'necro-vigil-2',
                'description' => 'Tenir le combat plus longtemps que ses propres reserves',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'thrift', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'vigil']],
                'requirements' => ['necro_rang3_3'],
            ],
            // L'accord de chaque branche — la regle 5 de § 6.1 bis, et celle
            // qui decide si la fourche est un choix ou une decoration.
            'necro_shroud_accord' => [
                'title' => 'Materia : Linceul',
                'slug' => 'necro-shroud-accord',
                'description' => 'Permet d\'utiliser la materia Linceul — le geste etouffe avant de partir',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'shroud'],
                    ['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'shroud'],
                ],
                'requirements' => ['necro_rang3_3'],
            ],
            'necro_vigil_accord' => [
                'title' => 'Materia : Serviteur d\'ossements',
                'slug' => 'necro-vigil-accord',
                'description' => 'Permet d\'utiliser la materia Serviteur d\'ossements — ce qui a servi sert encore',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'bone-servant'],
                    ['action' => 'specialization.branch', 'domain' => 'nécromancien', 'branch' => 'vigil'],
                ],
                'requirements' => ['necro_rang3_3'],
            ],

            // --- Capstone (100 pts) ------------------------------------------
            // Un seul passif, **conditionnel**, 14 pb sur le levier principal.
            // Sa condition est atteignable des le tour 1 avec le seul kit
            // d'entree — le Voile de cendre est gratuit et ne fait que
            // marquer —, donc **frequente** : x1,4 et non x2,0 (§ 7,
            // decision 23).
            'necro_capstone' => [
                'title' => 'Ce qui ne lache pas',
                'slug' => 'necro-capstone',
                'description' => 'Ce qui tient deja tient plus longtemps',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'grip', 'points' => 14, 'condition' => 'target_marked']],
                'requirements' => ['necro_rang3_3'],
            ],

            // Le nœud au cout du dormant : hors du total des 390 (§ 6.1). Il
            // garde a l'arbre son accord de haut de gamme sans peser sur son
            // calendrier — le meme role que l'Eruption volcanique chez le
            // Pyromancien.
            'necro_rang5_1' => [
                'title' => 'Materia : Nova de mort',
                'slug' => 'necro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Nova de mort',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-nova']],
                'requirements' => ['necro_rang3_4'],
            ],
        ];
    }

    // =========================================================================
    // DRUIDE (bete) — 18 skills, healer/support nature
    // =========================================================================
    private function getDruidSkills(): array
    {
        $d = 'druid';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'druid_apprenti_1' => [
                'title' => 'Materia : Liane',
                'slug' => 'druid-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Liane',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'liana-whip']],
            ],
            'druid_apprenti_2' => [
                'title' => 'Materia : Guerison naturelle',
                'slug' => 'druid-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Guerison naturelle',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'natural-healing']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'druid_rang2_1' => [
                'title' => 'Symbiose naturelle',
                'slug' => 'druid-rang2-1',
                'description' => 'Augmente la puissance des soins de nature',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['druid_apprenti_1'],
            ],
            'druid_rang2_2' => [
                'title' => 'Affinite naturelle',
                'slug' => 'druid-rang2-2',
                'description' => 'Augmente la precision des sorts de nature',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['druid_apprenti_1'],
            ],
            'druid_rang2_3' => [
                'title' => 'Materia : Empoisonnement',
                'slug' => 'druid-rang2-3',
                'description' => 'Permet d\'utiliser la materia Empoisonnement (DoT)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'poison-cloud']],
                'requirements' => ['druid_apprenti_2'],
            ],
            'druid_rang2_4' => [
                'title' => 'Materia : Bouclier d\'epines',
                'slug' => 'druid-rang2-4',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'epines',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-shield']],
                'requirements' => ['druid_apprenti_2'],
            ],

            'druid_rang2_5' => [
                'title' => 'Instinct animal',
                'slug' => 'druid-rang2-5',
                'description' => 'Developpe les instincts primaux du druide',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['druid_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 5 skills
            'druid_rang3_1' => [
                'title' => 'Materia : Etreinte de la foret',
                'slug' => 'druid-rang3-1',
                'description' => 'Permet d\'utiliser la materia Etreinte de la foret (soin puissant)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'forest-embrace']],
                'requirements' => ['druid_rang2_1', 'druid_rang2_2'],
            ],
            'druid_rang3_2' => [
                'title' => 'Materia : Croissance sauvage',
                'slug' => 'druid-rang3-2',
                'description' => 'Permet d\'utiliser la materia Croissance sauvage (soin + degats)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wild-growth']],
                'requirements' => ['druid_rang2_3'],
            ],
            'druid_rang3_3' => [
                'title' => 'Communion vegetale',
                'slug' => 'druid-rang3-3',
                'description' => 'Augmente les soins et la vitalite',
                'requiredPoints' => 25,
                'domain' => $d,
                'heal' => 1,
                'life' => 3,
                'requirements' => ['druid_rang2_4'],
            ],
            'druid_rang3_4' => [
                'title' => 'Materia : Appel de la foret',
                'slug' => 'druid-rang3-4',
                'description' => 'Permet d\'utiliser la materia Appel de la foret (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-wrath']],
                'requirements' => ['druid_rang3_1'],
            ],

            'druid_t2_regen' => [
                'title' => 'Materia : Regeneration sauvage',
                'slug' => 'druid-t2-regen',
                'description' => 'Permet d\'utiliser la materia Regeneration sauvage — soin de nature tier 2',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wild-regeneration']],
                'requirements' => ['druid_rang2_3', 'druid_rang2_5'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'druid_rang4_1' => [
                'title' => 'Materia : Benediction de la nature',
                'slug' => 'druid-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction de la nature — soin surpuissant',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-blessing']],
                'requirements' => ['druid_rang3_1', 'druid_rang3_2'],
            ],
            'druid_rang4_2' => [
                'title' => 'Materia : Afflux primordial',
                'slug' => 'druid-rang4-2',
                'description' => 'Permet d\'utiliser la materia Afflux primordial (soin + degats)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'primal-surge']],
                'requirements' => ['druid_rang3_3', 'druid_rang3_4'],
            ],

            'druid_t2_claw' => [
                'title' => 'Materia : Griffes sauvages',
                'slug' => 'druid-t2-claw',
                'description' => 'Permet d\'utiliser la materia Griffes sauvages — attaque bestiale rapide',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'claw-swipe']],
                'requirements' => ['druid_rang3_1', 'druid_rang3_3'],
            ],

            // Rang 5 (100-150 pts) — 3 skills
            'druid_t3_stampede' => [
                'title' => 'Materia : Ruee sauvage',
                'slug' => 'druid-t3-stampede',
                'description' => 'Permet d\'utiliser la materia Ruee sauvage — charge bestiale en zone',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stampede']],
                'requirements' => ['druid_rang4_1', 'druid_t2_claw'],
            ],
            'druid_t3_apex' => [
                'title' => 'Materia : Predateur supreme',
                'slug' => 'druid-t3-apex',
                'description' => 'Permet d\'utiliser la materia Predateur supreme — instinct de tueur',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'apex-predator']],
                'requirements' => ['druid_rang4_2', 'druid_t3_stampede'],
            ],
            'druid_rang5_1' => [
                'title' => 'Materia : Fureur naturelle',
                'slug' => 'druid-rang5-1',
                'description' => 'Permet d\'utiliser la materia Fureur naturelle',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-fury']],
                'requirements' => ['druid_t3_apex'],
            ],
        ];
    }

    // =========================================================================
    // CHASSEUR (bete) — 13 skills, DPS distance pieges et pistage
    // =========================================================================
    private function getHunterSkills(): array
    {
        $d = 'hunter';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'hunter_apprenti_1' => [
                'title' => 'Materia : Appel du faucon',
                'slug' => 'hunter-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Appel du faucon',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'falcon-strike']],
            ],
            'hunter_apprenti_2' => [
                'title' => 'Materia : Morsure venimeuse',
                'slug' => 'hunter-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Morsure venimeuse',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'venomous-bite']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'hunter_rang2_1' => [
                'title' => 'Oeil de pisteur',
                'slug' => 'hunter-rang2-1',
                'description' => 'Augmente la precision des tirs',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['hunter_apprenti_1'],
            ],
            'hunter_rang2_2' => [
                'title' => 'Instinct de chasseur',
                'slug' => 'hunter-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['hunter_apprenti_1'],
            ],
            'hunter_rang2_3' => [
                'title' => 'Materia : Piege a ours',
                'slug' => 'hunter-rang2-3',
                'description' => 'Permet d\'utiliser la materia Piege a ours (paralysie)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bear-trap']],
                'requirements' => ['hunter_apprenti_2'],
            ],
            'hunter_rang2_4' => [
                'title' => 'Materia : Piege de vignes',
                'slug' => 'hunter-rang2-4',
                'description' => 'Permet d\'utiliser la materia Piege de vignes',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vine-snare']],
                'requirements' => ['hunter_apprenti_2'],
            ],
            'hunter_materia_t2' => [
                'title' => 'Materia : Morsure sauvage',
                'slug' => 'hunter-materia-t2',
                'description' => 'Permet d\'utiliser la materia Morsure sauvage',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'savage-bite']],
                'requirements' => ['hunter_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'hunter_rang3_1' => [
                'title' => 'Materia : Tir empoisonne',
                'slug' => 'hunter-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tir empoisonne (poison)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'poison-arrow']],
                'requirements' => ['hunter_rang2_1', 'hunter_rang2_2'],
            ],
            'hunter_rang3_2' => [
                'title' => 'Materia : Spores toxiques',
                'slug' => 'hunter-rang3-2',
                'description' => 'Permet d\'utiliser la materia Spores toxiques',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'toxic-spores']],
                'requirements' => ['hunter_rang2_3'],
            ],
            'hunter_rang3_3' => [
                'title' => 'Traque mortelle',
                'slug' => 'hunter-rang3-3',
                'description' => 'Augmente les degats et le critique',
                'requiredPoints' => 25,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['hunter_rang2_4'],
            ],
            'hunter_rang3_4' => [
                'title' => 'Materia : Lame feuille',
                'slug' => 'hunter-rang3-4',
                'description' => 'Permet d\'utiliser la materia Lame feuille',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'leaf-blade']],
                'requirements' => ['hunter_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'hunter_rang4_1' => [
                'title' => 'Materia : Explosion d\'epines',
                'slug' => 'hunter-rang4-1',
                'description' => 'Permet d\'utiliser la materia Explosion d\'epines',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-burst']],
                'requirements' => ['hunter_rang3_1', 'hunter_rang3_2'],
            ],
            'hunter_rang4_2' => [
                'title' => 'Materia : Fureur naturelle',
                'slug' => 'hunter-rang4-2',
                'description' => 'Permet d\'utiliser la materia Fureur naturelle',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-fury']],
                'requirements' => ['hunter_rang3_3', 'hunter_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'hunter_rang5_1' => [
                'title' => 'Materia : Chasse en meute',
                'slug' => 'hunter-rang5-1',
                'description' => 'Permet d\'utiliser la materia Chasse en meute (AoE)',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'pack-hunt']],
                'requirements' => ['hunter_rang4_1', 'hunter_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // DOMPTEUR (bete) — 13 skills, tank/invocateur familiers
    // =========================================================================
    private function getTamerSkills(): array
    {
        $d = 'tamer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'tamer_apprenti_1' => [
                'title' => 'Materia : Lien bestial',
                'slug' => 'tamer-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Lien bestial',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'beast-bond']],
            ],
            'tamer_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'epines',
                'slug' => 'tamer-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'epines',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'tamer_rang2_1' => [
                'title' => 'Lien renforce',
                'slug' => 'tamer-rang2-1',
                'description' => 'Augmente la puissance des soins du familier',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['tamer_apprenti_1'],
            ],
            'tamer_rang2_2' => [
                'title' => 'Instinct animal',
                'slug' => 'tamer-rang2-2',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['tamer_apprenti_1'],
            ],
            'tamer_rang2_3' => [
                'title' => 'Materia : Racines enchevetrees',
                'slug' => 'tamer-rang2-3',
                'description' => 'Permet d\'utiliser la materia Racines enchevetrees (paralysie)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'entangling-roots']],
                'requirements' => ['tamer_apprenti_2'],
            ],
            'tamer_rang2_4' => [
                'title' => 'Constitution bestiale',
                'slug' => 'tamer-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['tamer_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'tamer_rang3_1' => [
                'title' => 'Materia : Charge sauvage',
                'slug' => 'tamer-rang3-1',
                'description' => 'Permet d\'utiliser la materia Charge sauvage',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'savage-charge']],
                'requirements' => ['tamer_rang2_1', 'tamer_rang2_2'],
            ],
            'tamer_rang3_2' => [
                'title' => 'Materia : Croissance sauvage',
                'slug' => 'tamer-rang3-2',
                'description' => 'Permet d\'utiliser la materia Croissance sauvage (soin + degats)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wild-growth']],
                'requirements' => ['tamer_rang2_3'],
            ],
            'tamer_rang3_3' => [
                'title' => 'Carapace epaisse',
                'slug' => 'tamer-rang3-3',
                'description' => 'Augmente les points de vie et les soins',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 5,
                'heal' => 1,
                'requirements' => ['tamer_rang2_4'],
            ],
            'tamer_rang3_4' => [
                'title' => 'Materia : Liane',
                'slug' => 'tamer-rang3-4',
                'description' => 'Permet d\'utiliser la materia Liane',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'liana-whip']],
                'requirements' => ['tamer_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'tamer_rang4_1' => [
                'title' => 'Materia : Afflux primordial',
                'slug' => 'tamer-rang4-1',
                'description' => 'Permet d\'utiliser la materia Afflux primordial (soin + degats)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'primal-surge']],
                'requirements' => ['tamer_rang3_1', 'tamer_rang3_2'],
            ],
            'tamer_rang4_2' => [
                'title' => 'Materia : Benediction de la nature',
                'slug' => 'tamer-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction de la nature',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-blessing']],
                'requirements' => ['tamer_rang3_3', 'tamer_rang3_4'],
            ],

            // Rang 5 (100-150 pts) — 2 skills
            'tamer_t3_primal' => [
                'title' => 'Materia : Eveil primordial',
                'slug' => 'tamer-t3-primal',
                'description' => 'Permet d\'utiliser la materia Eveil primordial — poison et regain',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'primal-awakening']],
                'requirements' => ['tamer_rang4_1', 'tamer_rang4_2'],
            ],
            'tamer_rang5_1' => [
                'title' => 'Materia : Rugissement alpha',
                'slug' => 'tamer-rang5-1',
                'description' => 'Permet d\'utiliser la materia Rugissement alpha',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'alpha-roar']],
                'requirements' => ['tamer_rang4_1', 'tamer_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // FOUDROMANCIEN (air) — 13 skills, mage foudre/vent offensif
    // =========================================================================
    private function getStormcallerSkills(): array
    {
        $d = 'stormcaller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'storm_materia_1' => [
                'title' => 'Materia : Lame d\'air',
                'slug' => 'storm-materia-1',
                'description' => 'Permet d\'utiliser la materia Lame d\'air',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-lame']],
            ],
            'storm_apprenti_2' => [
                'title' => 'Materia : Bourrasque',
                'slug' => 'storm-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bourrasque',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'gust']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'storm_hit_1' => [
                'title' => 'Precision du vent',
                'slug' => 'storm-hit-1',
                'description' => 'Augmente la precision des sorts d\'air',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['storm_materia_1'],
            ],
            'storm_rang2_2' => [
                'title' => 'Efficacite de l\'air',
                'slug' => 'storm-rang2-2',
                'description' => 'Augmente les degats des sorts d\'air',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['storm_materia_1'],
            ],
            'storm_materia_2' => [
                'title' => 'Materia : Tornade',
                'slug' => 'storm-materia-2',
                'description' => 'Permet d\'utiliser la materia Tornade',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tornado']],
                'requirements' => ['storm_apprenti_2'],
            ],
            'storm_rang2_4' => [
                'title' => 'Materia : Souffle du vent',
                'slug' => 'storm-rang2-4',
                'description' => 'Permet d\'utiliser la materia Souffle du vent',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-blast']],
                'requirements' => ['storm_apprenti_2'],
            ],
            'storm_materia_t2' => [
                'title' => 'Materia : Éclair en chaîne',
                'slug' => 'storm-materia-t2',
                'description' => 'Permet d\'utiliser la materia Éclair en chaîne (AoE)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-chain-lightning']],
                'requirements' => ['storm_materia_1'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'storm_rang3_1' => [
                'title' => 'Materia : Cyclone',
                'slug' => 'storm-rang3-1',
                'description' => 'Permet d\'utiliser la materia Cyclone (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'cyclone']],
                'requirements' => ['storm_hit_1', 'storm_rang2_2'],
            ],
            'storm_rang3_2' => [
                'title' => 'Materia : Faux de vent',
                'slug' => 'storm-rang3-2',
                'description' => 'Permet d\'utiliser la materia Faux de vent',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-scythe']],
                'requirements' => ['storm_materia_2'],
            ],
            'storm_rang3_3' => [
                'title' => 'Oeil du cyclone',
                'slug' => 'storm-rang3-3',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 25,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['storm_rang2_4'],
            ],
            'storm_rang3_4' => [
                'title' => 'Materia : Mur de vent',
                'slug' => 'storm-rang3-4',
                'description' => 'Permet d\'utiliser la materia Mur de vent (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-wall']],
                'requirements' => ['storm_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'storm_rang4_1' => [
                'title' => 'Materia : Tempete',
                'slug' => 'storm-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete — devastation aerienne',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tempest']],
                'requirements' => ['storm_rang3_1', 'storm_rang3_2'],
            ],
            'storm_rang4_2' => [
                'title' => 'Materia : Lame de vide',
                'slug' => 'storm-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lame de vide',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vacuum-blade']],
                'requirements' => ['storm_rang3_3', 'storm_rang3_4'],
            ],

            // Rang 5 (100-150 pts) — 2 skills
            'storm_t3_thunder' => [
                'title' => 'Materia : Tempete de foudre',
                'slug' => 'storm-t3-thunder',
                'description' => 'Permet d\'utiliser la materia Tempete de foudre — eclairs aveugles',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thunder-storm']],
                'requirements' => ['storm_rang4_1', 'storm_rang4_2'],
            ],
            'storm_materia_3' => [
                'title' => 'Materia : Ouragan',
                'slug' => 'storm-materia-3',
                'description' => 'Permet d\'utiliser la materia Ouragan',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'hurricane']],
                'requirements' => ['storm_rang4_1', 'storm_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // ARCHER (air x distance x assaut) — « la Portee » (ARC-07d)
    // =========================================================================

    /**
     * L'Archer — air x distance x **assaut**, au gabarit (ARC-07d).
     *
     * GAME_ARCHETYPES § 9.4. Le quatrieme et dernier patron, et **le seul des
     * quatre dont la ressource est materielle** : il ne paie ni en PM ni en
     * tours, il paie en munitions. Sa promesse est *le meilleur rendement par
     * tour du jeu — si j'ai prepare le terrain*, et sa courbe est une **cadence
     * decroissante** la ou le Pyromancien a un pic et le Soldat un plateau.
     *
     * **Pourquoi il n'est pas un Pyromancien avec un arc**, alors qu'ils
     * partagent la fonction : sa ressource depend d'un artisan, son profil
     * decroit au lieu de s'effondrer, et sa teinte porte sur l'**economie** de
     * son geste (`wind`, la fleche recuperee) plutot que sur la marque qu'il
     * laisse. Trois differences structurelles, **aucune numerique** — c'est le
     * standard que le § 9.5 demande a tout couple d'arbres de meme fonction.
     *
     * **Un defaut trouve en ecrivant l'arbre, et anterieur au jalon** : trois
     * des accords de l'Archer — `air-current`, `wind-scythe`, `vacuum-blade` —
     * sont de registre **`Spell`**. Un arbre de distance dont les gestes sont
     * des sorts ne qualifie pas ses propres passifs (invariant 7 d'ARC-02b),
     * exactement le `magnetic-pull` que le Soldat a rendu a l'Ingenieur. Ils
     * partent, et **aucune materia ne perd son canal** : le Foudromancien ouvre
     * les deux premiers, le Vagabond le troisieme.
     *
     * **La fourche oppose deux rapports a la munition.** *Le Guet* prepare — un
     * gros coup, et le droit de le tirer en premier (`tempo`) ; *la Volee*
     * entretient la cadence — elle perce, et elle **coute moins**, ce qui pour
     * ce registre veut dire quelque chose de tres concret : moins de fleches
     * achetees. La teinte `wind` ne vit que dans la Volee.
     *
     * **Le capstone tombe pile au plafond**, et c'est la contrainte qui a ecrit
     * la fourche : `critical_power` plafonne a 15, le Guet en depense 6 en
     * commun plus 9 — il ne peut pas y avoir un troisieme nœud de critique dans
     * cet arbre, et c'est ce qui force la Volee a chercher ailleurs.
     */
    private function getArcherSkills(): array
    {
        $d = 'archer';

        return [
            // --- Entree (0 pt) : deux techniques ------------------------------
            'archer_apprenti_1' => [
                'title' => 'Materia : Tir tendu',
                'slug' => 'archer-apprenti-1',
                'description' => 'Permet d\'utiliser la technique Tir tendu — une munition ordinaire',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-dash']],
            ],
            // **Le plan B du jour 1, et la condition du capstone.** Il porte le
            // Desequilibre, donc le sommet de l'arbre est atteignable des le
            // tour 2 avec le seul kit d'entree — un accord d'entree coute
            // 0 point (GAME_MATERIA § 3), le joueur l'a le jour ou il ouvre
            // l'arbre. C'est aussi le seul accord non-`degat` de l'arbre
            // (§ 5.1, loi 2), et il garde ses 2 degats : le § 1.1 veut qu'une
            // marque soit portee par un geste qui blesse, faute de quoi elle
            // coute un tour plein pour un tour vole.
            'archer_apprenti_2' => [
                'title' => 'Materia : Tir entravant',
                'slug' => 'archer-apprenti-2',
                'description' => 'Permet d\'utiliser la technique Tir entravant — elle laisse un Desequilibre',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'precise-shot']],
            ],

            // --- Palier 1 (10 pts) --------------------------------------------
            'archer_rang2_1' => [
                'title' => 'Souffle court',
                'slug' => 'archer-rang2-1',
                'description' => 'On tire entre deux respirations, pas pendant',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 3]],
                'requirements' => ['archer_apprenti_1'],
            ],
            'archer_rang2_2' => [
                'title' => 'Bras d\'arc',
                'slug' => 'archer-rang2-2',
                'description' => 'Un arc plus dur a bander envoie plus loin',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 3]],
                'requirements' => ['archer_apprenti_1'],
            ],
            'archer_rang2_3' => [
                'title' => 'Materia : Volee',
                'slug' => 'archer-rang2-3',
                'description' => 'Permet d\'utiliser la technique Volee — deux munitions, plusieurs cibles',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'pressure-point']],
                'requirements' => ['archer_apprenti_2'],
            ],

            // --- Palier 2 (25 pts) --------------------------------------------
            // **La condition qui dit ce qu'on porte** : les deux mains a l'arc.
            // C'est le § 4.3 applique — un passif conditionnel recompense une
            // tenue reconnaissable, et on voit un archetype a ce qu'il porte
            // avant meme qu'il agisse.
            'archer_rang3_1' => [
                'title' => 'Lecture du vent',
                'slug' => 'archer-rang3-1',
                'description' => 'Les deux mains a l\'arc, on lit ou la fleche va deriver',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 6, 'condition' => 'offhand_free']],
                'requirements' => ['archer_rang2_1'],
            ],
            'archer_rang3_2' => [
                'title' => 'Encoche haute',
                'slug' => 'archer-rang3-2',
                'description' => 'Quand le coup porte, il porte plus haut',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 6]],
                'requirements' => ['archer_rang2_2'],
            ],
            'archer_rang3_3' => [
                'title' => 'Materia : Fleche de fracture',
                'slug' => 'archer-rang3-3',
                'description' => 'Permet d\'utiliser la technique Fleche de fracture — elle ouvre la garde',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-slash']],
                'requirements' => ['archer_rang2_3'],
            ],

            // --- Palier 3 (50 pts) : la fourche --------------------------------
            'archer_watch_1' => [
                'title' => 'Œil du faucon',
                'slug' => 'archer-watch-1',
                'description' => 'Le coup prepare ne fait pas plus souvent mal : il fait plus mal',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'watch']],
                'requirements' => ['archer_rang3_3'],
            ],
            'archer_watch_2' => [
                'title' => 'Avantage du guet',
                'slug' => 'archer-watch-2',
                'description' => 'Celui qui voit le premier tire le premier',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'watch']],
                'requirements' => ['archer_rang3_3'],
            ],
            'archer_volley_1' => [
                'title' => 'Pointe affutee',
                'slug' => 'archer-volley-1',
                'description' => 'Ce qui traverse l\'armure n\'a pas a la depasser',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'pierce', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'volley']],
                'requirements' => ['archer_rang3_3'],
            ],
            // **La teinte, et la seule du jeu qui porte sur la ressource.** Le
            // § 9 septies a retire aux munitions leur cout en gils — le carquois
            // est une piece durable qui se vide dans la rencontre et se ramasse
            // apres. `wind` ne rend donc pas de l'argent : il rend des tours de
            // tir dans le combat ou l'on est, ce qui est precisement ce que la
            // cadence decroissante coute.
            'archer_volley_2' => [
                'title' => 'Trait recupere',
                'slug' => 'archer-volley-2',
                'description' => 'Une fleche sur sept ressort intacte, et on la retire',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'wind', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'volley']],
                'requirements' => ['archer_rang3_3'],
            ],
            'archer_watch_accord' => [
                'title' => 'Materia : Tir du faucon',
                'slug' => 'archer-watch-accord',
                'description' => 'Permet d\'utiliser la technique Tir du faucon — le gros coup prepare',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'critical-shot'],
                    ['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'watch'],
                ],
                'requirements' => ['archer_rang3_3'],
            ],
            'archer_volley_accord' => [
                'title' => 'Materia : Grele',
                'slug' => 'archer-volley-accord',
                'description' => 'Permet d\'utiliser la technique Grele — plusieurs cibles, et la moitie des traits revient',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'arrow-rain'],
                    ['action' => 'specialization.branch', 'domain' => 'archer', 'branch' => 'volley'],
                ],
                'requirements' => ['archer_rang3_3'],
            ],

            // --- Capstone (100 pts) --------------------------------------------
            'archer_capstone' => [
                'title' => 'Trait dans le vent',
                'slug' => 'archer-capstone',
                'description' => 'Contre qui ne tient plus sa ligne, la fleche trouve seule',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 14, 'condition' => 'target_marked']],
                'requirements' => ['archer_rang3_3'],
            ],

            // Le nœud au cout du dormant : hors du total des 390 (§ 6.1).
            'archer_rang5_1' => [
                'title' => 'Materia : Fleche perforante',
                'slug' => 'archer-rang5-1',
                'description' => 'Permet d\'utiliser la technique Fleche perforante',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'piercing-arrow']],
                'requirements' => ['archer_rang3_3'],
            ],

            // --- Les deux echelons de port ------------------------------------
            // Ceux de l'arc, declares dans le corps de l'arbre et rattaches
            // ensuite aux arbres qui l'enseignent (`rewireWeaponPortLadders()`).
            // Ils ne portent **aucune statistique** : *un echelon est une porte,
            // jamais une recompense* (ecart 5 de GAME_TREE_ANATOMY).
            'archer_weapon_t2' => [
                'title' => 'Maitrise de l\'arc (T2)',
                'slug' => 'archer-weapon-t2',
                'description' => 'Permet d\'equiper les arcs de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['archer_apprenti_1'],
            ],
            'archer_weapon_t3' => [
                'title' => 'Maitrise de l\'arc (T3)',
                'slug' => 'archer-weapon-t3',
                'description' => 'Permet d\'equiper les arcs de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['archer_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // VAGABOND (air) — 13 skills, support vitesse et evasion
    // =========================================================================
    private function getWandererSkills(): array
    {
        $d = 'wanderer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'wander_apprenti_1' => [
                'title' => 'Materia : Hate',
                'slug' => 'wander-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Hate',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'haste']],
            ],
            'wander_apprenti_2' => [
                'title' => 'Materia : Bouclier de vent',
                'slug' => 'wander-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier de vent',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'wander_rang2_1' => [
                'title' => 'Agilite du vent',
                'slug' => 'wander-rang2-1',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['wander_apprenti_1'],
            ],
            'wander_rang2_2' => [
                'title' => 'Vitesse du vagabond',
                'slug' => 'wander-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['wander_apprenti_1'],
            ],
            'wander_rang2_3' => [
                'title' => 'Materia : Brise guerisseuse',
                'slug' => 'wander-rang2-3',
                'description' => 'Permet d\'utiliser la materia Brise guerisseuse',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-breeze']],
                'requirements' => ['wander_apprenti_2'],
            ],
            'wander_rang2_4' => [
                'title' => 'Endurance du voyageur',
                'slug' => 'wander-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['wander_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'wander_rang3_1' => [
                'title' => 'Materia : Mirage',
                'slug' => 'wander-rang3-1',
                'description' => 'Permet d\'utiliser la materia Mirage (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mirage']],
                'requirements' => ['wander_rang2_1', 'wander_rang2_2'],
            ],
            'wander_rang3_2' => [
                'title' => 'Materia : Courant d\'air',
                'slug' => 'wander-rang3-2',
                'description' => 'Permet d\'utiliser la materia Courant d\'air',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-current']],
                'requirements' => ['wander_rang2_3'],
            ],
            'wander_rang3_3' => [
                'title' => 'Souffle revitalisant',
                'slug' => 'wander-rang3-3',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 25,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['wander_rang2_4'],
            ],
            'wander_rang3_4' => [
                'title' => 'Materia : Benediction du vent',
                'slug' => 'wander-rang3-4',
                'description' => 'Permet d\'utiliser la materia Benediction du vent',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-blessing']],
                'requirements' => ['wander_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'wander_rang4_1' => [
                'title' => 'Materia : Mur de vent',
                'slug' => 'wander-rang4-1',
                'description' => 'Permet d\'utiliser la materia Mur de vent (degats + soin)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-wall']],
                'requirements' => ['wander_rang3_1', 'wander_rang3_2'],
            ],
            'wander_rang4_2' => [
                'title' => 'Materia : Tempete',
                'slug' => 'wander-rang4-2',
                'description' => 'Permet d\'utiliser la materia Tempete',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tempest']],
                'requirements' => ['wander_rang3_3', 'wander_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'wander_rang5_1' => [
                'title' => 'Materia : Zephyr',
                'slug' => 'wander-rang5-1',
                'description' => 'Permet d\'utiliser la materia Zephyr',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'zephyr']],
                'requirements' => ['wander_rang4_1', 'wander_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // PALADIN (lumiere) — 13 skills, guerrier sacre tank/healer
    // =========================================================================
    private function getPaladinSkills(): array
    {
        $d = 'paladin';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'paladin_apprenti_1' => [
                'title' => 'Materia : Frappe sacree',
                'slug' => 'paladin-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Frappe sacree',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-strike']],
            ],
            'paladin_apprenti_2' => [
                'title' => 'Materia : Aura de lumiere',
                'slug' => 'paladin-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Aura de lumiere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'light-aura']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'paladin_rang2_1' => [
                'title' => 'Bras du jugement',
                'slug' => 'paladin-rang2-1',
                'description' => 'Augmente les degats des attaques sacrees',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['paladin_apprenti_1'],
            ],
            'paladin_rang2_2' => [
                'title' => 'Constitution sacree',
                'slug' => 'paladin-rang2-2',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['paladin_apprenti_1'],
            ],
            'paladin_rang2_3' => [
                'title' => 'Materia : Lumiere',
                'slug' => 'paladin-rang2-3',
                'description' => 'Permet d\'utiliser la materia Lumiere (degats + soin)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-light']],
                'requirements' => ['paladin_apprenti_2'],
            ],
            'paladin_rang2_4' => [
                'title' => 'Materia : Toucher guerisseur',
                'slug' => 'paladin-rang2-4',
                'description' => 'Permet d\'utiliser la materia Toucher guerisseur',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-touch']],
                'requirements' => ['paladin_apprenti_2'],
            ],
            'paladin_materia_t2' => [
                'title' => 'Materia : Benediction',
                'slug' => 'paladin-materia-t2',
                'description' => 'Permet d\'utiliser la materia Benediction (soin)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'light-blessing']],
                'requirements' => ['paladin_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'paladin_rang3_1' => [
                'title' => 'Materia : Lumiere sacree',
                'slug' => 'paladin-rang3-1',
                'description' => 'Permet d\'utiliser la materia Lumiere sacree (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-light']],
                'requirements' => ['paladin_rang2_1', 'paladin_rang2_2'],
            ],
            'paladin_rang3_2' => [
                'title' => 'Materia : Bouclier de vie',
                'slug' => 'paladin-rang3-2',
                'description' => 'Permet d\'utiliser la materia Bouclier de vie',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-shield']],
                'requirements' => ['paladin_rang2_3'],
            ],
            'paladin_rang3_3' => [
                'title' => 'Precision divine',
                'slug' => 'paladin-rang3-3',
                'description' => 'Augmente la precision des attaques sacrees',
                'requiredPoints' => 25,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['paladin_rang2_4'],
            ],
            'paladin_rang3_4' => [
                'title' => 'Materia : Purification',
                'slug' => 'paladin-rang3-4',
                'description' => 'Permet d\'utiliser la materia Purification',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'purification']],
                'requirements' => ['paladin_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'paladin_rang4_1' => [
                'title' => 'Materia : Benediction divine',
                'slug' => 'paladin-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction divine — soin puissant',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-blessing']],
                'requirements' => ['paladin_rang3_1', 'paladin_rang3_2'],
            ],
            'paladin_rang4_2' => [
                'title' => 'Materia : Explosion de vie',
                'slug' => 'paladin-rang4-2',
                'description' => 'Permet d\'utiliser la materia Explosion de vie',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-burst']],
                'requirements' => ['paladin_rang3_3', 'paladin_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'paladin_rang5_1' => [
                'title' => 'Materia : Jugement divin',
                'slug' => 'paladin-rang5-1',
                'description' => 'Permet d\'utiliser la materia Jugement divin',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-judgment']],
                'requirements' => ['paladin_rang4_1', 'paladin_rang4_2'],
            ],

            // Maitrise des armes (batons)
            'paladin_weapon_t2' => [
                'title' => 'Maitrise du baton (T2)',
                'slug' => 'paladin-weapon-t2',
                'description' => 'Permet d\'equiper les batons de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['paladin_apprenti_1'],
            ],
            'paladin_weapon_t3' => [
                'title' => 'Maitrise du baton (T3)',
                'slug' => 'paladin-weapon-t3',
                'description' => 'Permet d\'equiper les batons de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['paladin_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // PRETRE (lumiere) — 18 skills, healer pur
    // =========================================================================
    private function getPriestSkills(): array
    {
        $d = 'priest';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'priest_apprenti_1' => [
                'title' => 'Materia : Priere',
                'slug' => 'priest-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Priere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'prayer']],
            ],
            'priest_apprenti_2' => [
                'title' => 'Materia : Toucher angelique',
                'slug' => 'priest-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher angelique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'angelic-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'priest_rang2_1' => [
                'title' => 'Grace divine',
                'slug' => 'priest-rang2-1',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['priest_apprenti_1'],
            ],
            'priest_rang2_2' => [
                'title' => 'Concentration sacree',
                'slug' => 'priest-rang2-2',
                'description' => 'Augmente la precision des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['priest_apprenti_1'],
            ],
            'priest_rang2_3' => [
                'title' => 'Materia : Vague de guerison',
                'slug' => 'priest-rang2-3',
                'description' => 'Permet d\'utiliser la materia Vague de guerison',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-wave']],
                'requirements' => ['priest_apprenti_2'],
            ],
            'priest_rang2_4' => [
                'title' => 'Materia : Floraison de vie',
                'slug' => 'priest-rang2-4',
                'description' => 'Permet d\'utiliser la materia Floraison de vie',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-bloom']],
                'requirements' => ['priest_apprenti_2'],
            ],

            'priest_rang2_5' => [
                'title' => 'Benediction passive',
                'slug' => 'priest-rang2-5',
                'description' => 'La grace divine renforce le pretre en permanence',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'life' => 2,
                'requirements' => ['priest_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 5 skills
            'priest_rang3_1' => [
                'title' => 'Materia : Regeneration',
                'slug' => 'priest-rang3-1',
                'description' => 'Permet d\'utiliser la materia Regeneration (HoT)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rejuvenation']],
                'requirements' => ['priest_rang2_1', 'priest_rang2_2'],
            ],
            'priest_rang3_2' => [
                'title' => 'Materia : Afflux de vitalite',
                'slug' => 'priest-rang3-2',
                'description' => 'Permet d\'utiliser la materia Afflux de vitalite',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vitality-surge']],
                'requirements' => ['priest_rang2_3'],
            ],
            'priest_rang3_3' => [
                'title' => 'Vitalite du pretre',
                'slug' => 'priest-rang3-3',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['priest_rang2_4'],
            ],
            'priest_rang3_4' => [
                'title' => 'Materia : Transfert de vie',
                'slug' => 'priest-rang3-4',
                'description' => 'Permet d\'utiliser la materia Transfert de vie',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-transfer']],
                'requirements' => ['priest_rang3_1'],
            ],

            'priest_t2_nova' => [
                'title' => 'Materia : Nova sacree',
                'slug' => 'priest-t2-nova',
                'description' => 'Permet d\'utiliser la materia Nova sacree — explosion de lumiere en zone',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-nova']],
                'requirements' => ['priest_rang2_3', 'priest_rang2_5'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'priest_rang4_1' => [
                'title' => 'Materia : Benediction celeste',
                'slug' => 'priest-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction celeste — soin ultime',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'celestial-blessing']],
                'requirements' => ['priest_rang3_1', 'priest_rang3_2'],
            ],
            'priest_rang4_2' => [
                'title' => 'Materia : Benediction divine',
                'slug' => 'priest-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction divine',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-blessing']],
                'requirements' => ['priest_rang3_3', 'priest_rang3_4'],
            ],

            'priest_t2_purge' => [
                'title' => 'Materia : Purge',
                'slug' => 'priest-t2-purge',
                'description' => 'Permet d\'utiliser la materia Purge — dissipe les effets negatifs',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'purge']],
                'requirements' => ['priest_rang3_1', 'priest_rang3_3'],
            ],

            // Rang 5 (100-150 pts) — 3 skills
            'priest_t3_grace' => [
                'title' => 'Materia : Grace divine',
                'slug' => 'priest-t3-grace',
                'description' => 'Permet d\'utiliser la materia Grace divine — soin et protection celeste',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-grace']],
                'requirements' => ['priest_rang4_1', 'priest_t2_purge'],
            ],
            'priest_t3_judgment' => [
                'title' => 'Materia : Jugement celeste',
                'slug' => 'priest-t3-judgment',
                'description' => 'Permet d\'utiliser la materia Jugement celeste — courroux divin',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'celestial-judgment']],
                'requirements' => ['priest_rang4_2', 'priest_t3_grace'],
            ],
            'priest_rang5_1' => [
                'title' => 'Materia : Miracle',
                'slug' => 'priest-rang5-1',
                'description' => 'Permet d\'utiliser la materia Miracle',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'miracle']],
                'requirements' => ['priest_t3_judgment'],
            ],
        ];
    }

    // =========================================================================
    // INQUISITEUR (lumiere) — 13 skills, DPS magique sacre
    // =========================================================================
    private function getInquisitorSkills(): array
    {
        $d = 'inquisitor';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'inquis_apprenti_1' => [
                'title' => 'Materia : Chatiment sacre',
                'slug' => 'inquis-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Chatiment sacre',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'smite']],
            ],
            'inquis_apprenti_2' => [
                'title' => 'Materia : Lumiere',
                'slug' => 'inquis-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Lumiere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-light']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'inquis_rang2_1' => [
                'title' => 'Colere divine',
                'slug' => 'inquis-rang2-1',
                'description' => 'Augmente les degats des sorts sacres',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['inquis_apprenti_1'],
            ],
            'inquis_rang2_2' => [
                'title' => 'Fanatisme',
                'slug' => 'inquis-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['inquis_apprenti_1'],
            ],
            'inquis_rang2_3' => [
                'title' => 'Materia : Lumiere sacree',
                'slug' => 'inquis-rang2-3',
                'description' => 'Permet d\'utiliser la materia Lumiere sacree',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-light']],
                'requirements' => ['inquis_apprenti_2'],
            ],
            'inquis_rang2_4' => [
                'title' => 'Materia : Purification',
                'slug' => 'inquis-rang2-4',
                'description' => 'Permet d\'utiliser la materia Purification',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'purification']],
                'requirements' => ['inquis_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'inquis_rang3_1' => [
                'title' => 'Materia : Feu sacre',
                'slug' => 'inquis-rang3-1',
                'description' => 'Permet d\'utiliser la materia Feu sacre',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-fire']],
                'requirements' => ['inquis_rang2_1', 'inquis_rang2_2'],
            ],
            'inquis_rang3_2' => [
                'title' => 'Materia : Explosion de vie',
                'slug' => 'inquis-rang3-2',
                'description' => 'Permet d\'utiliser la materia Explosion de vie',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-burst']],
                'requirements' => ['inquis_rang2_3'],
            ],
            'inquis_rang3_3' => [
                'title' => 'Oeil inquisiteur',
                'slug' => 'inquis-rang3-3',
                'description' => 'Augmente la precision des sorts sacres',
                'requiredPoints' => 25,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['inquis_rang2_4'],
            ],
            'inquis_rang3_4' => [
                'title' => 'Materia : Transfert de vie',
                'slug' => 'inquis-rang3-4',
                'description' => 'Permet d\'utiliser la materia Transfert de vie',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-transfer']],
                'requirements' => ['inquis_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'inquis_rang4_1' => [
                'title' => 'Materia : Jugement sacre',
                'slug' => 'inquis-rang4-1',
                'description' => 'Permet d\'utiliser la materia Jugement sacre — degats + soin',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-intervention']],
                'requirements' => ['inquis_rang3_1', 'inquis_rang3_2'],
            ],
            'inquis_rang4_2' => [
                'title' => 'Materia : Afflux de vitalite',
                'slug' => 'inquis-rang4-2',
                'description' => 'Permet d\'utiliser la materia Afflux de vitalite',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vitality-surge']],
                'requirements' => ['inquis_rang3_3', 'inquis_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'inquis_rang5_1' => [
                'title' => 'Materia : Sentence divine',
                'slug' => 'inquis-rang5-1',
                'description' => 'Permet d\'utiliser la materia Sentence divine',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-sentence']],
                'requirements' => ['inquis_rang4_1', 'inquis_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // ASSASSIN — tenebres x melee x assaut, « L'Ombre » / « La Lame » (ARC-08b)
    // =========================================================================
    /**
     * Le premier arbre d'**assaut en melee**, et la case que les quatre patrons
     * laissaient vide.
     *
     * ARC-07 a livre l'assaut deux fois — en sorts (Pyromancien) et au tir
     * (Archer) — et la melee **en encaisse** (Soldat). Personne ne frappait
     * fort au contact, si bien que le simulateur d'ARC-17 ne pouvait pas juger
     * la case ou vit la moitie du bestiaire.
     *
     * C'est aussi l'arbre que GAME_TREE_ANATOMY deroule comme **methode**
     * (§ 4) : ses dix-huit nœuds y sont ecrits un par un. L'ecrire revient donc
     * a verifier que le document tient en donnees — et il tient, sans dosage :
     * 390 points, 50 pb par branche, `critical_power` a 15/15 cote Lame.
     *
     * **La fourche n'oppose pas deux dosages du critique.** Elle oppose la
     * facon de **ne pas etre touche** a la facon de **trancher** : l'Ombre
     * evite en cuir et rejoue a deux lames, la Lame ignore l'armure et fait
     * payer le critique. Aucun levier commun — {`dodge`, `tempo`} contre
     * {`critical_power`, `pierce`}.
     *
     * **Ce que l'arbre perd en se resserrant.** Il portait onze accords ; le
     * gabarit en autorise sept. Cinq gestes partent, et deux d'entre eux
     * (`vital-drain`, `soul-siphon`) etaient des drains — un verbe d'entretien
     * dans un arbre d'assaut. Ce n'est pas une coupe de confort : *un arbre qui
     * ouvre tout n'ouvre rien*, et la palette d'assaut (§ 5.1) ne demande pas
     * qu'on sache aussi se soigner.
     */
    private function getAssassinSkills(): array
    {
        $d = 'assassin';

        return [
            // --- Entree (0 pt) : les deux accords du jour 1 ------------------
            // GAME_MATERIA § 3 : exactement deux accords gratuits. L'Embuscade
            // porte **Aveugle**, la marque des tenebres (ARC-13b-a) — et c'est
            // elle qui rend le capstone atteignable des la premiere rencontre,
            // puisqu'elle ne coute ni PM ni reprise.
            //
            // *GAME_TREE_ANATOMY § 4.7 attribuait la marque au Toucher
            // necrotique ; la donnee livree la porte sur l'Embuscade. Les deux
            // sont des accords d'entree gratuits, donc la loi est tenue de la
            // meme facon — lequel des deux la porte est une question d'auteur,
            // pas de regle, et on garde celui qui est deja teste.*
            'assassin_apprenti_1' => [
                'title' => 'Materia : Embuscade',
                'slug' => 'assassin-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Embuscade — celle qui aveugle avant de blesser',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ambush']],
            ],
            'assassin_apprenti_2' => [
                'title' => 'Materia : Toucher necrotique',
                'slug' => 'assassin-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher necrotique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'necrotic-touch']],
            ],

            // --- Palier 1 (10 pts) : 2 passifs a 3 pb + 1 accord + 1 port ----
            // Les passifs du palier 1 ne sont **jamais conditionnels** (§ 6.1) :
            // au jour 1 le joueur n'a pas de tenue a arbitrer.
            'assassin_rang2_1' => [
                'title' => 'Coup bas',
                'slug' => 'assassin-rang2-1',
                'description' => 'Ce qui ne se voit pas venir entre plus profond',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 3]],
                'requirements' => ['assassin_apprenti_1'],
            ],
            'assassin_rang2_2' => [
                'title' => 'Point vital',
                'slug' => 'assassin-rang2-2',
                'description' => 'Savoir ou ca fait mal, et y revenir',
                'requiredPoints' => 10,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 3]],
                'requirements' => ['assassin_apprenti_2'],
            ],
            'assassin_rang2_3' => [
                'title' => 'Materia : Voile',
                'slug' => 'assassin-rang2-3',
                'description' => 'Permet d\'utiliser la materia Voile — un pas dans l\'ombre plutot qu\'une parade',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-veil']],
                'requirements' => ['assassin_apprenti_1'],
            ],

            // --- Palier 2 (25 pts) : 2 passifs a 6 pb + 1 accord + 1 port ----
            'assassin_rang3_1' => [
                'title' => 'La ou ca saigne',
                'slug' => 'assassin-rang3-1',
                'description' => 'Un coup bien place ne fait pas un peu plus mal, il fait beaucoup plus mal',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 6]],
                'requirements' => ['assassin_rang2_1'],
            ],
            // Le premier passif **conditionnel** de l'arbre (§ 4.3) : c'est lui
            // qui fait de l'equipement un build plutot qu'un total. Le budget
            // compte l'effet **moyen**, l'ecran affiche l'effet obtenu.
            'assassin_rang3_2' => [
                'title' => 'Lame courte',
                'slug' => 'assassin-rang3-2',
                'description' => 'Ce qui tient dans la main se glisse ou rien d\'autre ne passe',
                'requiredPoints' => 25,
                'domain' => $d,
                'levers' => [['lever' => 'critical', 'points' => 6, 'condition' => 'weapon:dagger']],
                'requirements' => ['assassin_rang2_2'],
            ],
            // Le nœud charniere : la fourche et le capstone en dependent tous,
            // et un seul parent au-dela (§ 6.6).
            'assassin_rang3_3' => [
                'title' => 'Materia : Fauchee d\'ombre',
                'slug' => 'assassin-rang3-3',
                'description' => 'Permet d\'utiliser la materia Fauchee d\'ombre — la lame passe bas et large',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-reap']],
                'requirements' => ['assassin_rang2_1', 'assassin_rang2_2'],
            ],

            // --- Palier 3 (50 pts) : la fourche ------------------------------
            // Deux branches de deux passifs **et d'un accord chacune**, dont on
            // n'apprend qu'une : l'arbre ecrit 60 pb, le personnage en porte 50.
            // Les prerequis ne traversent jamais la fourche (§ 6.6).
            'assassin_shadow_1' => [
                'title' => 'Pas de cote',
                'slug' => 'assassin-shadow-1',
                'description' => 'Le cuir ne pare rien : il permet de ne pas etre la',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'dodge', 'points' => 9, 'condition' => 'armor:leather']],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'shadow']],
                'requirements' => ['assassin_rang3_3'],
            ],
            'assassin_shadow_2' => [
                'title' => 'Deux fois plutot qu\'une',
                'slug' => 'assassin-shadow-2',
                'description' => 'Deux lames ne frappent pas plus fort, elles frappent plus tot',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'tempo', 'points' => 9, 'condition' => 'dual_wield']],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'shadow']],
                'requirements' => ['assassin_rang3_3'],
            ],
            'assassin_blade_1' => [
                'title' => 'Entre les cotes',
                'slug' => 'assassin-blade-1',
                'description' => 'La ou l\'armure s\'arrete, la lame continue',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'critical_power', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'blade']],
                'requirements' => ['assassin_rang3_3'],
            ],
            'assassin_blade_2' => [
                'title' => 'Fil aiguise',
                'slug' => 'assassin-blade-2',
                'description' => 'Ce qui coupe assez bien n\'a pas a contourner',
                'requiredPoints' => 50,
                'domain' => $d,
                'levers' => [['lever' => 'pierce', 'points' => 9]],
                'actions' => [['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'blade']],
                'requirements' => ['assassin_rang3_3'],
            ],
            // L'accord de chaque branche — la regle 5 du § 6.1 bis, celle qui
            // decide si la fourche est un choix ou une decoration : sans lui,
            // deux branches produisent le meme combat au tour pres.
            'assassin_shadow_accord' => [
                'title' => 'Materia : Danse des ombres',
                'slug' => 'assassin-shadow-accord',
                'description' => 'Permet d\'utiliser la materia Danse des ombres — plusieurs cibles, et repartir',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'shadow-dance'],
                    ['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'shadow'],
                ],
                'requirements' => ['assassin_rang3_3'],
            ],
            'assassin_blade_accord' => [
                'title' => 'Materia : Coup mortel',
                'slug' => 'assassin-blade-accord',
                'description' => 'Permet d\'utiliser la materia Coup mortel — une seule ouverture suffit',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => [
                    'materia' => ['unlock' => 'deadly-strike'],
                    ['action' => 'specialization.branch', 'domain' => 'assassin', 'branch' => 'blade'],
                ],
                'requirements' => ['assassin_rang3_3'],
            ],

            // --- Capstone (100 pts) ------------------------------------------
            // Un seul passif, **conditionnel**, 14 pb sur le levier principal.
            // Sa condition — une cible qui porte la marque — est atteignable
            // des le tour 1 avec le seul kit d'entree, donc **frequente** :
            // x1,4 et non x2,0 (§ 7, decision 23).
            //
            // Et c'est ici que le § 7.1 se verifie une quatrieme fois : 14 pb de
            // `power` plus les 3 du palier 1 laissent 3 pb sous le plafond de
            // 20, donc **`power` ne pouvait pas etre le levier de la fourche**.
            // Le plafond a ecrit l'opposition ; on ne l'a pas dosee.
            'assassin_capstone' => [
                'title' => 'Ce qui ne voit pas venir',
                'slug' => 'assassin-capstone',
                'description' => 'Ce qui ne vous voit pas ne se protege pas',
                'requiredPoints' => 100,
                'domain' => $d,
                'levers' => [['lever' => 'power', 'points' => 14, 'condition' => 'target_marked']],
                'requirements' => ['assassin_rang3_3'],
            ],

            // --- Les echelons de port (0 pb) ---------------------------------
            // *Un echelon est une porte, jamais une recompense* : ils ne
            // portent aucune statistique depuis la correction de l'ecart 5, et
            // ils ne comptent pas dans les 390 points de l'arbre.
            'assassin_weapon_t2' => [
                'title' => 'Maitrise de la dague (T2)',
                'slug' => 'assassin-weapon-t2',
                'description' => 'Permet d\'equiper les dagues de tier 2',
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['assassin_apprenti_1'],
            ],
            'assassin_weapon_t3' => [
                'title' => 'Maitrise de la dague (T3)',
                'slug' => 'assassin-weapon-t3',
                'description' => 'Permet d\'equiper les dagues de tier 3',
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['assassin_weapon_t2'],
            ],
        ];
    }

    // =========================================================================
    // SORCIER (ombre) — 13 skills, maledictions et debuffs
    // =========================================================================
    private function getWarlockSkills(): array
    {
        $d = 'warlock';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'warlock_apprenti_1' => [
                'title' => 'Materia : Malefice',
                'slug' => 'warlock-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Malefice',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'hex']],
            ],
            'warlock_apprenti_2' => [
                'title' => 'Materia : Chatiment',
                'slug' => 'warlock-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Chatiment',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'punishment']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'warlock_rang2_1' => [
                'title' => 'Puissance maudite',
                'slug' => 'warlock-rang2-1',
                'description' => 'Augmente les degats des sorts sombres',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['warlock_apprenti_1'],
            ],
            'warlock_rang2_2' => [
                'title' => 'Regard mauvais',
                'slug' => 'warlock-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['warlock_apprenti_1'],
            ],
            'warlock_rang2_3' => [
                'title' => 'Materia : Emprise de la mort',
                'slug' => 'warlock-rang2-3',
                'description' => 'Permet d\'utiliser la materia Emprise de la mort',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-grip']],
                'requirements' => ['warlock_apprenti_2'],
            ],
            'warlock_rang2_4' => [
                'title' => 'Materia : Guerison des ombres',
                'slug' => 'warlock-rang2-4',
                'description' => 'Permet d\'utiliser la materia Guerison des ombres',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-mend']],
                'requirements' => ['warlock_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'warlock_rang3_1' => [
                'title' => 'Materia : Terreur',
                'slug' => 'warlock-rang3-1',
                'description' => 'Permet d\'utiliser la materia Terreur (paralysie)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'terror']],
                'requirements' => ['warlock_rang2_1', 'warlock_rang2_2'],
            ],
            'warlock_rang3_2' => [
                'title' => 'Materia : Rituel sombre',
                'slug' => 'warlock-rang3-2',
                'description' => 'Permet d\'utiliser la materia Rituel sombre (sacrifice + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-ritual']],
                'requirements' => ['warlock_rang2_3'],
            ],
            'warlock_rang3_3' => [
                'title' => 'Canalisation sombre',
                'slug' => 'warlock-rang3-3',
                'description' => 'Augmente la puissance des soins sombres',
                'requiredPoints' => 25,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['warlock_rang2_4'],
            ],
            'warlock_rang3_4' => [
                'title' => 'Materia : Vague d\'ombre',
                'slug' => 'warlock-rang3-4',
                'description' => 'Permet d\'utiliser la materia Vague d\'ombre (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-wave']],
                'requirements' => ['warlock_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'warlock_rang4_1' => [
                'title' => 'Materia : Spirale de mort',
                'slug' => 'warlock-rang4-1',
                'description' => 'Permet d\'utiliser la materia Spirale de mort',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-coil']],
                'requirements' => ['warlock_rang3_1', 'warlock_rang3_2'],
            ],
            'warlock_rang4_2' => [
                'title' => 'Materia : Siphon d\'ame',
                'slug' => 'warlock-rang4-2',
                'description' => 'Permet d\'utiliser la materia Siphon d\'ame (drain)',
                'requiredPoints' => 50,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-siphon']],
                'requirements' => ['warlock_rang3_3', 'warlock_rang3_4'],
            ],

            // Rang 5 (100-150 pts) — 2 skills
            'warlock_t3_covenant' => [
                'title' => 'Materia : Pacte des ombres',
                'slug' => 'warlock-t3-covenant',
                'description' => 'Permet d\'utiliser la materia Pacte des ombres — drain et poison de l\'ame',
                'requiredPoints' => 100,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-covenant']],
                'requirements' => ['warlock_rang4_1', 'warlock_rang4_2'],
            ],
            'warlock_rang5_1' => [
                'title' => 'Materia : Pacte sombre',
                'slug' => 'warlock-rang5-1',
                'description' => 'Permet d\'utiliser la materia Pacte sombre',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-pact']],
                'requirements' => ['warlock_rang4_1', 'warlock_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // MINEUR (terre/recolte) — extraction de minerais, 5 tiers de progression
    // T1 Cuivre/Étain/Fer → T2 Argent/Or/Cobalt → T3 Mithril/Platine/Sombracier
    // → T4 Adamantite/Astrétal/Orichalque → T5 Améthystite/Voidium
    // =========================================================================
    private function getMinerSkills(): array
    {
        $d = 'miner';

        return [
            // =================================================================
            // RANG 1 (0 pts) — T1 Commun : Cuivre, Étain, Fer
            // =================================================================
            'miner_copper_xs' => [
                'slug' => 'miner-copper-xs',
                'title' => 'Minage du cuivre',
                'description' => 'Permet de miner les filons de cuivre et debloque l\'emplacement de pioche',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-copper-xs', 'spot-copper-s']], ['action' => 'tool_slot.unlock', 'slot' => 'pickaxe'], ['action' => 'equip.tool', 'slugs' => ['pickaxe-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'miner_tin_xs' => [
                'slug' => 'miner-tin-xs',
                'title' => 'Minage de l\'etain',
                'description' => 'Permet de miner les filons d\'etain',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-tin-xs', 'spot-tin-s']]],
                'requiredPoints' => 0,
                'domain' => $d,
                'requirements' => ['miner_copper_xs'],
            ],
            'miner_iron_xs' => [
                'slug' => 'miner-iron-xs',
                'title' => 'Minage du fer debutant',
                'description' => 'Permet de miner les filons de fer basiques et d\'utiliser une pioche en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-xs', 'spot-iron-s']], ['action' => 'equip.tool', 'slugs' => ['pickaxe-iron']]],
                'requiredPoints' => 5,
                'domain' => $d,
                'requirements' => ['miner_copper_xs'],
            ],

            // =================================================================
            // RANG 2 (10-20 pts) — T2 Peu commun : Argent, Or, Cobalt
            // =================================================================
            'miner_efficiency_1' => [
                'slug' => 'miner-efficiency-1',
                'title' => 'Pioche affutee',
                'description' => 'Augmente la vitesse d\'extraction et permet d\'utiliser une pioche en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['pickaxe-steel']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['miner_iron_xs'],
            ],
            'miner_silver_xs' => [
                'slug' => 'miner-silver-xs',
                'title' => 'Minage de l\'argent',
                'description' => 'Permet de miner les filons d\'argent',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-silver-xs', 'spot-silver-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['miner_iron_xs'],
            ],
            'miner_gold_xs' => [
                'slug' => 'miner-gold-xs',
                'title' => 'Minage de l\'or debutant',
                'description' => 'Permet de miner les filons d\'or basiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-gold-xs', 'spot-gold-s']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'requirements' => ['miner_silver_xs'],
            ],
            'miner_cobalt_xs' => [
                'slug' => 'miner-cobalt-xs',
                'title' => 'Minage du cobalt',
                'description' => 'Permet de miner les filons de cobalt, un minerai d\'un bleu profond',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-cobalt-xs', 'spot-cobalt-s']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['miner_efficiency_1'],
            ],

            // =================================================================
            // RANG 3 (25-45 pts) — T3 Rare : Mithril, Platine, Sombracier
            // =================================================================
            'miner_yield_1' => [
                'slug' => 'miner-yield-1',
                'title' => 'Filon genereux',
                'description' => 'Chance de doubler les minerais extraits',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]],
                'requiredPoints' => 25,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['miner_gold_xs'],
            ],
            'miner_mithril_xs' => [
                'slug' => 'miner-mithril-xs',
                'title' => 'Minage du mithril',
                'description' => 'Permet de miner les filons de mithril legendaire',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mithril-xs', 'spot-mithril-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['miner_cobalt_xs', 'miner_yield_1'],
            ],
            'miner_platinum_xs' => [
                'slug' => 'miner-platinum-xs',
                'title' => 'Minage du platine',
                'description' => 'Permet de miner les filons de platine d\'une purete exceptionnelle',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-platinum-xs']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['miner_mithril_xs'],
            ],
            'miner_deep_vein' => [
                'slug' => 'miner-deep-vein',
                'title' => 'Veines profondes',
                'description' => 'Permet d\'utiliser une pioche en mithril et augmente les rendements',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['pickaxe-mithril']], ['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]],
                'requiredPoints' => 40,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['miner_mithril_xs'],
            ],
            'miner_darksteel_xs' => [
                'slug' => 'miner-darksteel-xs',
                'title' => 'Minage du sombracier',
                'description' => 'Permet de miner les filons de sombracier dans les profondeurs',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-darksteel-xs']]],
                'requiredPoints' => 45,
                'domain' => $d,
                'requirements' => ['miner_deep_vein'],
            ],

            // =================================================================
            // RANG 4 (55-80 pts) — T4 Épique : Orichalque
            //
            // OBJ-02b : les nœuds d'adamantite et d'astretal partent avec
            // leurs minerais a la reserve d'extension (EXTENSION_RESERVE.md) —
            // un nœud qui promet un filon inexistant est un mensonge
            // d'interface. L'Extension 1 les ramenera avec leurs filons.
            // =================================================================
            'miner_yield_2' => [
                'slug' => 'miner-yield-2',
                'title' => 'Filon prodigieux',
                'description' => 'Augmente encore les chances de doubler les minerais rares',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 15]],
                'requiredPoints' => 70,
                'domain' => $d,
                'critical' => 3,
                'hit' => 2,
                'requirements' => ['miner_darksteel_xs', 'miner_platinum_xs'],
            ],
            'miner_orichalcum_xs' => [
                'slug' => 'miner-orichalcum-xs',
                'title' => 'Minage de l\'orichalque',
                'description' => 'Permet de miner les filons d\'orichalque, le metal mythique des anciens',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-orichalcum-xs']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['miner_darksteel_xs', 'miner_platinum_xs'],
            ],

            // =================================================================
            // RANG 5 (100-150 pts) — T5 Légendaire : Améthystite, Voidium
            // =================================================================
            'miner_amethystite_xs' => [
                'slug' => 'miner-amethystite-xs',
                'title' => 'Minage de l\'amethystite',
                'description' => 'Permet de miner les cristaux d\'amethystite, la gemme signature d\'Amethyste',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-amethystite-xs']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['miner_orichalcum_xs', 'miner_yield_2'],
            ],
            // OBJ-02b : le voidium (Extension 2) n'existe plus dans la base —
            // le capstone garde ses bonus, il ne promet plus un filon absent.
            'miner_master' => [
                'slug' => 'miner-master',
                'title' => 'Maitre mineur',
                'description' => 'Maitrise absolue du minage — bonus ultimes de l\'art',
                'requiredPoints' => 150,
                'domain' => $d,
                'damage' => 2,
                'critical' => 2,
                'hit' => 1,
                'requirements' => ['miner_amethystite_xs'],
            ],
        ];
    }

    // =========================================================================
    // HERBORISTE (bete/recolte) — 15 skills, cueillette de plantes
    // =========================================================================
    private function getHerbalistSkills(): array
    {
        $d = 'herbalist';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'herbalist_dandelion' => [
                'slug' => 'herbalist-dandelion-xs',
                'title' => 'Recolte de pissenlit',
                'description' => 'Permet de recolter les pissenlits basiques et debloque l\'emplacement de faucille',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'sickle'], ['action' => 'equip.tool', 'slugs' => ['sickle-bronze']]],
                'domain' => $d,
            ],
            'herbalist_mint' => [
                'slug' => 'herbalist-mint-xs',
                'title' => 'Recolte de menthe',
                'description' => 'Permet de recolter la menthe basique et d\'utiliser une faucille en fer',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-xs']], ['action' => 'equip.tool', 'slugs' => ['sickle-iron']]],
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'herbalist_sage' => [
                'slug' => 'herbalist-sage-xs',
                'title' => 'Recolte de sauge',
                'description' => 'Permet de recolter la sauge',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_mint'],
            ],
            'herbalist_dandelion_s' => [
                'slug' => 'herbalist-dandelion-s',
                'title' => 'Recolte de pissenlit apprenti',
                'description' => 'Permet de recolter les pissenlits de qualite superieure',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_dandelion'],
            ],
            'herbalist_keen_eye' => [
                'slug' => 'herbalist-keen-eye',
                'title' => 'Oeil aiguise',
                'description' => 'Augmente les chances de trouver des plantes rares et permet d\'utiliser une faucille en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['sickle-steel']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['herbalist_dandelion'],
            ],
            'herbalist_chamomile' => [
                'slug' => 'herbalist-chamomile-xs',
                'title' => 'Recolte de camomille',
                'description' => 'Permet de recolter la camomille',
                'requiredPoints' => 20,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-chamomile-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_mint'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'herbalist_sage_s' => [
                'slug' => 'herbalist-sage-s',
                'title' => 'Recolte de sauge apprenti',
                'description' => 'Permet de recolter la sauge de qualite superieure',
                'requiredPoints' => 25,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_sage', 'herbalist_keen_eye'],
            ],
            'herbalist_lavender' => [
                'slug' => 'herbalist-lavender-xs',
                'title' => 'Recolte de lavande',
                'description' => 'Permet de recolter la lavande',
                'requiredPoints' => 30,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-lavender-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_chamomile'],
            ],
            'herbalist_gentle_hands' => [
                'slug' => 'herbalist-gentle-hands',
                'title' => 'Mains delicates',
                'description' => 'Ameliore la qualite des plantes recoltees',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['herbalist_dandelion_s'],
            ],
            'herbalist_mint_m' => [
                'slug' => 'herbalist-mint-m',
                'title' => 'Recolte de menthe avance',
                'description' => 'Permet de recolter la menthe rare',
                'requiredPoints' => 40,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-m']]],
                'domain' => $d,
                'requirements' => ['herbalist_sage_s'],
            ],
            'herbalist_chamomile_s' => [
                'slug' => 'herbalist-chamomile-s',
                'title' => 'Recolte de camomille apprenti',
                'description' => 'Permet de recolter la camomille de qualite superieure',
                'requiredPoints' => 50,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-chamomile-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_lavender'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'herbalist_rare_plants' => [
                'slug' => 'herbalist-rare-plants',
                'title' => 'Connaissance des plantes rares',
                'description' => 'Permet de recolter les plantes rares de toutes les regions',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-m', 'spot-chamomile-m', 'spot-lavender-m']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['herbalist_sage_s', 'herbalist_lavender'],
            ],
            'herbalist_bountiful' => [
                'slug' => 'herbalist-bountiful',
                'title' => 'Recolte abondante',
                'description' => 'Chance de doubler la quantite de plantes recoltees et permet d\'utiliser une faucille en mithril',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['sickle-mithril']], ['action' => 'yield', 'category' => 'gather_percent', 'percent' => 15]],
                'requiredPoints' => 80,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['herbalist_gentle_hands', 'herbalist_mint_m'],
            ],
            'herbalist_preservation' => [
                'slug' => 'herbalist-preservation',
                'title' => 'Conservation des plantes',
                'description' => 'Les plantes recoltees conservent mieux leurs proprietes',
                'requiredPoints' => 100,
                'domain' => $d,
                'heal' => 2,
                'requirements' => ['herbalist_chamomile_s'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'herbalist_master' => [
                'slug' => 'herbalist-master',
                'title' => 'Maitre herboriste',
                'description' => 'Maitrise absolue de l\'herboristerie — acces aux plantes legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xl', 'spot-mint-xl', 'spot-sage-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['herbalist_rare_plants', 'herbalist_bountiful'],
            ],
        ];
    }

    // =========================================================================
    // PECHEUR (eau/recolte) — 15 skills, peche en milieu aquatique
    // =========================================================================
    private function getFishermanSkills(): array
    {
        $d = 'fisherman';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'fisher_trout_xs' => [
                'slug' => 'fisher-trout-xs',
                'title' => 'Peche de la truite debutant',
                'description' => 'Permet de pecher la truite dans les eaux calmes et debloque l\'emplacement de canne a peche',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'fishing_rod'], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'fisher_carp_xs' => [
                'slug' => 'fisher-carp-xs',
                'title' => 'Peche de la carpe debutant',
                'description' => 'Permet de pecher la carpe dans les etangs et d\'utiliser une canne a peche en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-xs']], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-iron']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'fisher_trout_s' => [
                'slug' => 'fisher-trout-s',
                'title' => 'Peche de la truite apprenti',
                'description' => 'Permet de pecher la truite de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['fisher_trout_xs'],
            ],
            'fisher_carp_s' => [
                'slug' => 'fisher-carp-s',
                'title' => 'Peche de la carpe apprenti',
                'description' => 'Permet de pecher la carpe de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['fisher_carp_xs'],
            ],
            'fisher_patience' => [
                'slug' => 'fisher-patience',
                'title' => 'Patience du pecheur',
                'description' => 'Augmente les chances de capture des poissons et permet d\'utiliser une canne a peche en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['fishing-rod-steel']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['fisher_trout_xs'],
            ],
            'fisher_salmon_xs' => [
                'slug' => 'fisher-salmon-xs',
                'title' => 'Peche du saumon debutant',
                'description' => 'Permet de pecher le saumon dans les rivieres',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['fisher_carp_xs'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'fisher_trout_m' => [
                'slug' => 'fisher-trout-m',
                'title' => 'Peche de la truite avance',
                'description' => 'Permet de pecher la truite rare',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-m']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['fisher_trout_s', 'fisher_patience'],
            ],
            'fisher_carp_m' => [
                'slug' => 'fisher-carp-m',
                'title' => 'Peche de la carpe avance',
                'description' => 'Permet de pecher la carpe doree',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-m']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['fisher_carp_s'],
            ],
            'fisher_salmon_s' => [
                'slug' => 'fisher-salmon-s',
                'title' => 'Peche du saumon apprenti',
                'description' => 'Permet de pecher le saumon de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['fisher_salmon_xs'],
            ],
            'fisher_lucky_catch' => [
                'slug' => 'fisher-lucky-catch',
                'title' => 'Prise chanceuse',
                'description' => 'Chance de pecher un poisson supplementaire',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 12]],
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['fisher_trout_m'],
            ],
            'fisher_bait_mastery' => [
                'slug' => 'fisher-bait-mastery',
                'title' => 'Maitrise des appats',
                'description' => 'Les appats sont plus efficaces pour attirer les gros poissons',
                'requiredPoints' => 50,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['fisher_salmon_s'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'fisher_deep_sea' => [
                'slug' => 'fisher-deep-sea',
                'title' => 'Peche en eaux profondes',
                'description' => 'Permet de pecher dans les eaux profondes et les lacs et d\'utiliser une canne a peche en mithril',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-l', 'spot-carp-l']], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-mithril']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['fisher_trout_m', 'fisher_carp_m'],
            ],
            'fisher_salmon_m' => [
                'slug' => 'fisher-salmon-m',
                'title' => 'Peche du saumon avance',
                'description' => 'Permet de pecher le saumon royal',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-m']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['fisher_salmon_s', 'fisher_lucky_catch'],
            ],
            'fisher_ocean' => [
                'slug' => 'fisher-ocean',
                'title' => 'Peche en haute mer',
                'description' => 'Permet de pecher les poissons des eaux oceaniques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-l']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['fisher_bait_mastery'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            // ZON-35 — les deux prises de palier 4. Le poisson-lune et le kraken
            // juvenile etaient **cuisines sans etre pechables** : deux recettes
            // livrees par ECO-29 dont la matiere n'existait nulle part. Ces deux
            // nœuds sont les portes de leurs filons (ECO-24c), et ils comblent le
            // trou de palier haut de la peche (loi 9, GAME_ZONES § 3 ter).
            'fisher_moonfish' => [
                'slug' => 'fisher-moonfish',
                'title' => 'Peche du poisson-lune',
                'description' => 'Permet de lever le poisson-lune des tourbieres du Marais, la prise qu\'on ne fait pas en passant',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-moonfish-xs']]],
                'requiredPoints' => 90,
                'domain' => $d,
                'requirements' => ['fisher_deep_sea'],
            ],
            'fisher_master' => [
                'slug' => 'fisher-master',
                'title' => 'Maitre pecheur',
                'description' => 'Maitrise absolue de la peche — acces aux poissons legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-xl', 'spot-carp-xl', 'spot-salmon-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['fisher_deep_sea', 'fisher_salmon_m'],
            ],
            'fisher_kraken' => [
                'slug' => 'fisher-kraken',
                'title' => 'Peche du kraken juvenile',
                'description' => 'Permet de tirer un kraken juvenile des bancs de la Mer de Sel, la seule eau profonde du monde',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-kraken-xs']]],
                'requiredPoints' => 200,
                'domain' => $d,
                'requirements' => ['fisher_master', 'fisher_moonfish'],
            ],
        ];
    }

    // =========================================================================
    // DEPECEUR (bete/recolte) — 15 skills, depecage de creatures
    // =========================================================================
    private function getSkinnerSkills(): array
    {
        $d = 'skinner';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'skinner_hide_xs' => [
                'slug' => 'skinner-hide-xs',
                'title' => 'Depecage de cuir brut',
                'description' => 'Permet de depecer les creatures basiques pour obtenir du cuir brut et debloque l\'emplacement de couteau de depecage',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'skinning_knife'], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'skinner_bone_xs' => [
                'slug' => 'skinner-bone-xs',
                'title' => 'Collecte d\'os',
                'description' => 'Permet de recuperer les os des creatures vaincues et d\'utiliser un couteau en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-xs']], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-iron']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'skinner_hide_s' => [
                'slug' => 'skinner-hide-s',
                'title' => 'Depecage de cuir apprenti',
                'description' => 'Permet de depecer les creatures pour obtenir du cuir fin',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['skinner_hide_xs'],
            ],
            'skinner_bone_s' => [
                'slug' => 'skinner-bone-s',
                'title' => 'Collecte d\'os apprenti',
                'description' => 'Permet de recuperer des os de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['skinner_bone_xs'],
            ],
            'skinner_precision' => [
                'slug' => 'skinner-precision',
                'title' => 'Lame precise',
                'description' => 'Augmente la precision du depecage et permet d\'utiliser un couteau en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['skinning-knife-steel']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['skinner_hide_xs'],
            ],
            'skinner_fang_xs' => [
                'slug' => 'skinner-fang-xs',
                'title' => 'Extraction de crocs',
                'description' => 'Permet de recuperer les crocs et griffes des creatures',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['skinner_bone_xs'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'skinner_hide_m' => [
                'slug' => 'skinner-hide-m',
                'title' => 'Depecage de cuir avance',
                'description' => 'Permet de depecer les creatures puissantes pour obtenir du cuir epais',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-m']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['skinner_hide_s', 'skinner_precision'],
            ],
            'skinner_bone_m' => [
                'slug' => 'skinner-bone-m',
                'title' => 'Collecte d\'os avance',
                'description' => 'Permet de recuperer des os rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-m']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['skinner_bone_s'],
            ],
            'skinner_fang_s' => [
                'slug' => 'skinner-fang-s',
                'title' => 'Extraction de crocs apprenti',
                'description' => 'Permet de recuperer des crocs de creatures puissantes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['skinner_fang_xs'],
            ],
            'skinner_yield' => [
                'slug' => 'skinner-yield',
                'title' => 'Depecage minutieux',
                'description' => 'Chance de recuperer des materiaux supplementaires',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 12]],
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['skinner_hide_m'],
            ],
            'skinner_scale_xs' => [
                'slug' => 'skinner-scale-xs',
                'title' => 'Extraction d\'ecailles',
                'description' => 'Permet de recuperer les ecailles des creatures reptiliennes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-scale-xs']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['skinner_fang_s'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'skinner_exotic' => [
                'slug' => 'skinner-exotic',
                'title' => 'Depecage de creatures exotiques',
                'description' => 'Permet de depecer les creatures rares et exotiques et d\'utiliser un couteau en mithril',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-l', 'spot-bone-l']], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-mithril']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['skinner_hide_m', 'skinner_bone_m'],
            ],
            'skinner_fang_m' => [
                'slug' => 'skinner-fang-m',
                'title' => 'Extraction de crocs avance',
                'description' => 'Permet de recuperer des crocs et griffes rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-m']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['skinner_fang_s', 'skinner_yield'],
            ],
            'skinner_scale_s' => [
                'slug' => 'skinner-scale-s',
                'title' => 'Extraction d\'ecailles avance',
                'description' => 'Permet de recuperer des ecailles de creatures puissantes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-scale-s']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['skinner_scale_xs'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'skinner_master' => [
                'slug' => 'skinner-master',
                'title' => 'Maitre depeceur',
                'description' => 'Maitrise absolue du depecage — acces aux materiaux legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-xl', 'spot-bone-xl', 'spot-fang-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['skinner_exotic', 'skinner_fang_m'],
            ],
        ];
    }

    // =========================================================================
    // CUISINIER (eau/craft) — le debouche de la peche et des vivres (ECO-29)
    // =========================================================================
    // Avant ce jalon, **aucun poisson du monde n'etait consomme par quoi que ce
    // soit** : six filons de peche, un arbre entier de competences, et rien au
    // bout. L'arbre du cuisinier ouvre les sept plats qui ferment ce trou.
    //
    // OBJ-06 : la marmite existe — le type d'outil, le bit d'equipement et
    // l'emplacement d'interface qu'ECO-29 avait differes sont livres, et le
    // nœud gratuit du four porte l'emplacement et le palier d'entree.
    private function getCookSkills(): array
    {
        $d = 'cook';

        return [
            // Rang 1 (0 pts) — le four et le feu
            'cook_bread' => [
                'slug' => 'cook-bread',
                'title' => 'Panification',
                'description' => 'Permet de cuire le pain de campagne a partir du ble',
                // OBJ-06 : la marmite arrive avec le premier feu — emplacement
                // ouvert et palier d'entree livres par le nœud gratuit, comme
                // dans les quatre arbres d'artisanat historiques.
                // OBJ-07 : la fricassee rejoint le premier feu — le champignon
                // de butin a un debouche des le nœud gratuit.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-bread', 'recipe-mushroom-fricassee']], ['action' => 'tool_slot.unlock', 'slot' => 'cookpot'], ['action' => 'equip.tool', 'slugs' => ['cookpot-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'cook_skewer' => [
                'slug' => 'cook-skewer',
                'title' => 'Cuisson au feu',
                'description' => 'Permet de preparer la brochette du gue : perche et truite sur la meme branche',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-fish-skewer']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-25 pts) — la marmite
            'cook_stew' => [
                'slug' => 'cook-stew',
                'title' => 'Mijotage',
                'description' => 'Permet de mijoter la carpe des etangs avec le gibier de plaine',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-carp-stew']], ['action' => 'equip.tool', 'slugs' => ['cookpot-iron']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['cook_skewer'],
            ],
            'cook_palate' => [
                'slug' => 'cook-palate',
                'title' => 'Palais du cuisinier',
                'description' => 'Gouter avant de servir : les plats sortent meilleurs',
                'requiredPoints' => 25,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['cook_bread'],
            ],
            // ZON-35 — les epices, reportees par ECO-29 faute de ce jalon. Quatre
            // herbes banales se recoltaient sans qu'une seule recette ne les
            // consomme ; le melange les absorbe toutes les quatre.
            'cook_spices' => [
                'slug' => 'cook-spices',
                'title' => 'Melange d\'epices',
                'description' => 'Permet de piler le pissenlit, l\'ortie, le romarin et l\'echinacee en un melange qui releve les plats de haut palier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-spice-blend']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['cook_bread'],
            ],

            // Rang 3 (35-60 pts) — les tables qui se paient
            'cook_roast' => [
                'slug' => 'cook-roast',
                'title' => 'Rotissage',
                'description' => 'Permet de rotir le saumon des rapides sur son lit de pain',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-salmon-roast']], ['action' => 'equip.tool', 'slugs' => ['cookpot-steel']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['cook_stew'],
            ],
            'cook_moonfish' => [
                'slug' => 'cook-moonfish',
                'title' => 'Ecaillage fin',
                'description' => 'Permet d\'apprêter le poisson-lune sans abimer ses ecailles',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-moonfish-plate']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['cook_roast'],
            ],

            // Rang 4 (90-150 pts) — ce qui mord encore dans l'assiette
            'cook_eel' => [
                'slug' => 'cook-eel',
                'title' => 'Anguille au poivre',
                'description' => 'Permet d\'apprêter l\'anguille electrique sans se faire mordre',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-eel-dish']], ['action' => 'equip.tool', 'slugs' => ['cookpot-mithril']]],
                'requiredPoints' => 90,
                'domain' => $d,
                'requirements' => ['cook_moonfish'],
            ],
            'cook_feast' => [
                'slug' => 'cook-feast',
                'title' => 'Festin',
                'description' => 'Permet de dresser un kraken juvenile pour toute une tablee',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-kraken-feast']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['cook_eel', 'cook_palate'],
            ],
            // Rang 3 bis (30-70 pts) — le geste, pas la recette (DOM-06)
            'cook_timing' => [
                'slug' => 'cook-timing',
                'title' => 'Sens du feu',
                'description' => 'Retirer au bon moment : les plats sortent plus vite de la marmite',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['cook_palate'],
            ],
            'cook_thrift' => [
                'slug' => 'cook-thrift',
                'title' => 'Economie de garde-manger',
                'description' => 'Rien ne se perd : les parures et les fonds resservent au plat suivant',
                'requiredPoints' => 45,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['cook_timing'],
            ],
            'cook_keeping' => [
                'slug' => 'cook-keeping',
                'title' => 'Conservation',
                'description' => 'Saler, fumer, confire : ce qui est prepare tient plus longtemps',
                'requiredPoints' => 70,
                'domain' => $d,
                'life' => 4,
                'requirements' => ['cook_thrift'],
            ],

            // Rang 4 (110-150 pts) — la branche, puis le geste de maitre
            //
            // DOM-06 : les deux nœuds terminaux appartiennent chacun a une
            // branche (DOM-04). C'est ici que le choix pris a l'etabli devient
            // visible dans l'arbre — sans quoi la specialisation resterait un
            // bonus de qualite dont rien, dans ce que le joueur apprend, ne
            // porterait la trace.
            'cook_feast_table' => [
                'slug' => 'cook-feast-table',
                'title' => 'Table de fete',
                'description' => 'Des effets puissants et courts, pour ce qu\'on fait ensemble',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'cuisinier', 'branch' => 'feast']],
                'requiredPoints' => 110,
                'domain' => $d,
                'life' => 6,
                'requirements' => ['cook_keeping'],
            ],
            'cook_road_provisions' => [
                'slug' => 'cook-road-provisions',
                'title' => 'Vivres de route',
                'description' => 'Des effets modestes et longs, pour ce qu\'on fait seul et loin',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'cuisinier', 'branch' => 'provisions']],
                'requiredPoints' => 110,
                'domain' => $d,
                'life' => 4,
                'hit' => 1,
                'requirements' => ['cook_keeping'],
            ],
            'cook_master' => [
                'slug' => 'cook-master',
                'title' => 'Maitre queux',
                'description' => 'La signature du cuisinier : ce qui sort de sa table se reconnait',
                'requiredPoints' => 150,
                'domain' => $d,
                'life' => 8,
                'critical' => 2,
                'requirements' => ['cook_feast'],
            ],
        ];
    }

    // =========================================================================
    // BUCHERON (bois/recolte) — la cinquieme recolte (ZON-34)
    // =========================================================================
    // Quatre essences, raretes inversees : le hetre a deux sources et ne doit
    // jamais etre un goulot ; les trois autres sont chacune l'exclusivite d'une
    // zone forestiere, et se gagnent sur un **savoir** (ECO-24c).
    //
    // **La hache est arrivee (DOM-05).** ZON-34 avait livre cet arbre sans
    // emplacement d'outil, et disait pourquoi : elle demandait un type d'outil,
    // un bit d'equipement et un emplacement d'interface neufs — un changement de
    // mecanisme, pas de donnees — et elle devait venir avec le charpentier, a qui
    // elle sert. Le charpentier existe depuis ECO-30 : la promesse est soldee, et
    // l'arbre passe de huit a quinze nœuds, au gabarit de recolte.
    private function getLumberjackSkills(): array
    {
        $d = 'lumberjack';

        return [
            // Rang 1 (0 pts) — deux entrees, comme le gabarit l'exige : la
            // matiere de tout le monde, et l'outil qui la coupe.
            'lumber_axe' => [
                'slug' => 'lumber-axe',
                'title' => 'Prise de hache',
                'description' => 'Debloque l\'emplacement de hache et l\'usage de la hache en bronze',
                'actions' => [['action' => 'tool_slot.unlock', 'slot' => 'axe'], ['action' => 'equip.tool', 'slugs' => ['axe-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'lumber_beech_xs' => [
                'slug' => 'lumber-beech-xs',
                'title' => 'Coupe du hetre',
                'description' => 'Permet d\'abattre les hetres des Vallons et de la Foret',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-beech-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — le geste s'affine, et la Foret s'ouvre
            'lumber_grain' => [
                'slug' => 'lumber-grain',
                'title' => 'Lecture du fil',
                'description' => 'Lire le fil du bois avant de frapper : moins de perte, plus de rondins',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['lumber_beech_xs'],
            ],
            'lumber_edge' => [
                'slug' => 'lumber-edge',
                'title' => 'Affutage',
                'description' => 'Un fer bien passe mord du premier coup — et ouvre la hache en fer',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['axe-iron']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['lumber_axe'],
            ],
            'lumber_whisperoak_xs' => [
                'slug' => 'lumber-whisperoak-xs',
                'title' => 'Coupe du chene murmurant',
                'description' => 'Permet d\'abattre l\'arbre qui donne son nom a la Foret des murmures',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-whisperoak-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['lumber_beech_xs'],
            ],

            // Rang 3 (35-60 pts) — les bois qui ont cesse d'etre du bois
            'lumber_peat_xs' => [
                'slug' => 'lumber-peat-xs',
                'title' => 'Coupe du bois tourbe',
                'description' => 'Permet de tirer du marais un bois que l\'eau morte a noirci',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-peat-xs']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['lumber_whisperoak_xs'],
            ],
            'lumber_keen_eye' => [
                'slug' => 'lumber-keen-eye',
                'title' => 'Oeil du bucheron',
                'description' => 'Reperer de loin les essences que les autres passent sans voir, et manier la hache en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['axe-steel']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['lumber_edge'],
            ],
            'lumber_reading' => [
                'slug' => 'lumber-reading',
                'title' => 'Lecture de la coupe',
                'description' => 'Savoir ce qu\'un peuplement a encore a donner avant d\'y porter le fer',
                'requiredPoints' => 45,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['lumber_keen_eye'],
            ],
            'lumber_bark' => [
                'slug' => 'lumber-bark',
                'title' => 'Ecorcage',
                'description' => 'Retirer l\'ecorce sur pied : le bois seche mieux et se fend moins',
                'requiredPoints' => 55,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['lumber_keen_eye'],
            ],
            'lumber_seasoning' => [
                'slug' => 'lumber-seasoning',
                'title' => 'Sechage',
                'description' => 'Choisir le bois deja sec sur pied : le rendement suit',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 15]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['lumber_grain'],
            ],
            'lumber_petrified_xs' => [
                'slug' => 'lumber-petrified-xs',
                'title' => 'Extraction du bois petrifie',
                'description' => 'Permet de degager du sable des Dunes un tronc de l\'age precedent',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-petrified-xs']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['lumber_peat_xs'],
            ],

            // Rang 4 (90-150 pts) — le sommet du metier
            'lumber_heartwood' => [
                'slug' => 'lumber-heartwood',
                'title' => 'Coeur de bille',
                'description' => 'Ne garder que le duramen : moins de bois, mais du meilleur',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 20]],
                'requiredPoints' => 90,
                'domain' => $d,
                'requirements' => ['lumber_seasoning', 'lumber_peat_xs'],
            ],
            'lumber_knotless' => [
                'slug' => 'lumber-knotless',
                'title' => 'Fil sans nœud',
                'description' => 'Choisir la bille sans defaut : ce qui sort de la coupe se travaille mieux',
                'requiredPoints' => 80,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['lumber_bark'],
            ],
            'lumber_deep_scouting' => [
                'slug' => 'lumber-deep-scouting',
                'title' => 'Lecture du peuplement',
                'description' => 'Voir de loin ce qu\'un bois donnera, et manier la hache en mithril',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['axe-mithril']]],
                'requiredPoints' => 110,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['lumber_reading', 'lumber_heartwood'],
            ],
            'lumber_master' => [
                'slug' => 'lumber-master',
                'title' => 'Maitre bucheron',
                'description' => 'Le bois n\'a plus de secret : chaque essence rend ce qu\'elle peut donner',
                'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 25]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['lumber_heartwood', 'lumber_petrified_xs'],
            ],
        ];
    }

    // =========================================================================
    // CHARPENTIER (bois/craft) — le debouche de la ligne du bois (ECO-30)
    // =========================================================================
    // ZON-34 a livre quatre essences **sans un seul debouche** : on pouvait
    // abattre le chene murmurant et n'avoir rien a en faire. Cet arbre ouvre les
    // dix recettes qui ferment ce trou, et chaque essence y trouve sa fin.
    //
    // OBJ-06 : la varlope existe — le mecanisme differe par ECO-30 est livre,
    // et le nœud gratuit de la planche porte l'emplacement et le palier
    // d'entree.
    private function getCarpenterSkills(): array
    {
        $d = 'carpenter';

        return [
            // Rang 1 (0 pts) — la planche, par ou tout passe
            'carpenter_plank' => [
                'slug' => 'carpenter-plank',
                'title' => 'Debit du hetre',
                'description' => 'Permet de debiter le hetre en planches, la matiere de tout le metier',
                // OBJ-06 : la varlope arrive avec la planche — meme motif que
                // les autres arbres, le nœud gratuit livre l'emplacement et le
                // palier d'entree.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-plank']], ['action' => 'tool_slot.unlock', 'slot' => 'plane'], ['action' => 'equip.tool', 'slugs' => ['plane-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-25 pts) — ce qu'on vend aux autres, et les premieres armes
            'carpenter_haft' => [
                'slug' => 'carpenter-haft',
                'title' => 'Tournage de manches',
                'description' => 'Permet de tourner les manches que le forgeron ne sait pas faire',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-wood-haft']]],
                // DOM-06 : seconde entree gratuite, comme le gabarit l'exige. La
                // planche et le manche sont le meme geste elementaire ; faire
                // payer le second faisait de l'entree un couloir.
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'carpenter_first_weapons' => [
                'slug' => 'carpenter-first-weapons',
                'title' => 'Arcs et batons',
                'description' => 'Permet de cintrer l\'arc court et de tailler le baton de novice',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-t1-bow', 'recipe-t1-staff']], ['action' => 'equip.tool', 'slugs' => ['plane-iron']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['carpenter_plank'],
            ],

            // Rang 3 (35-60 pts) — le consommable, le geste, le composite
            'carpenter_fletching' => [
                'slug' => 'carpenter-fletching',
                'title' => 'Empennage',
                'description' => 'Permet d\'empenner les fleches : le seul produit du metier qui se depense',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-arrows']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['carpenter_first_weapons'],
            ],
            'carpenter_joinery' => [
                'slug' => 'carpenter-joinery',
                'title' => 'Assemblage',
                'description' => 'Assembler sans clou ni colle : ce qu\'on batit ainsi tient mieux',
                'requiredPoints' => 50,
                'domain' => $d,
                'life' => 4,
                'requirements' => ['carpenter_haft'],
            ],
            'carpenter_composite' => [
                'slug' => 'carpenter-composite',
                'title' => 'Collage composite',
                'description' => 'Permet de coller le chene murmurant et la corne en armes de second palier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-t2-bow', 'recipe-t2-staff']], ['action' => 'equip.tool', 'slugs' => ['plane-steel']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['carpenter_first_weapons'],
            ],

            // Rang 4 (90-150 pts) — meubler les autres, et le bois qui a cesse d'en etre
            'carpenter_cabinetmaking' => [
                'slug' => 'carpenter-cabinetmaking',
                'title' => 'Ebenisterie',
                'description' => 'Permet de monter le necessaire qui meuble la demeure d\'un autre joueur',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-furnishing-kit']], ['action' => 'equip.tool', 'slugs' => ['plane-mithril']]],
                'requiredPoints' => 90,
                'domain' => $d,
                'requirements' => ['carpenter_joinery'],
            ],
            'carpenter_master' => [
                'slug' => 'carpenter-master',
                'title' => 'Maitre charpentier',
                'description' => 'Permet de travailler le bois tourbe et le bois petrifie, que le temps a durcis',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-t3-bow', 'recipe-t3-staff']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['carpenter_composite', 'carpenter_cabinetmaking'],
            ],
            // Rang 3 bis (30-70 pts) — le geste, pas la recette (DOM-06)
            'carpenter_true_grain' => [
                'slug' => 'carpenter-true-grain',
                'title' => 'Fil d\'equerre',
                'description' => 'Debiter dans le fil : le canal du baton porte le sort sans le tordre',
                'requiredPoints' => 30,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['carpenter_first_weapons'],
            ],
            'carpenter_offcuts' => [
                'slug' => 'carpenter-offcuts',
                'title' => 'Chutes utiles',
                'description' => 'Les tombees d\'une piece font les fleches de la suivante',
                'requiredPoints' => 45,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['carpenter_true_grain'],
            ],
            'carpenter_batch' => [
                'slug' => 'carpenter-batch',
                'title' => 'Travail en lot',
                'description' => 'Douze fleches d\'un coup plutot qu\'une douzaine de fois une',
                'requiredPoints' => 70,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['carpenter_offcuts'],
            ],

            'carpenter_tuning' => [
                'slug' => 'carpenter-tuning',
                'title' => 'Accord du canal',
                'description' => 'Regler la piece jusqu\'a ce que le flux la traverse sans accrocher',
                'requiredPoints' => 85,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['carpenter_composite'],
            ],

            // Rang 4 (110-150 pts) — la branche, puis le geste de maitre (DOM-06)
            'carpenter_bowyer' => [
                'slug' => 'carpenter-bowyer',
                'title' => 'Armes de trait',
                'description' => 'Arcs, batons et fleches : le bois qui projette',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'charpentier', 'branch' => 'ranged']],
                'requiredPoints' => 110,
                'domain' => $d,
                'damage' => 3,
                'requirements' => ['carpenter_batch'],
            ],
            'carpenter_furnisher' => [
                'slug' => 'carpenter-furnisher',
                'title' => 'Mobilier',
                'description' => 'Ce qui meuble une demeure et ce qui la fait tenir',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'charpentier', 'branch' => 'furnishing']],
                'requiredPoints' => 110,
                'domain' => $d,
                'life' => 6,
                'requirements' => ['carpenter_batch'],
            ],
            'carpenter_signature' => [
                'slug' => 'carpenter-signature',
                'title' => 'Marque du charpentier',
                'description' => 'La signature au fer sur la piece finie : on sait de qui elle vient',
                'requiredPoints' => 150,
                'domain' => $d,
                'critical' => 2,
                'hit' => 1,
                'requirements' => ['carpenter_master'],
            ],
        ];
    }

    // =========================================================================
    // TAILLEUR (air/craft) — celui qui habille les lanceurs de sorts (ECO-31)
    // =========================================================================
    // Le trou le plus beant de l'audit d'equipement : sur 121 pieces, **pas une
    // robe**. Pyromancien, hydromancien, necromancien — tous s'habillaient en
    // cuir et en metal, et aucun metier ne les habillait.
    //
    // L'arbre ouvre les onze recettes de la categorie tissu, et reveille au
    // passage `crafted-cloth`, un objet livre de longue date que **rien ne
    // produisait ni ne consommait**.
    //
    // OBJ-06 : l'aiguille existe — le mecanisme differe par ECO-31 est livre,
    // et le nœud gratuit du tissage porte l'emplacement et le palier d'entree.
    private function getTailorSkills(): array
    {
        $d = 'tailor';

        return [
            // Rang 1 (0 pts) — la toile, par ou tout passe
            'tailor_weaving' => [
                'slug' => 'tailor-weaving',
                'title' => 'Tissage',
                'description' => 'Permet de rouir, filer et tisser le lin des Vallons en toile',
                // OBJ-06 : l'aiguille arrive avec la toile — meme motif que les
                // autres arbres, le nœud gratuit livre l'emplacement et le
                // palier d'entree.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cloth']], ['action' => 'tool_slot.unlock', 'slot' => 'needle'], ['action' => 'equip.tool', 'slugs' => ['needle-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-25 pts) — le plancher du lanceur de sorts
            'tailor_linen' => [
                'slug' => 'tailor-linen',
                'title' => 'Coupe de la toile',
                'description' => 'Permet de tailler la capuche et les mitaines de lin, les deux premieres pieces du mage',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-linen-hood', 'recipe-linen-gloves']]],
                // DOM-06 : seconde entree gratuite, comme le gabarit l'exige.
                // Tisser et tailler sont le meme geste elementaire ; faire payer
                // le second faisait de l'entree un couloir.
                'requiredPoints' => 0,
                'domain' => $d,
                'requirements' => ['tailor_weaving'],
            ],
            'tailor_robe' => [
                'slug' => 'tailor-robe',
                'title' => 'Montage de la robe',
                'description' => 'Permet de monter la premiere robe du monde : rien n\'y entrave le geste',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-linen-robe']], ['action' => 'equip.tool', 'slugs' => ['needle-iron']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['tailor_weaving'],
            ],

            // Rang 3 (35-60 pts) — le lin fin, et la main qui s'affine
            'tailor_fine_weave' => [
                'slug' => 'tailor-fine-weave',
                'title' => 'Battage du lin',
                'description' => 'Permet de battre le lin jusqu\'a la soie : capuche et mitaines de lin fin',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-fine-linen-hood', 'recipe-fine-linen-gloves']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['tailor_linen'],
            ],
            'tailor_needle' => [
                'slug' => 'tailor-needle',
                'title' => 'Aiguille sure',
                'description' => 'Coudre sans y penser : la main tient plus longtemps',
                'requiredPoints' => 50,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['tailor_robe'],
            ],
            'tailor_fine_robe' => [
                'slug' => 'tailor-fine-robe',
                'title' => 'Couture invisible',
                'description' => 'Permet de monter la robe de lin fin, dont les coutures ne se voient pas',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-fine-linen-robe']], ['action' => 'equip.tool', 'slugs' => ['needle-steel']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['tailor_fine_weave'],
            ],

            // Rang 4 (90-150 pts) — ce qui n'est plus tout a fait du lin
            'tailor_shadowsilk' => [
                'slug' => 'tailor-shadowsilk',
                'title' => 'Trame d\'ombre',
                'description' => 'Permet de tisser le poil de loup-garou dans la toile : la soie d\'ombre',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-shadowsilk-hood', 'recipe-shadowsilk-robe']], ['action' => 'equip.tool', 'slugs' => ['needle-mithril']]],
                'requiredPoints' => 90,
                'domain' => $d,
                'requirements' => ['tailor_fine_robe'],
            ],
            'tailor_archivist' => [
                'slug' => 'tailor-archivist',
                'title' => 'Maitre tailleur',
                'description' => 'Permet de coudre une gemme enchantee dans le col : le mantelet et la robe de l\'archiviste',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-archivist-mantle', 'recipe-archivist-robe']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['tailor_shadowsilk', 'tailor_needle'],
            ],
            // Rang 3 bis (30-70 pts) — le geste, pas la recette (DOM-06)
            'tailor_setting' => [
                'slug' => 'tailor-setting',
                'title' => 'Sertissage de tissu',
                'description' => 'Coudre l\'emplacement de sort a meme la trame : il tient mieux le flux',
                'requiredPoints' => 30,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['tailor_robe'],
            ],
            'tailor_lining' => [
                'slug' => 'tailor-lining',
                'title' => 'Doublure',
                'description' => 'Une doublure bien posee : on travaille plus longtemps sans s\'user',
                'requiredPoints' => 45,
                'domain' => $d,
                'life' => 4,
                'requirements' => ['tailor_setting'],
            ],
            'tailor_selvage' => [
                'slug' => 'tailor-selvage',
                'title' => 'Lisiere franche',
                'description' => 'Une lisiere qui ne file pas : la piece sort du metier sans perte',
                'requiredPoints' => 70,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['tailor_lining'],
            ],

            'tailor_dye' => [
                'slug' => 'tailor-dye',
                'title' => 'Teinture stable',
                'description' => 'Une couleur qui ne passe pas au soleil ni sous la pluie',
                'requiredPoints' => 85,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['tailor_fine_robe'],
            ],

            // Rang 4 (110-150 pts) — la branche, puis le geste de maitre (DOM-06)
            'tailor_spellrobes' => [
                'slug' => 'tailor-spellrobes',
                'title' => 'Robes de sort',
                'description' => 'Le tissu qui porte les emplacements de sort',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'tailleur', 'branch' => 'spellrobes']],
                'requiredPoints' => 110,
                'domain' => $d,
                'critical' => 3,
                'requirements' => ['tailor_selvage'],
            ],
            'tailor_workwear' => [
                'slug' => 'tailor-workwear',
                'title' => 'Tenues de travail',
                'description' => 'Le tissu qui tient a la recolte : doublures et confort',
                'actions' => [['action' => 'specialization.branch', 'craft' => 'tailleur', 'branch' => 'workwear']],
                'requiredPoints' => 110,
                'domain' => $d,
                'life' => 6,
                'requirements' => ['tailor_selvage'],
            ],
            'tailor_signature' => [
                'slug' => 'tailor-signature',
                'title' => 'Point du maitre',
                'description' => 'Une couture qu\'on reconnait sans lire l\'etiquette',
                'requiredPoints' => 150,
                'domain' => $d,
                'critical' => 2,
                'life' => 4,
                'requirements' => ['tailor_archivist'],
            ],
        ];
    }

    // =========================================================================
    // FORGERON (metal/craft) — 15 skills, forge d'armes et armures
    // =========================================================================
    private function getBlacksmithSkills(): array
    {
        $d = 'blacksmith';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'smith_dagger' => [
                'slug' => 'smith-dagger',
                'title' => 'Forge de dagues',
                'description' => 'Permet de forger des dagues en fer et debloque l\'emplacement de marteau de forge',
                // ECO-02 : `equip.tool` accompagne l'ouverture de l'emplacement, comme
                // dans les arbres de recolte. Sans lui, `CraftingManager::checkCraftTool`
                // refusait tout artisanat — l'emplacement s'ouvrait, aucun outil n'y
                // entrait, et les quatre metiers etaient injouables.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-dagger', 'recipe-short-sword', 'recipe-bronze-ingot', 'recipe-iron-ingot']], ['action' => 'tool_slot.unlock', 'slot' => 'hammer'], ['action' => 'equip.tool', 'slugs' => ['hammer-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'smith_chainmail' => [
                'slug' => 'smith-chainmail',
                'title' => 'Forge de cottes de mailles',
                'description' => 'Permet de forger des cottes de mailles basiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-chainmail']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'smith_sword' => [
                'slug' => 'smith-sword',
                'title' => 'Forge d\'epees',
                'description' => 'Permet de forger des epees en fer',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-sword']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['smith_dagger'],
            ],
            'smith_shield' => [
                'slug' => 'smith-shield',
                'title' => 'Forge de boucliers',
                'description' => 'Permet de forger des boucliers en fer',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['hammer-iron']], ['action' => 'craft', 'recipes' => ['recipe-iron-shield']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['smith_chainmail'],
            ],
            'smith_temper' => [
                'slug' => 'smith-temper',
                'title' => 'Trempe amelioree',
                'description' => 'Augmente la qualite des objets forges',
                'requiredPoints' => 15,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['smith_dagger'],
            ],
            'smith_plate' => [
                'slug' => 'smith-plate',
                'title' => 'Forge de plaques',
                'description' => 'Permet de forger des pieces d\'armure en plaques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-chestplate', 'recipe-iron-greaves', 'recipe-iron-helmet', 'recipe-iron-gauntlets', 'recipe-iron-boots']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['smith_chainmail'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'smith_steel_weapons' => [
                'slug' => 'smith-steel-weapons',
                'title' => 'Forge d\'acier — armes',
                'description' => 'Permet de forger des armes en acier',
                // OBJ-06 : le forgeron devient la source des outils d'acier —
                // les 12 types, recettes derivees (RecipeFixtures::toolRecipesData).
                'actions' => [['action' => 'equip.tool', 'slugs' => ['hammer-steel']], ['action' => 'craft', 'recipes' => [
                    'recipe-steel-sword', 'recipe-steel-dagger',
                    'recipe-pickaxe-steel', 'recipe-sickle-steel', 'recipe-fishing-rod-steel', 'recipe-skinning-knife-steel',
                    'recipe-hammer-steel', 'recipe-tanning-kit-steel', 'recipe-mortar-steel', 'recipe-chisel-steel',
                    'recipe-axe-steel', 'recipe-cookpot-steel', 'recipe-plane-steel', 'recipe-needle-steel',
                ]]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['smith_sword', 'smith_temper'],
            ],
            'smith_steel_armor' => [
                'slug' => 'smith-steel-armor',
                'title' => 'Forge d\'acier — armures',
                'description' => 'Permet de forger des armures en acier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-steel-chainmail', 'recipe-steel-plate']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['smith_shield', 'smith_plate'],
            ],
            'smith_whetstone' => [
                'slug' => 'smith-whetstone',
                'title' => 'Forge de pierres a aiguiser',
                'description' => 'Permet de forger des pierres a aiguiser pour ameliorer les armes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-whetstone']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['smith_temper'],
            ],
            'smith_reinforcement' => [
                'slug' => 'smith-reinforcement',
                'title' => 'Renforcement',
                'description' => 'Augmente la solidite des equipements forges',
                'requiredPoints' => 40,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['smith_steel_weapons'],
            ],
            'smith_axe' => [
                'slug' => 'smith-axe',
                'title' => 'Forge de haches',
                'description' => 'Permet de forger des haches en acier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-steel-axe']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['smith_steel_armor'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'smith_mithril' => [
                'slug' => 'smith-mithril',
                'title' => 'Forge de mithril',
                'description' => 'Permet de forger des equipements en mithril',
                // OBJ-06 : et des outils de mithril — le haut de l'echelle
                // d'outillage est un craft, pas une boutique.
                'actions' => [['action' => 'equip.tool', 'slugs' => ['hammer-mithril']], ['action' => 'craft', 'recipes' => [
                    'recipe-mithril-helm', 'recipe-mithril-cuirass', 'recipe-mithril-greaves', 'recipe-mithril-sabatons', 'recipe-mithril-gauntlets', 'recipe-mithril-pauldrons',
                    'recipe-pickaxe-mithril', 'recipe-sickle-mithril', 'recipe-fishing-rod-mithril', 'recipe-skinning-knife-mithril',
                    'recipe-hammer-mithril', 'recipe-tanning-kit-mithril', 'recipe-mortar-mithril', 'recipe-chisel-mithril',
                    'recipe-axe-mithril', 'recipe-cookpot-mithril', 'recipe-plane-mithril', 'recipe-needle-mithril',
                ]]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['smith_steel_weapons', 'smith_steel_armor'],
            ],
            'smith_heavy_armor' => [
                'slug' => 'smith-heavy-armor',
                'title' => 'Forge d\'armures lourdes',
                'description' => 'Permet de forger des armures lourdes en acier renforce',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-heavy-steel-plate']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['smith_whetstone', 'smith_reinforcement'],
            ],
            'smith_alloy' => [
                'slug' => 'smith-alloy',
                'title' => 'Alliages speciaux',
                'description' => 'Permet de creer des alliages aux proprietes uniques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-mithril-ingot', 'recipe-cobalt-ingot', 'recipe-adamantite-ingot', 'recipe-orichalcum-ingot']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'damage' => 2,
                'requirements' => ['smith_axe'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'smith_master' => [
                'slug' => 'smith-master',
                'title' => 'Maitre forgeron',
                'description' => 'Maitrise absolue de la forge — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-masterwork-blade']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['smith_mithril', 'smith_heavy_armor'],
            ],
        ];
    }

    // =========================================================================
    // TANNEUR (bete/craft) — 15 skills, travail du cuir et des peaux
    // =========================================================================
    private function getLeatherworkerSkills(): array
    {
        $d = 'leatherworker';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'leather_light_armor' => [
                'slug' => 'leather-light-armor',
                'title' => 'Travail du cuir brut',
                'description' => 'Permet de confectionner des armures legeres en cuir brut et debloque l\'emplacement de kit de tannage',
                // ECO-02 : `recipe-leather-vest` n'existe pas — le skill d'entree du
                // tanneur ne debloquait rien. Il ouvre desormais le palier 1 reel du
                // metier (bottes + lanieres, deux recettes a base de cuir brut).
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-boots', 'recipe-leather-strip', 'recipe-leather-hat']], ['action' => 'tool_slot.unlock', 'slot' => 'tanning_kit'], ['action' => 'equip.tool', 'slugs' => ['tanning-kit-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'leather_gloves' => [
                'slug' => 'leather-gloves',
                'title' => 'Confection de gants',
                'description' => 'Permet de confectionner des gants en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-gloves']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'leather_boots' => [
                'slug' => 'leather-boots',
                'title' => 'Confection de bottes',
                'description' => 'Permet de confectionner des bottes en cuir',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['tanning-kit-iron']], ['action' => 'craft', 'recipes' => ['recipe-leather-boots']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['leather_light_armor'],
            ],
            'leather_belt' => [
                'slug' => 'leather-belt',
                'title' => 'Confection de ceintures',
                'description' => 'Permet de confectionner des ceintures en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-belt']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['leather_gloves'],
            ],
            'leather_tanning' => [
                'slug' => 'leather-tanning',
                'title' => 'Tannage ameliore',
                'description' => 'Ameliore la qualite du cuir tanne',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 2,
                'requirements' => ['leather_light_armor'],
            ],
            'leather_quiver' => [
                'slug' => 'leather-quiver',
                'title' => 'Confection de carquois',
                'description' => 'Permet de confectionner des carquois en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-quiver']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['leather_gloves'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'leather_hardened' => [
                'slug' => 'leather-hardened',
                'title' => 'Cuir renforce',
                'description' => 'Permet de confectionner des armures en cuir renforce',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['tanning-kit-steel']], ['action' => 'craft', 'recipes' => ['recipe-hardened-vest', 'recipe-hardened-boots', 'recipe-hardened-pants', 'recipe-hardened-shoulders']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['leather_boots', 'leather_tanning'],
            ],
            'leather_accessories' => [
                'slug' => 'leather-accessories',
                'title' => 'Accessoires en cuir',
                'description' => 'Permet de confectionner des accessoires avances',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-hardened-belt', 'recipe-hardened-gloves']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['leather_belt', 'leather_quiver'],
            ],
            'leather_supple' => [
                'slug' => 'leather-supple',
                'title' => 'Cuir souple',
                'description' => 'Augmente la souplesse des equipements en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-armor', 'recipe-leather-pants', 'recipe-leather-shoulders']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['leather_tanning'],
            ],
            'leather_exotic_hide' => [
                'slug' => 'leather-exotic-hide',
                'title' => 'Travail des peaux exotiques',
                'description' => 'Permet de travailler les cuirs de creatures rares',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-exotic-leather-vest']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'requirements' => ['leather_hardened'],
            ],
            'leather_reinforced_quiver' => [
                'slug' => 'leather-reinforced-quiver',
                'title' => 'Carquois renforce',
                'description' => 'Permet de confectionner des carquois renforces en cuir epais',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-hardened-quiver']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['leather_accessories'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'leather_dragon_hide' => [
                'slug' => 'leather-dragon-hide',
                'title' => 'Travail du cuir de dragon',
                'description' => 'Permet de confectionner des equipements en cuir de dragon',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['tanning-kit-mithril']], ['action' => 'craft', 'recipes' => ['recipe-dragon-vest', 'recipe-dragon-boots']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['leather_hardened', 'leather_accessories'],
            ],
            'leather_resilience' => [
                'slug' => 'leather-resilience',
                'title' => 'Resilience du cuir',
                'description' => 'Les equipements en cuir conferes accordent des bonus de vie',
                'requiredPoints' => 80,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['leather_supple', 'leather_exotic_hide'],
            ],
            'leather_enchanted' => [
                'slug' => 'leather-enchanted',
                'title' => 'Cuir enchante',
                'description' => 'Permet de travailler des cuirs impregnes de magie',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-enchanted-vest']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['leather_reinforced_quiver'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'leather_master' => [
                'slug' => 'leather-master',
                'title' => 'Maitre tanneur',
                'description' => 'Maitrise absolue de la tannerie — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-masterwork-drakehide-cloak']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['leather_dragon_hide', 'leather_resilience'],
            ],
        ];
    }

    // =========================================================================
    // ALCHIMISTE (eau/craft) — 15 skills, potions et elixirs
    // =========================================================================
    private function getAlchimistSkills(): array
    {
        $d = 'alchimist';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'alchi_health_pot' => [
                'slug' => 'alchi-health-pot',
                'title' => 'Potion de soin mineure',
                'description' => 'Permet de brasser des potions de soin mineures et debloque l\'emplacement de mortier d\'alchimie',
                // ECO-02 : `recipe-health-potion-minor` n'existe pas ; la recette de
                // soin reellement livree est `recipe-healing-potion`.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-healing-potion', 'recipe-potion-base']], ['action' => 'tool_slot.unlock', 'slot' => 'mortar'], ['action' => 'equip.tool', 'slugs' => ['mortar-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'alchi_energy_pot' => [
                'slug' => 'alchi-energy-pot',
                'title' => 'Potion d\'energie mineure',
                'description' => 'Permet de brasser des potions d\'energie mineures',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-energy-potion']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'alchi_health_pot_2' => [
                'slug' => 'alchi-health-pot-2',
                'title' => 'Potion de soin standard',
                'description' => 'Permet de brasser des potions de soin standard',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['mortar-iron']], ['action' => 'craft', 'recipes' => ['recipe-healing-medium', 'recipe-onguent-healing']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['alchi_health_pot'],
            ],
            'alchi_energy_pot_2' => [
                'slug' => 'alchi-energy-pot-2',
                'title' => 'Potion d\'energie standard',
                'description' => 'Permet de brasser des potions d\'energie standard',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-energy-potion-standard']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot'],
            ],
            'alchi_distillation' => [
                'slug' => 'alchi-distillation',
                'title' => 'Distillation amelioree',
                'description' => 'Augmente l\'efficacite des potions brassees',
                'requiredPoints' => 15,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['alchi_health_pot'],
            ],
            'alchi_antidote' => [
                'slug' => 'alchi-antidote',
                'title' => 'Preparation d\'antidotes',
                'description' => 'Permet de brasser des antidotes contre les poisons',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-antidote']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'alchi_health_pot_3' => [
                'slug' => 'alchi-health-pot-3',
                'title' => 'Potion de soin superieure',
                'description' => 'Permet de brasser des potions de soin superieures',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['mortar-steel']], ['action' => 'craft', 'recipes' => ['recipe-healing-major']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['alchi_health_pot_2', 'alchi_distillation'],
            ],
            'alchi_buff_pot' => [
                'slug' => 'alchi-buff-pot',
                'title' => 'Elixir de force',
                'description' => 'Permet de brasser des elixirs augmentant les degats',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-elixir-force']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot_2'],
            ],
            'alchi_resist_pot' => [
                'slug' => 'alchi-resist-pot',
                'title' => 'Elixir de resistance',
                'description' => 'Permet de brasser des elixirs de resistance elementaire',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-elixir-defense']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['alchi_antidote'],
            ],
            'alchi_concentration' => [
                'slug' => 'alchi-concentration',
                'title' => 'Concentration alchimique',
                'description' => 'Chance de brasser une potion bonus',
                // ECO-19 : le seul nœud d'alchimie ou une fiole de poison a sa place —
                // concentrer un principe actif jusqu'a le rendre letal.
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-poison-vial']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['alchi_health_pot_3'],
            ],
            'alchi_speed_pot' => [
                'slug' => 'alchi-speed-pot',
                'title' => 'Elixir de vitesse',
                'description' => 'Permet de brasser des elixirs augmentant la vitesse',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-speed-elixir']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['alchi_resist_pot'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'alchi_grand_potions' => [
                'slug' => 'alchi-grand-potions',
                'title' => 'Grandes potions',
                'description' => 'Permet de brasser des potions de grande puissance',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['mortar-mithril']], ['action' => 'craft', 'recipes' => ['recipe-elixir-vitality']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['alchi_health_pot_3', 'alchi_buff_pot'],
            ],
            'alchi_transmutation' => [
                'slug' => 'alchi-transmutation',
                'title' => 'Transmutation',
                'description' => 'Permet de transmuter des ingredients en materiaux rares',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-transmute-rare']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['alchi_resist_pot', 'alchi_concentration'],
            ],
            'alchi_purity' => [
                'slug' => 'alchi-purity',
                'title' => 'Purete alchimique',
                'description' => 'Augmente la puissance de toutes les potions brassees',
                'requiredPoints' => 100,
                'domain' => $d,
                'heal' => 2,
                'hit' => 1,
                'requirements' => ['alchi_speed_pot'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'alchi_master' => [
                'slug' => 'alchi-master',
                'title' => 'Maitre alchimiste',
                'description' => 'Maitrise absolue de l\'alchimie — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-masterwork-grand-elixir']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['alchi_grand_potions', 'alchi_transmutation'],
            ],
        ];
    }

    // =========================================================================
    // JOAILLIER (terre/craft) — 15 skills, gemmes et sertissage de materia
    // =========================================================================
    private function getJewellerSkills(): array
    {
        $d = 'jeweller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'jewel_cut_basic' => [
                'slug' => 'jewel-cut-basic',
                'title' => 'Taille de gemmes brutes',
                'description' => 'Permet de tailler des gemmes brutes en pierres utilisables et debloque l\'emplacement de burin de joaillier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cut-gem-basic']], ['action' => 'tool_slot.unlock', 'slot' => 'chisel'], ['action' => 'equip.tool', 'slugs' => ['chisel-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'jewel_ring_basic' => [
                'slug' => 'jewel-ring-basic',
                'title' => 'Fabrication d\'anneaux simples',
                'description' => 'Permet de fabriquer des anneaux en metal basique',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-ring', 'recipe-copper-ring']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'jewel_amulet_basic' => [
                'slug' => 'jewel-amulet-basic',
                'title' => 'Fabrication d\'amulettes',
                'description' => 'Permet de fabriquer des amulettes basiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-amulet']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['jewel_ring_basic'],
            ],
            'jewel_cut_fine' => [
                'slug' => 'jewel-cut-fine',
                'title' => 'Taille de gemmes fines',
                'description' => 'Permet de tailler des gemmes de qualite fine',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['chisel-iron']], ['action' => 'craft', 'recipes' => ['recipe-cut-gem-fine']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['jewel_cut_basic'],
            ],
            'jewel_polish' => [
                'slug' => 'jewel-polish',
                'title' => 'Polissage de gemmes',
                'description' => 'Augmente la qualite des gemmes taillees',
                'requiredPoints' => 15,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['jewel_cut_basic'],
            ],
            'jewel_bracelet' => [
                'slug' => 'jewel-bracelet',
                'title' => 'Fabrication de bracelets',
                'description' => 'Permet de fabriquer des bracelets en metal et gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-bracelet']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['jewel_ring_basic'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'jewel_cut_rare' => [
                'slug' => 'jewel-cut-rare',
                'title' => 'Taille de gemmes rares',
                'description' => 'Permet de tailler des gemmes rares aux proprietes magiques',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['chisel-steel']], ['action' => 'craft', 'recipes' => ['recipe-cut-gem-rare', 'recipe-amber-seal']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['jewel_cut_fine', 'jewel_polish'],
            ],
            'jewel_ring_gold' => [
                'slug' => 'jewel-ring-gold',
                'title' => 'Fabrication d\'anneaux en or',
                'description' => 'Permet de fabriquer des anneaux en or sertis de gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-gold-ingot', 'recipe-gold-ring', 'recipe-gold-amulet']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['jewel_amulet_basic'],
            ],
            'jewel_filigree' => [
                'slug' => 'jewel-filigree',
                'title' => 'Filigrane',
                'description' => 'Maitrise du filigrane — ameliore la qualite des bijoux fabriques',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'heal' => 1,
                'requirements' => ['jewel_bracelet'],
            ],
            'jewel_crown' => [
                'slug' => 'jewel-crown',
                'title' => 'Fabrication de couronnes',
                'description' => 'Permet de fabriquer des couronnes ornees de gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-gold-crown']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'requirements' => ['jewel_bracelet'],
            ],
            'jewel_enchant' => [
                'slug' => 'jewel-enchant',
                'title' => 'Enchantement de gemmes',
                'description' => 'Permet d\'enchanter les gemmes pour leur conferer des proprietes magiques',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['chisel-mithril']], ['action' => 'craft', 'recipes' => ['recipe-enchant-gem']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['jewel_cut_rare'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'jewel_masterwork' => [
                'slug' => 'jewel-masterwork',
                'title' => 'Bijoux d\'exception',
                'description' => 'Permet de creer des bijoux d\'exception aux stats elevees',
                // ZON-40 — l'anneau d'amethyste rejoint le rang qui porte deja
                // les anneaux de mithril (niveau 6, contre 7 pour celui-ci).
                // Une recette qu'aucun nœud d'arbre ne debloque est du contenu
                // livre et inatteignable (loi ECO-20b).
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-mithril-ring', 'recipe-mithril-amulet', 'recipe-amethyst-ring']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['jewel_cut_rare', 'jewel_ring_gold'],
            ],
            'jewel_gem_elemental' => [
                'slug' => 'jewel-gem-elemental',
                'title' => 'Taille de gemmes elementaires',
                'description' => 'Maitrise de la taille de gemmes infusees d\'energie elementaire',
                'requiredPoints' => 80,
                'domain' => $d,
                'damage' => 2,
                'heal' => 2,
                'requirements' => ['jewel_filigree', 'jewel_crown'],
            ],
            'jewel_prismatic' => [
                'slug' => 'jewel-prismatic',
                'title' => 'Gemmes prismatiques',
                'description' => 'Permet de creer des gemmes multi-elementaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-prismatic-gem']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['jewel_enchant'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'jewel_master' => [
                'slug' => 'jewel-master',
                'title' => 'Maitre joaillier',
                'description' => 'Maitrise absolue de la joaillerie — acces aux gemmes et bijoux legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-legendary-ring', 'recipe-legendary-amulet', 'recipe-masterwork-starforged-ring']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['jewel_masterwork', 'jewel_gem_elemental'],
            ],
        ];
    }

    // =========================================================================
    // COMPETENCES PARTAGEES — multi-domaines (recolte + craft)
    // =========================================================================
    private function getSharedSkills(): array
    {
        return [
            // Premiers soins — partage entre tous les domaines de recolte
            'shared_first_aid' => [
                'slug' => 'shared-first-aid',
                'title' => 'Premiers soins',
                'description' => 'Permet de se soigner legerement pendant les activites de recolte',
                'requiredPoints' => 10,
                'domain' => ['miner', 'herbalist', 'fisherman', 'skinner'],
                'heal' => 1,
            ],
            // Endurance — partage entre tous les domaines de recolte
            'shared_endurance' => [
                'slug' => 'shared-endurance',
                'title' => 'Endurance du recolteur',
                'description' => 'Augmente les points de vie pendant les activites de recolte',
                'requiredPoints' => 20,
                'domain' => ['miner', 'herbalist', 'fisherman', 'skinner'],
                'life' => 3,
                'requirements' => ['shared_first_aid'],
            ],
            // Sens du terrain — partage entre mineur et herboriste
            'shared_terrain_sense' => [
                'slug' => 'shared-terrain-sense',
                'title' => 'Sens du terrain',
                'description' => 'Augmente les chances de trouver des ressources rares en explorant',
                'requiredPoints' => 30,
                'domain' => ['miner', 'herbalist'],
                'critical' => 1,
                'requirements' => ['shared_endurance'],
            ],
            // Instinct de survie — partage entre depeceur et pecheur
            'shared_survival' => [
                'slug' => 'shared-survival',
                'title' => 'Instinct de survie',
                'description' => 'Ameliore les chances de recolte dans les zones dangereuses',
                'requiredPoints' => 30,
                'domain' => ['skinner', 'fisherman'],
                'hit' => 2,
                'requirements' => ['shared_endurance'],
            ],
            // Efficacite artisanale — partage entre tous les domaines de craft
            'shared_craft_efficiency' => [
                'slug' => 'shared-craft-efficiency',
                'title' => 'Efficacite artisanale',
                'description' => 'Reduit les chances d\'echec lors de la fabrication d\'objets',
                'requiredPoints' => 10,
                'domain' => ['blacksmith', 'leatherworker', 'alchimist', 'jeweller'],
                'hit' => 1,
            ],
            // Economie de materiaux — partage entre tous les domaines de craft
            'shared_material_saving' => [
                'slug' => 'shared-material-saving',
                'title' => 'Economie de materiaux',
                'description' => 'Chance de ne pas consommer certains materiaux lors du craft',
                'requiredPoints' => 20,
                'domain' => ['blacksmith', 'leatherworker', 'alchimist', 'jeweller'],
                'critical' => 1,
                'requirements' => ['shared_craft_efficiency'],
            ],
            // Masterwork — partage entre forgeron et tanneur
            'shared_masterwork' => [
                'slug' => 'shared-masterwork',
                'title' => 'Maitre artisan',
                'description' => 'Augmente la qualite globale des equipements fabriques',
                'requiredPoints' => 30,
                'domain' => ['blacksmith', 'leatherworker'],
                'damage' => 1,
                'life' => 2,
                'requirements' => ['shared_material_saving'],
            ],
            // Savoir alchimique — partage entre alchimiste et joaillier
            'shared_arcane_craft' => [
                'slug' => 'shared-arcane-craft',
                'title' => 'Savoir arcanique',
                'description' => 'Augmente la puissance des objets a proprietes magiques fabriques',
                'requiredPoints' => 30,
                'domain' => ['alchimist', 'jeweller'],
                'heal' => 1,
                'critical' => 1,
                'requirements' => ['shared_material_saving'],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
}
