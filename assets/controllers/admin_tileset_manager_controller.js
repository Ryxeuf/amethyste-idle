import { Controller } from '@hotwired/stimulus'

/**
 * Admin Tileset Manager — navigateur d'images et formulaire d'ajout de tileset.
 */
export default class extends Controller {
    static values = {
        browseUrl: String,
        previewUrl: String,
    }

    static targets = [
        'nameInput', 'imagePathInput', 'columnsInput', 'tileWidthInput', 'tileHeightInput',
        'previewInfo', 'searchInput', 'imageCount', 'imageList',
        'selectedPreview', 'previewImg', 'previewPath', 'previewDims', 'previewSuggestion',
        'zoomModal', 'zoomImg', 'zoomTitle',
    ]

    _allImages = []

    connect() {
        // Auto-load images on connect
        this.loadImages()
    }

    async loadImages() {
        this.imageListTarget.innerHTML = '<p class="text-gray-500 text-sm">Chargement...</p>'

        try {
            const res = await fetch(this.browseUrlValue)
            const data = await res.json()
            this._allImages = data.images || []
            this.imageCountTarget.textContent = `${this._allImages.length} images trouvees`
            this._renderImageList(this._allImages)
        } catch (e) {
            this.imageListTarget.innerHTML = '<p class="text-red-400 text-sm">Erreur lors du chargement.</p>'
        }
    }

    filterImages() {
        const query = this.searchInputTarget.value.toLowerCase().trim()
        if (!query) {
            this._renderImageList(this._allImages)
            return
        }

        const filtered = this._allImages.filter(img =>
            img.path.toLowerCase().includes(query) ||
            img.filename.toLowerCase().includes(query) ||
            img.directory.toLowerCase().includes(query)
        )
        this._renderImageList(filtered)
    }

    _renderImageList(images) {
        const container = this.imageListTarget
        container.innerHTML = ''

        if (images.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-sm">Aucune image trouvee.</p>'
            return
        }

        // Group by directory
        const groups = {}
        for (const img of images) {
            const dir = img.directory || '.'
            if (!groups[dir]) groups[dir] = []
            groups[dir].push(img)
        }

        const dirs = Object.keys(groups).sort()

        for (const dir of dirs) {
            const details = document.createElement('details')
            details.className = 'border border-gray-800 rounded mb-1'

            const summary = document.createElement('summary')
            summary.className = 'px-3 py-2 cursor-pointer text-xs font-semibold text-gray-400 hover:bg-gray-800 select-none'
            summary.textContent = `${dir}/ (${groups[dir].length})`
            details.appendChild(summary)

            const list = document.createElement('div')
            list.className = 'px-3 pb-2 space-y-0.5'

            for (const img of groups[dir]) {
                const row = document.createElement('div')
                row.className = 'flex items-center gap-2 px-2 py-1 rounded cursor-pointer hover:bg-gray-700/50 text-xs text-gray-300 transition-colors'
                row.innerHTML = `
                    <span class="truncate flex-1" title="${img.path}">${img.filename}</span>
                    <span class="text-gray-500 flex-shrink-0">${img.width}x${img.height}</span>
                    <span class="text-gray-600 flex-shrink-0">${img.sizeKb}KB</span>
                `
                row.addEventListener('click', () => this._selectImage(img))
                list.appendChild(row)
            }

            details.appendChild(list)
            container.appendChild(details)
        }
    }

    openZoom(event) {
        const src = event.currentTarget.dataset.zoomSrc
        const title = event.currentTarget.dataset.zoomTitle || ''
        this._showZoom(src, title)
    }

    openZoomFromPreview() {
        const src = this.previewImgTarget.src
        if (!src) return
        const title = this.previewPathTarget.textContent || ''
        this._showZoom(src, title)
    }

    closeZoom() {
        this.zoomModalTarget.classList.add('hidden')
    }

    _showZoom(src, title) {
        this.zoomImgTarget.src = src
        this.zoomTitleTarget.textContent = title
        this.zoomModalTarget.classList.remove('hidden')
    }

    async _selectImage(img) {
        // Fill form fields
        this.imagePathInputTarget.value = img.path

        // Auto-generate name from filename
        const name = img.filename
            .replace(/\.png$/i, '')
            .replace(/[^a-zA-Z0-9_-]/g, '_')
            .toLowerCase()
        this.nameInputTarget.value = name

        // Calculate columns based on tile width
        const tileWidth = parseInt(this.tileWidthInputTarget.value, 10) || 32
        const tileHeight = parseInt(this.tileHeightInputTarget.value, 10) || 32
        const cols = Math.floor(img.width / tileWidth)
        const rows = Math.floor(img.height / tileHeight)
        this.columnsInputTarget.value = cols

        // Show preview
        this.selectedPreviewTarget.classList.remove('hidden')
        this.previewImgTarget.src = `/assets/styles/images/${img.path}`
        this.previewPathTarget.textContent = img.path
        this.previewDimsTarget.textContent = `${img.width}x${img.height}px`
        this.previewSuggestionTarget.textContent = `${cols} colonnes x ${rows} lignes = ${cols * rows} tiles (${tileWidth}x${tileHeight})`

        // Show info in form
        this.previewInfoTarget.classList.remove('hidden')
        this.previewInfoTarget.innerHTML = `
            <p>Image: <strong>${img.width}x${img.height}px</strong></p>
            <p>Avec tiles ${tileWidth}x${tileHeight}: <strong>${cols} col x ${rows} lignes = ${cols * rows} tiles</strong></p>
        `
    }
}
