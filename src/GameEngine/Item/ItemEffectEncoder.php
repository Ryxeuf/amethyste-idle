<?php

namespace App\GameEngine\Item;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemEffectEncoder
{
    final public const ACTION_USE_SPELL = 'use_spell';
    final public const ACTION_LEARN_SKILL = 'learn_skill';
    final public const ACTION_BUILD_ITEM = 'build_item';

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

        $resolver->setAllowedValues(self::KEY_ACTION, [self::ACTION_USE_SPELL, self::ACTION_LEARN_SKILL, self::ACTION_BUILD_ITEM]);
    }
}
