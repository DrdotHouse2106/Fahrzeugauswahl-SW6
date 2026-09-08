import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Horizontal Vehicle Switcher
 *
 * - Single-Select per Klick (kein Stacking): ein Klick aktiviert genau ein Fahrzeug,
 *   ein Klick auf das aktive Fahrzeug (oder auf "Alle Fahrzeuge") hebt die Auswahl auf.
 * - Schreibt die optionId in LocalStorage (schnelle UI / Cross-Tab) und via POST in die Session.
 * - Nach der Serverantwort wird die Seite neu geladen, damit der globale
 *   ProductListing-Filter greift.
 *
 * Die Klick-Behandlung läuft über EIN delegiertes Listener auf `document`.
 * Grund: Themes (z. B. Eightwork Lumina) klonen oder re-rendern den Header
 * für Sticky-/Transparent-Effekte. Direkt an die Kacheln gebundene Listener
 * gehen dabei verloren – Delegation auf document überlebt das.
 */

let globalClickBound = false;
let busy = false;

export default class VehicleSwitcherPlugin extends Plugin {
    static options = {
        selectRoute: '/vehicle-switcher/select',
        activeClass: 'is-active',
        storageKey: 'vehicleSwitcherOptionId',
        loadingClass: 'vehicle-switcher--loading',
    };

    init() {
        this._client = new HttpClient();
        this._syncLocalStorage();

        if (!globalClickBound) {
            document.addEventListener('click', VehicleSwitcherPlugin._onDocumentClick);
            globalClickBound = true;
        }
    }

    static _onDocumentClick(event) {
        const item = event.target.closest('[data-vehicle-switcher-item]');
        if (!item) {
            return;
        }

        const container = item.closest('[data-vehicle-switcher]');
        if (!container) {
            return;
        }

        event.preventDefault();

        if (busy) {
            return;
        }

        const opts = VehicleSwitcherPlugin._readOptions(container);
        const optionId = item.getAttribute('data-option-id') || '';
        const isActive = item.classList.contains(opts.activeClass);
        const nextOptionId = isActive ? '' : optionId;

        VehicleSwitcherPlugin._setActiveState(nextOptionId, opts.activeClass);
        VehicleSwitcherPlugin._persist(nextOptionId, opts.storageKey);
        VehicleSwitcherPlugin._submit(nextOptionId, opts);
    }

    static _readOptions(container) {
        const fallback = {
            selectRoute: '/vehicle-switcher/select',
            activeClass: 'is-active',
            storageKey: 'vehicleSwitcherOptionId',
        };

        try {
            const raw = container.getAttribute('data-vehicle-switcher-options');
            return { ...fallback, ...(raw ? JSON.parse(raw) : {}) };
        } catch (e) {
            return fallback;
        }
    }

    static _submit(optionId, opts) {
        busy = true;

        document.querySelectorAll('[data-vehicle-switcher]').forEach((el) => {
            el.classList.add('vehicle-switcher--loading');
        });

        new HttpClient().post(
            opts.selectRoute,
            JSON.stringify({ optionId }),
            () => window.location.reload(),
        );
    }

    /**
     * Setzt die active-Klasse sofort (optimistic UI) auf ALLEN im DOM
     * vorhandenen Kopien der Leiste – z. B. Original + Sticky-Klon.
     */
    static _setActiveState(optionId, activeClass) {
        document.querySelectorAll('[data-vehicle-switcher-item]').forEach((item) => {
            const shouldBeActive = (item.getAttribute('data-option-id') || '') === optionId;
            item.classList.toggle(activeClass, shouldBeActive);
            item.setAttribute('aria-pressed', shouldBeActive ? 'true' : 'false');
        });
    }

    static _persist(optionId, storageKey) {
        try {
            if (optionId) {
                window.localStorage.setItem(storageKey, optionId);
            } else {
                window.localStorage.removeItem(storageKey);
            }
        } catch (e) {
            // Private mode / disabled storage -> Session reicht als Fallback.
        }
    }

    /**
     * Falls ein anderer Tab die Auswahl geändert hat und diese von der
     * serverseitig gerenderten Auswahl abweicht: einmalig nachziehen.
     */
    _syncLocalStorage() {
        let stored = null;

        try {
            stored = window.localStorage.getItem(this.options.storageKey);
        } catch (e) {
            return;
        }

        const items = Array.from(this.el.querySelectorAll('[data-vehicle-switcher-item]'));
        if (items.length === 0) {
            return;
        }

        const domActive = items.find((item) => item.classList.contains(this.options.activeClass));
        const domActiveId = domActive ? (domActive.getAttribute('data-option-id') || '') : '';
        const knownIds = items.map((item) => item.getAttribute('data-option-id') || '');

        if (stored && stored !== domActiveId && knownIds.indexOf(stored) !== -1 && !busy) {
            VehicleSwitcherPlugin._setActiveState(stored, this.options.activeClass);
            VehicleSwitcherPlugin._submit(stored, {
                selectRoute: this.options.selectRoute,
            });
        }
    }
}
