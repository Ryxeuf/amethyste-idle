import { Controller } from '@hotwired/stimulus';

/**
 * Virtual joystick controller for mobile touch input.
 * Renders a semi-transparent joystick overlay on touch devices.
 * Dispatches directional movement events to the map controller.
 */
export default class extends Controller {
    static values = {
        deadzone: { type: Number, default: 15 },
        size: { type: Number, default: 120 },
    };

    connect() {
        this._active = false;
        this._touchId = null;
        this._direction = null;
        this._repeatTimer = null;

        // Only show on touch devices
        if (!this._isTouchDevice()) return;

        this._createJoystick();
        this._bindEvents();
    }

    disconnect() {
        this._stopRepeat();
        if (this._el) {
            this._el.remove();
            this._el = null;
        }
    }

    _isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    _createJoystick() {
        const size = this.sizeValue;
        const half = size / 2;

        // Outer ring
        this._el = document.createElement('div');
        Object.assign(this._el.style, {
            position: 'absolute',
            bottom: '16px',
            left: '16px',
            width: `${size}px`,
            height: `${size}px`,
            borderRadius: '50%',
            border: '2px solid rgba(168, 85, 247, 0.5)',
            backgroundColor: 'rgba(17, 24, 39, 0.6)',
            zIndex: '50',
            touchAction: 'none',
            pointerEvents: 'auto',
        });

        // Inner knob
        this._knob = document.createElement('div');
        const knobSize = size * 0.4;
        Object.assign(this._knob.style, {
            position: 'absolute',
            width: `${knobSize}px`,
            height: `${knobSize}px`,
            borderRadius: '50%',
            backgroundColor: 'rgba(168, 85, 247, 0.7)',
            top: `${half - knobSize / 2}px`,
            left: `${half - knobSize / 2}px`,
            transition: 'none',
        });
        this._el.appendChild(this._knob);

        // Direction indicators
        const arrows = [
            { char: '▲', top: '6px', left: '50%', transform: 'translateX(-50%)' },
            { char: '▼', bottom: '6px', left: '50%', transform: 'translateX(-50%)' },
            { char: '◀', left: '6px', top: '50%', transform: 'translateY(-50%)' },
            { char: '▶', right: '6px', top: '50%', transform: 'translateY(-50%)' },
        ];
        for (const a of arrows) {
            const span = document.createElement('span');
            span.textContent = a.char;
            Object.assign(span.style, {
                position: 'absolute',
                color: 'rgba(168, 85, 247, 0.4)',
                fontSize: '10px',
                pointerEvents: 'none',
                ...a,
            });
            this._el.appendChild(span);
        }

        this.element.appendChild(this._el);
    }

    _bindEvents() {
        this._el.addEventListener('pointerdown', (e) => this._onStart(e), { passive: false });
        this._el.addEventListener('pointermove', (e) => this._onMove(e), { passive: false });
        this._el.addEventListener('pointerup', (e) => this._onEnd(e));
        this._el.addEventListener('pointercancel', (e) => this._onEnd(e));
        this._el.addEventListener('pointerleave', (e) => this._onEnd(e));
    }

    _onStart(e) {
        e.preventDefault();
        e.stopPropagation();
        this._active = true;
        this._touchId = e.pointerId;
        this._el.setPointerCapture(e.pointerId);
        this._processPointer(e);
    }

    _onMove(e) {
        if (!this._active || e.pointerId !== this._touchId) return;
        e.preventDefault();
        e.stopPropagation();
        this._processPointer(e);
    }

    _onEnd(e) {
        if (!this._active || e.pointerId !== this._touchId) return;
        this._active = false;
        this._touchId = null;
        this._direction = null;
        this._stopRepeat();
        this._resetKnob();
    }

    _processPointer(e) {
        const rect = this._el.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const dx = e.clientX - cx;
        const dy = e.clientY - cy;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const maxDist = this.sizeValue / 2;

        // Move knob visually (clamped to circle)
        const clampedDist = Math.min(dist, maxDist);
        const angle = Math.atan2(dy, dx);
        const knobX = Math.cos(angle) * clampedDist;
        const knobY = Math.sin(angle) * clampedDist;
        const knobSize = this.sizeValue * 0.4;
        this._knob.style.left = `${maxDist + knobX - knobSize / 2}px`;
        this._knob.style.top = `${maxDist + knobY - knobSize / 2}px`;

        if (dist < this.deadzoneValue) {
            this._direction = null;
            this._stopRepeat();
            return;
        }

        // Determine direction (4-way)
        let newDir;
        if (Math.abs(dx) > Math.abs(dy)) {
            newDir = dx > 0 ? 'right' : 'left';
        } else {
            newDir = dy > 0 ? 'down' : 'up';
        }

        if (newDir !== this._direction) {
            this._direction = newDir;
            this._stopRepeat();
            this._emitMove(newDir);
            // Repeat movement while held
            this._repeatTimer = setInterval(() => {
                if (this._direction === newDir) {
                    this._emitMove(newDir);
                }
            }, 200);
        }
    }

    _emitMove(direction) {
        this.dispatch('move', { detail: { direction } });
    }

    _resetKnob() {
        const half = this.sizeValue / 2;
        const knobSize = this.sizeValue * 0.4;
        this._knob.style.left = `${half - knobSize / 2}px`;
        this._knob.style.top = `${half - knobSize / 2}px`;
    }

    _stopRepeat() {
        if (this._repeatTimer) {
            clearInterval(this._repeatTimer);
            this._repeatTimer = null;
        }
    }
}
