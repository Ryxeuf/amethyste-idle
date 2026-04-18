import { Controller } from '@hotwired/stimulus'

/**
 * Preview temps reel de l'avatar a la creation de personnage (AVT-24).
 *
 * Compose les layers (body, outfit, hair teinte) dans un canvas 64x64 en affichant
 * la frame stand-down (col 0, row 0) de chaque spritesheet Mana Seed. Ecoute les
 * changements des radios body / hair / hairColor / outfit du formulaire.
 *
 * Approche "fallback : images statiques pre-generees" : pas de PixiJS, pas
 * d'animation — uniquement la premiere frame pour guider le choix visuel.
 */
export default class extends Controller {
    static targets = ['canvas']

    static FRAME = 64
    static FIELDS = ['body', 'outfit', 'hair']

    connect() {
        this._imageCache = new Map()
        this._onChange = this._handleChange.bind(this)
        this.element.addEventListener('change', this._onChange)
        this._render()
    }

    disconnect() {
        this.element.removeEventListener('change', this._onChange)
        this._imageCache.clear()
    }

    _handleChange(event) {
        const name = event.target?.name ?? ''
        if (!name) return
        if (!this._isRelevant(name)) return
        this._render()
    }

    _isRelevant(name) {
        return this.constructor.FIELDS.some(
            (field) => name.endsWith(`[${field}]`) || name === field,
        ) || name.endsWith('[hairColor]') || name === 'hairColor'
    }

    _render() {
        if (!this.hasCanvasTarget) return

        const canvas = this.canvasTarget
        const frame = this.constructor.FRAME
        canvas.width = frame
        canvas.height = frame
        const ctx = canvas.getContext('2d')
        if (!ctx) return
        ctx.imageSmoothingEnabled = false
        ctx.clearRect(0, 0, frame, frame)

        const selections = this._readSelections()
        if (selections.body) {
            this._drawSheet(ctx, selections.body)
        }
        if (selections.outfit) {
            this._drawSheet(ctx, selections.outfit)
        }
        if (selections.hair) {
            this._drawSheet(ctx, selections.hair, selections.hairColor)
        }
    }

    _readSelections() {
        const sel = { body: null, hair: null, outfit: null, hairColor: null }
        for (const field of this.constructor.FIELDS) {
            const radio = this._findCheckedRadio(field)
            if (radio) {
                const sheet = radio.getAttribute('data-sheet')
                if (sheet) sel[field] = sheet
            }
        }
        const colorRadio = this._findCheckedRadio('hairColor')
        if (colorRadio) {
            sel.hairColor = colorRadio.value || null
        }
        return sel
    }

    _findCheckedRadio(field) {
        const radios = this.element.querySelectorAll(
            `input[type="radio"][name$="[${field}]"], input[type="radio"][name="${field}"]`,
        )
        for (const radio of radios) {
            if (radio.checked) return radio
        }
        return null
    }

    _drawSheet(ctx, url, tint = null) {
        const frame = this.constructor.FRAME
        const image = this._getImage(url)
        if (!image) return

        if (!tint) {
            ctx.drawImage(image, 0, 0, frame, frame, 0, 0, frame, frame)
            return
        }

        // Tint pipeline: multiply color then re-apply original alpha.
        const temp = document.createElement('canvas')
        temp.width = frame
        temp.height = frame
        const tctx = temp.getContext('2d')
        if (!tctx) return
        tctx.imageSmoothingEnabled = false
        tctx.drawImage(image, 0, 0, frame, frame, 0, 0, frame, frame)
        tctx.globalCompositeOperation = 'multiply'
        tctx.fillStyle = tint
        tctx.fillRect(0, 0, frame, frame)
        tctx.globalCompositeOperation = 'destination-in'
        tctx.drawImage(image, 0, 0, frame, frame, 0, 0, frame, frame)
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
}
