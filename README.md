# Fahrzeug-Schnellauswahl (Horizontal Vehicle Switcher)

Schlankes Shopware 6.6+ Plugin für eine globale, horizontale Fahrzeug-Schnellauswahl
im Storefront-Header. Kein Dropdown – Fahrzeuge werden als Kacheln / Pills nebeneinander
angezeigt. Single-Select per Klick, global über alle Kategorien.

> English version: [README.en.md](README.en.md)

## Funktionsweise

| Schritt | Was passiert |
|--------|--------------|
| Datenbasis | Ganz normale Shopware-**Eigenschaften** (Properties). Zwei Eigenschaftsgruppen werden im Admin ausgewählt (z. B. „Geeignet für" / „Geeignet für A-Typ"). Wie diese Gruppen befüllt werden – manuell, per Import oder aus einem ERP – ist dem Plugin egal. |
| Anzeige | `HeaderPageletSubscriber` hängt die Optionen der Gruppen als Extension `vehicleSwitcher` an das Header-Pagelet. Twig rendert die Pill-Leiste unter dem Header. |
| Auswahl | JS-Plugin `VehicleSwitcher`: Klick → genau ein Fahrzeug aktiv (kein Stacking). Klick auf das aktive Fahrzeug oder auf „Alle Fahrzeuge" → Auswahl aufgehoben. Wert landet in `localStorage` **und** per POST in der SalesChannel-Session. |
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
        │   └── routes.php
        ├── snippet/
        │   ├── de_DE/messages.de-DE.json
        │   └── en_GB/messages.en-GB.json        # nur technischer Fallback
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

## Eigenschaften befüllen

Das Plugin liest nur `property_group` / `property_group_option` und die Zuordnung
`product.properties`. Wer die Werte pflegt, ist offen:

- von Hand im Admin (Kataloge → Eigenschaften, dann am Produkt zuweisen),
- per Produkt-Import (CSV / API),
- aus einem ERP (z. B. ERPNext-Multiselect-Feld → Shopware-Eigenschaft) über den
  bestehenden Produkt-Sync.

Sobald die Optionen einer konfigurierten Gruppe an Produkten hängen, erscheinen sie
automatisch als Kacheln.

## Hinweise

- Der Filter greift serverseitig über die Session → nach einem Klick lädt die Seite einmal neu.
- Es ist immer **genau ein** Fahrzeug aktiv oder keins (`""` = „Alle Fahrzeuge").
- Ist die in der Session gespeicherte Option in den aktuell konfigurierten Gruppen nicht
  (mehr) vorhanden, wird sie ignoriert, damit der Kunde nicht in einem leeren Filter feststeckt.
