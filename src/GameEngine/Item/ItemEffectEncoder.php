<?php

namespace App\GameEngine\Item;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemEffectEncoder
{
    final public const ACTION_USE_SPELL = 'use_spell';
    final public const ACTION_LEARN_SKILL = 'learn_skill';
    final public const ACTION_BUILD_ITEM = 'build_item';
    /**
     * Ouvrir un arbre (ONB-08).
     *
     * Distinct de `learn_skill`, et c'est tout l'objet du jalon : les trois
     * parchemins livres accordaient **une competence precise**
     * (`{"action":"learn_skill","slug":"miner-copper-xs"}`), ce qui faisait du
     * parchemin un raccourci de progression. Le geste joueur etait le bon, la
     * semantique ne l'etait pas — le parchemin ouvre le champ, il ne le
     * parcourt pas a la place du joueur.
     *
     * Le `slug` designe un **domaine**, tel que `Domain::getSlug()` le derive.
     */
    final public const ACTION_OPEN_DOMAIN = 'open_domain';

    final public const KEY_ACTION = 'action';
    final public const KEY_ID = 'id';
    final public const KEY_SLUG = 'slug';
    final public const KEY_COMPONENTS = 'components';

    public function encodeItemEffect(array $effect): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $effect = $resolver->resolve($effect);

        // Remove null optional keys for cleaner JSON
        $effect = array_filter($effect, fn ($v) => $v !== null);

        return json_encode($effect, JSON_THROW_ON_ERROR);
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(self::KEY_ACTION);

        $resolver->setDefined([self::KEY_ID, self::KEY_SLUG, self::KEY_COMPONENTS]);

        $resolver->setDefault(self::KEY_ID, null);
        $resolver->setDefault(self::KEY_SLUG, null);
        $resolver->setDefault(self::KEY_COMPONENTS, null);

        $resolver->addAllowedTypes(self::KEY_ACTION, ['string']);
        $resolver->addAllowedTypes(self::KEY_ID, ['null', 'integer']);
        $resolver->addAllowedTypes(self::KEY_SLUG, ['null', 'string']);
        $resolver->addAllowedTypes(self::KEY_COMPONENTS, ['null', 'array']);

        $resolver->setAllowedValues(self::KEY_ACTION, [self::ACTION_USE_SPELL, self::ACTION_LEARN_SKILL, self::ACTION_BUILD_ITEM, self::ACTION_OPEN_DOMAIN]);
    }
}
