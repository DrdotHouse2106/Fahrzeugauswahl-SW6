import Plugin from 'src/plugin-system/plugin.class';

/**
 * Horizontal Vehicle Switcher
 *
 * - Single-Select per Klick (kein Stacking): ein Klick aktiviert genau ein Fahrzeug,
 *   ein Klick auf das aktive Fahrzeug (oder auf "Alle Fahrzeuge") hebt die Auswahl auf.
 * - Die Auswahl wird in ein Cookie geschrieben (serverseitig gelesen für Filter +
 *   HTTP-Cache-Key) und zusätzlich in localStorage gespiegelt. Danach ein Reload.
 * - Kein AJAX/Route nötig – das Cookie ist beim nächsten Request sofort da.
 *
 * Klick-Behandlung über EIN delegiertes Listener auf `document` – robust auch wenn
 * ein Theme (z. B. Eightwork Lumina) den Header klont oder neu rendert.
 */

const COOKIE_NAME = 'vehicle-switcher-option';
const STORAGE_KEY = 'vehicleSwitcherOptionId';
const ACTIVE_CLASS = 'is-active';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 Tage

let globalClickBound = false;
let busy = false;

export default class VehicleSwitcherPlugin extends Plugin {
    init() {
        this._alignMirrors();

        if (!globalClickBound) {
            document.addEventListener('click', VehicleSwitcherPlugin._onDocumentClick);
            globalClickBound = true;
        }
    }

    static _onDocumentClick(event) {
        const item = event.target.closest('[data-vehicle-switcher-item]');
        if (!item || !item.closest('[data-vehicle-switcher]')) {
            return;
        }

        event.preventDefault();

        if (busy) {
            return;
        }
        busy = true;

        const optionId = item.getAttribute('data-option-id') || '';
        const isActive = item.classList.contains(ACTIVE_CLASS);
        const nextOptionId = isActive ? '' : optionId;

        VehicleSwitcherPlugin._setActiveState(nextOptionId);
        VehicleSwitcherPlugin._write(nextOptionId);

        document.querySelectorAll('[data-vehicle-switcher]').forEach((el) => {
            el.classList.add('vehicle-switcher--loading');
        });

        window.location.reload();
    }

    /**
     * Optimistic UI: active-Klasse sofort auf ALLEN Kopien der Leiste im DOM.
     */
    static _setActiveState(optionId) {
        document.querySelectorAll('[data-vehicle-switcher-item]').forEach((item) => {
            const on = (item.getAttribute('data-option-id') || '') === optionId;
            item.classList.toggle(ACTIVE_CLASS, on);
            item.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    static _write(optionId) {
        const secure = window.location.protocol === 'https:' ? '; secure' : '';

        if (optionId) {
            document.cookie = `${COOKIE_NAME}=${encodeURIComponent(optionId)}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax${secure}`;
        } else {
            document.cookie = `${COOKIE_NAME}=; path=/; max-age=0; samesite=lax${secure}`;
        }

        try {
            if (optionId) {
                window.localStorage.setItem(STORAGE_KEY, optionId);
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            // Private mode / disabled storage -> Cookie reicht.
        }
    }

    /**
     * Cookie + localStorage an den serverseitig gerenderten Zustand angleichen.
     * NUR schreiben – niemals ein Reload auslösen (sonst Schleifen bei aktivem
     * HTTP-Cache).
     */
    _alignMirrors() {
        const items = document.querySelectorAll('[data-vehicle-switcher-item]');
        if (items.length === 0) {
            return;
        }

        let activeId = '';
        items.forEach((item) => {
            if (item.classList.contains(ACTIVE_CLASS)) {
                activeId = item.getAttribute('data-option-id') || '';
            }
        });

        VehicleSwitcherPlugin._write(activeId);
    }
}
