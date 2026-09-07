import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Horizontal Vehicle Switcher
 *
 * - Single-Select per Klick (kein Stacking): ein Klick aktiviert genau ein Fahrzeug,
 *   ein Klick auf das aktive Fahrzeug (oder auf "Alle Fahrzeuge") hebt die Auswahl auf.
 * - Schreibt die optionId in LocalStorage (schnelle UI / Cross-Tab) und via POST in die Session.
 * - Nach dem Serverantwort-Callback wird die Seite neu geladen, damit der globale
 *   ProductListing-Filter greift.
 */
export default class VehicleSwitcherPlugin extends Plugin {
    static options = {
        selectRoute: '',
        activeClass: 'is-active',
        itemSelector: '[data-vehicle-switcher-item]',
        storageKey: 'vehicleSwitcherOptionId',
        loadingClass: 'vehicle-switcher--loading',
    };

    init() {
        if (!this.options.selectRoute) {
            return;
        }

        this._client = new HttpClient();
        this._items = Array.from(this.el.querySelectorAll(this.options.itemSelector));
        this._busy = false;

        this._syncLocalStorage();
        this._registerEvents();
    }

    _registerEvents() {
        this._items.forEach((item) => {
            item.addEventListener('click', this._onItemClick.bind(this));
        });
    }

    _onItemClick(event) {
        event.preventDefault();

        if (this._busy) {
            return;
        }

        const target = event.currentTarget;
        const optionId = target.getAttribute('data-option-id') || '';
        const isActive = target.classList.contains(this.options.activeClass);

        // Klick auf bereits aktives Fahrzeug -> abwählen ("Show All").
        const nextOptionId = isActive ? '' : optionId;

        this._setActiveState(nextOptionId);
        this._persist(nextOptionId);
        this._submit(nextOptionId);
    }

    _submit(optionId) {
        this._busy = true;
        this.el.classList.add(this.options.loadingClass);
        this.$emitter.publish('beforeChange', { optionId });

        this._client.post(
            this.options.selectRoute,
            JSON.stringify({ optionId }),
            () => {
                this.$emitter.publish('change', { optionId });
                window.location.reload();
            },
        );
    }

    /**
     * Setzt die active-Klasse rein visuell sofort (optimistic UI),
     * bevor der Reload kommt.
     */
    _setActiveState(optionId) {
        this._items.forEach((item) => {
            const itemOption = item.getAttribute('data-option-id') || '';
            const shouldBeActive = itemOption === optionId;

            item.classList.toggle(this.options.activeClass, shouldBeActive);
            item.setAttribute('aria-pressed', shouldBeActive ? 'true' : 'false');
        });
    }

    _persist(optionId) {
        try {
            if (optionId) {
                window.localStorage.setItem(this.options.storageKey, optionId);
            } else {
                window.localStorage.removeItem(this.options.storageKey);
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

        const domActive = this._items.find((item) => item.classList.contains(this.options.activeClass));
        const domActiveId = domActive ? (domActive.getAttribute('data-option-id') || '') : '';

        // "" == "Alle Fahrzeuge" ist ein valider Zustand -> nur bei echtem Wert nachziehen.
        if (stored && stored !== domActiveId && this._items.some((i) => i.getAttribute('data-option-id') === stored)) {
            this._setActiveState(stored);
            this._submit(stored);
        }
    }
}
