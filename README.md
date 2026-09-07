# Fahrzeug-Schnellauswahl (Horizontal Vehicle Switcher)

Schlankes Shopware 6.6+ Plugin für eine globale, horizontale Fahrzeug-Schnellauswahl
im Storefront-Header. Kein Dropdown – Fahrzeuge werden als Kacheln / Pills nebeneinander
angezeigt. Single-Select per Klick, global über alle Kategorien.

## Funktionsweise

| Schritt | Was passiert |
|--------|--------------|
| Datenbasis | Shopware-**Eigenschaften** (Properties). Zwei Eigenschaftsgruppen werden im Admin konfiguriert (z.B. „Geeignet für" / „Geeignet für A-Typ"), befüllt aus den ERPNext-Multiselect-Feldern. |
| Anzeige | `HeaderPageletSubscriber` hängt die Optionen der Gruppen als Extension `vehicleSwitcher` an das Header-Pagelet. Twig rendert die Pill-Leiste unter dem Header. |
| Auswahl | JS-Plugin `VehicleSwitcher`: Klick → genau ein Fahrzeug aktiv (kein Stacking). Klick auf aktives Fahrzeug oder auf „Alle Fahrzeuge" → Auswahl aufgehoben. Wert landet in `localStorage` **und** per POST in der SalesChannel-Session. |
| Globaler Filter | `ProductListingSubscriber` hört auf `ProductListingCriteriaEvent` / `ProductSearchCriteriaEvent` / `ProductSuggestCriteriaEvent` und fügt bei aktivem Fahrzeug `EqualsFilter('product.properties.id', <optionId>)` zum `Criteria` hinzu – für **jede** Kategorie / Suche. |
| Multi-Shop | `config.xml` ist Sales-Channel-spezifisch. `active` steuert pro Verkaufskanal, ob Leiste **und** Filter greifen. |

## Verzeichnisstruktur

```
.
├── composer.json
└── src/
    ├── FahrzeugSchnellauswahl.php
    ├── Controller/
    │   └── VehicleSwitcherController.php        # POST /vehicle-switcher/select
    ├── Subscriber/
    │   ├── HeaderPageletSubscriber.php          # Optionen -> Template
    │   └── ProductListingSubscriber.php         # globaler Criteria-Filter
    ├── Service/
    │   ├── VehicleSwitcherConfig.php            # SystemConfig (pro SalesChannel)
    │   ├── VehicleSelectionStorage.php          # Session (Single-Select)
    │   └── VehicleOptionLoader.php              # property_group_option laden
    ├── Struct/
    │   └── VehicleSwitcherStruct.php
    └── Resources/
        ├── config/
        │   ├── config.xml                       # Admin-Konfiguration
        │   ├── services.xml
        │   └── routes.xml
        ├── snippet/
        │   ├── de_DE/messages.de-DE.json
        │   └── en_GB/messages.en-GB.json
        ├── views/storefront/
        │   ├── layout/header/header.html.twig
        │   └── component/vehicle-switcher/vehicle-switcher.html.twig
        └── app/storefront/src/
            ├── main.js
            ├── plugin/vehicle-switcher/vehicle-switcher.plugin.js
            └── scss/base.scss
```

## Installation

```bash
# Plugin nach custom/plugins/FahrzeugSchnellauswahl legen (Ordnername = Plugin-Klassenname)
bin/console plugin:refresh
bin/console plugin:install --activate FahrzeugSchnellauswahl
bin/console cache:clear

# Storefront-Assets bauen
bin/build-storefront.sh          # oder: bin/console theme:compile
```

## Konfiguration (Admin)

Einstellungen → System → Plugins → *Fahrzeug-Schnellauswahl* → *Konfiguration*

1. **Oben den Verkaufskanal auswählen** (nicht „Alle Verkaufskanäle").
2. „In diesem Verkaufskanal aktivieren" einschalten.
3. Eigenschaftsgruppe „Geeignet für" und optional „Geeignet für A-Typ" wählen.
4. Speichern. Für weitere Shops Schritt 1–3 wiederholen; nicht konfigurierte Kanäle bleiben unberührt.

## ERPNext-Anbindung

Das Plugin selbst spricht **nicht** mit ERPNext. Der bestehende Produkt-Sync muss die
ERPNext-Multiselect-Werte in Shopware-Eigenschaften (`property_group_option`) übersetzen
und den Produkten zuweisen. Das Plugin liest anschließend nur noch diese Eigenschaften.

## Hinweise

- Der Filter greift serverseitig über die Session → nach einem Klick lädt die Seite einmal neu.
- Es ist immer **genau ein** Fahrzeug aktiv oder keins (`""` = „Alle Fahrzeuge").
- Ist die in der Session gespeicherte Option in den aktuell konfigurierten Gruppen nicht
  (mehr) vorhanden, wird sie ignoriert, damit der Kunde nicht in einem leeren Filter feststeckt.
