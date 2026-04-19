import { Controller } from '@hotwired/stimulus'

/**
 * Paper doll de l'inventaire (AVT-34).
 *
 * Compose le personnage equipe (body + gear + appearance) dans un canvas 64x64
 * en affichant la frame stand-down (col 0, row 0) de chaque spritesheet 8x8
 * Mana Seed. Les layers et le baseSheet proviennent du payload server-side
 * fourni par PlayerAvatarPayloadBuilder::build() (memes regles d'ordre que
 * le rendu carte).
 *
 * Approche identique au character_creator_controller : Canvas2D, pas de
 * dependance PixiJS, support du tint multiply via destination-in.
 */
export default class extends Controller {
    static targets = ['canvas']
    static values = {
        payload: { type: Object, default: null },
    }

    static FRAME = 64
    static FRAME_COL = 0
    static FRAME_ROW = 0

    connect() {
        this._imageCache = new Map()
        this._render()
    }

    disconnect() {
        this._imageCache.clear()
    }

    payloadValueChanged() {
        if (this._imageCache) {
            this._render()
        }
    }

    _render() {
        if (!this.hasCanvasTarget) return
        const payload = this.payloadValue
        if (!payload || !payload.avatar) return

        const canvas = this.canvasTarget
        const frame = this.constructor.FRAME
        canvas.width = frame
        canvas.height = frame
        const ctx = canvas.getContext('2d')
        if (!ctx) return
        ctx.imageSmoothingEnabled = false
        ctx.clearRect(0, 0, frame, frame)

        const baseSheet = payload.avatar.baseSheet
        if (baseSheet) {
            this._drawSheet(ctx, baseSheet, null)
        }

        const layers = Array.isArray(payload.avatar.layers) ? payload.avatar.layers : []
        for (const layer of layers) {
            if (!layer || !layer.sheet) continue
            const tint = typeof layer.tint === 'number' ? this._intToHex(layer.tint) : null
            this._drawSheet(ctx, layer.sheet, tint)
        }
    }

    _drawSheet(ctx, url, tint) {
        const frame = this.constructor.FRAME
        const sx = this.constructor.FRAME_COL * frame
        const sy = this.constructor.FRAME_ROW * frame
        const image = this._getImage(url)
        if (!image) return

        if (!tint) {
            ctx.drawImage(image, sx, sy, frame, frame, 0, 0, frame, frame)
            return
        }

        const temp = document.createElement('canvas')
        temp.width = frame
        temp.height = frame
        const tctx = temp.getContext('2d')
        if (!tctx) return
        tctx.imageSmoothingEnabled = false
        tctx.drawImage(image, sx, sy, frame, frame, 0, 0, frame, frame)
        tctx.globalCompositeOperation = 'multiply'
        tctx.fillStyle = tint
        tctx.fillRect(0, 0, frame, frame)
        tctx.globalCompositeOperation = 'destination-in'
        tctx.drawImage(image, sx, sy, frame, frame, 0, 0, frame, frame)
        ctx.drawImage(temp, 0, 0)
    }

    _getImage(url) {
        const cached = this._imageCache.get(url)
        if (cached) {
            return cached.complete && cached.naturalWidth > 0 ? cached : null
        }
        const image = new Image()
        image.decoding = 'async'
        image.addEventListener('load', () => this._render(), { once: true })
        image.src = url
        this._imageCache.set(url, image)
        return image.complete && image.naturalWidth > 0 ? image : null
    }

    _intToHex(value) {
        const clamped = Math.max(0, Math.min(0xffffff, Math.trunc(value)))
        return '#' + clamped.toString(16).padStart(6, '0')
    }
}
