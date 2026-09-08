# Fahrzeug-Schnellauswahl (Horizontal Vehicle Switcher)

Schlankes Shopware 6.6+ Plugin für eine globale, horizontale Fahrzeug-Schnellauswahl
im Storefront-Header. Kein Dropdown – Fahrzeuge werden als Kacheln / Pills nebeneinander
angezeigt. Single-Select per Klick, global über alle Kategorien.

> English version: [README.en.md](README.en.md)

## Funktionsweise

| Schritt | Was passiert |
|--------|--------------|
| Datenbasis | Ganz normale Shopware-**Eigenschaften** (Properties). Im Admin werden eine oder zwei „Filtergruppen" (= je eine Eigenschaftsgruppe) ausgewählt. Wie diese befüllt werden – manuell, per Import oder aus einem ERP – ist dem Plugin egal. |
| Anzeige | `HeaderPageletSubscriber` hängt die Optionen der Gruppen als Extension `vehicleSwitcher` an das Header-Pagelet. Twig rendert die Pill-Leiste unter dem Header. |
| Auswahl | JS-Plugin `VehicleSwitcher`: Klick → genau ein Fahrzeug aktiv (kein Stacking). Klick auf das aktive Fahrzeug oder auf „Alle Fahrzeuge" → Auswahl aufgehoben. Wert landet in `localStorage` **und** per POST in der SalesChannel-Session. |
| Globaler Filter | `ProductListingSubscriber` hört auf `ProductListingCriteriaEvent` / `ProductSearchCriteriaEvent` / `ProductSuggestCriteriaEvent` und fügt bei aktivem Fahrzeug `EqualsFilter('product.properties.id', <optionId>)` zum `Criteria` hinzu – für **jede** Kategorie / Suche. |
| Multi-Shop | Feld **„In diesen Verkaufskanälen aktiv"** – eine Mehrfachauswahl. Leiste **und** Filter greifen nur in den gewählten Kanälen. Kein Vererbungs-Gefummel pro Kanal. |

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

1. Bei **„Alle Verkaufskanäle"** (oben) bleiben.
2. Feld **„In diesen Verkaufskanälen aktiv"**: die Verkaufskanäle wählen, in denen die Schnellauswahl erscheinen soll. Leer = nirgends aktiv.
3. Karte **Filtergruppen**: „Filtergruppe 1 – Eigenschaft" wählen, optional „Filtergruppe 2".
4. Speichern.

Die **Beschriftungs- und Darstellungs-Einstellungen** kannst du zusätzlich pro Verkaufskanal überschreiben (oben den Kanal wählen, am Feld die Vererbung lösen). Für Text- und Auswahlfelder funktioniert das problemlos.

### Filtergruppen

| Feld | Wirkung |
|---|---|
| **Filtergruppe 1 / 2 – Eigenschaft** | Die Shopware-Eigenschaft, deren Optionen als Kacheln erscheinen. **Filtergruppe 1 wird zuerst angezeigt, dann Filtergruppe 2** – unabhängig von der Sortierung der Eigenschaftsgruppen in Shopware. Für „zuerst die A-Typen" also die A-Typ-Eigenschaft in Filtergruppe 1 legen. |
| **Filtergruppe 1 / 2 – Überschrift** | Kleiner Text direkt vor den Kacheln dieser Gruppe. **Leer = keine Überschrift** (Standard – es stehen dann nur die Fahrzeugtypen da). Das Feld liegt direkt unter der jeweiligen Gruppe. |

### Darstellung

| Einstellung | Wirkung |
|---|---|
| **Anzeige** | `Alles in einem Balken` (Standard): eine Reihe, erst Gruppe 1, dann Gruppe 2. `Filtergruppen untereinander`: jede Gruppe in einer eigenen Zeile, „Alle"-Kachel in einer Zeile darüber. |
| **Sortierung der Fahrzeuge** | Innerhalb einer Gruppe: `Manuelle Reihenfolge` (Standard) = Drag-&-Drop-Reihenfolge aus der Eigenschaftsgruppe. Alternativ `A–Z` / `Z–A`. |
| **Maximale Anzahl Kacheln** | Obergrenze, falls eine Gruppe sehr viele Optionen hat. |
| **„Alle"-Kachel anzeigen** | Kachel zum Aufheben des Filters. Aus = keine Reset-Kachel (Filter lässt sich weiter durch Klick auf die aktive Kachel aufheben). |
| **Beschriftung der „Alle"-Kachel** | Freier Text, z. B. `Alle` oder `Zurücksetzen`. Leer = Standardtext. |

Die sichtbaren Kacheltexte selbst sind die **Namen der Eigenschafts-Optionen** – die änderst du direkt in Shopware unter *Kataloge → Eigenschaften* (bzw. mehrsprachig je Übersetzung).

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
