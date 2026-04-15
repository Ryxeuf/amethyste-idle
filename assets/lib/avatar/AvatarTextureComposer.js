import * as PIXI from 'pixi.js';

/**
 * Compose plusieurs layers de sprite sheets alignees sur une seule texture finale.
 *
 * Hypothese MVP alignee sur amethyste-idle :
 * - toutes les layers ont exactement le meme layout legacy RPG Maker
 * - meme largeur / hauteur
 * - meme ancrage visuel
 * - on empile des sheets completes, pas frame par frame
 *
 * API PixiJS v8 : `PIXI.RenderTexture.create({ width, height })` et
 * `renderer.render({ container, target, clear })`.
 */
export default class AvatarTextureComposer {
    constructor({ renderer }) {
        if (!renderer) {
            throw new Error('AvatarTextureComposer requires a PIXI renderer');
        }
        this.renderer = renderer;
    }

    compose({ baseTexture, layers = [] }) {
        if (!baseTexture) {
            throw new Error('AvatarTextureComposer.compose() requires a baseTexture');
        }

        const source = baseTexture.source || baseTexture;
        const width = source.width;
        const height = source.height;

        const renderTexture = PIXI.RenderTexture.create({ width, height });

        const container = new PIXI.Container();

        const baseSprite = new PIXI.Sprite(baseTexture);
        baseSprite.position.set(0, 0);
        container.addChild(baseSprite);

        for (const layer of layers) {
            if (!layer || !layer.texture) {
                continue;
            }

            const sprite = new PIXI.Sprite(layer.texture);
            sprite.position.set(0, 0);

            if (layer.tint !== undefined && layer.tint !== null) {
                sprite.tint = layer.tint;
            }

            if (layer.alpha !== undefined && layer.alpha !== null) {
                sprite.alpha = layer.alpha;
            }

            if (layer.visible === false) {
                sprite.visible = false;
            }

            container.addChild(sprite);
        }

        this.renderer.render({
            container,
            target: renderTexture,
            clear: true,
        });

        container.destroy({ children: true });

        return renderTexture;
    }
}
