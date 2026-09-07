# Vehicle Quick Switcher (Horizontal Vehicle Switcher)

> Die deutsche Fassung ist das Original: [README.md](README.md)

Lightweight Shopware 6.6+ plugin for a global, horizontal vehicle quick switcher in
the storefront header. No dropdown – vehicles are shown as tiles / pills side by side.
Single select per click, applied globally across every category.

## How it works

| Step | What happens |
|------|--------------|
| Data source | Plain Shopware **properties**. Two property groups are picked in the admin (e.g. "Suitable for" / "Suitable for A-type"). How those groups get filled – by hand, via import or from an ERP – is irrelevant to the plugin. |
| Rendering | `HeaderPageletSubscriber` attaches the group options as the `vehicleSwitcher` extension on the header pagelet. Twig renders the pill bar below the header. |
| Selection | JS plugin `VehicleSwitcher`: a click activates exactly one vehicle (no stacking). Clicking the active vehicle or "All vehicles" clears the selection. The value is stored in `localStorage` **and** POSTed into the sales channel session. |
| Global filter | `ProductListingSubscriber` listens on `ProductListingCriteriaEvent` / `ProductSearchCriteriaEvent` / `ProductSuggestCriteriaEvent` and, while a vehicle is active, adds `EqualsFilter('product.properties.id', <optionId>)` to the `Criteria` – for every category / search. |
| Multi shop | `config.xml` is sales-channel specific. `active` controls per sales channel whether the bar **and** the filter apply. |

## Directory layout

```
.
├── composer.json
└── src/
    ├── FahrzeugSchnellauswahl.php
    ├── Controller/
    │   └── VehicleSwitcherController.php        # POST /vehicle-switcher/select
    ├── Subscriber/
    │   ├── HeaderPageletSubscriber.php          # options -> template
    │   └── ProductListingSubscriber.php         # global criteria filter
    ├── Service/
    │   ├── VehicleSwitcherConfig.php            # SystemConfig (per sales channel)
    │   ├── VehicleSelectionStorage.php          # session (single select)
    │   └── VehicleOptionLoader.php              # load property_group_option
    ├── Struct/
    │   └── VehicleSwitcherStruct.php
    └── Resources/
        ├── config/
        │   ├── config.xml                       # admin configuration
        │   ├── services.xml
        │   └── routes.php
        ├── snippet/
        │   ├── de_DE/messages.de-DE.json
        │   └── en_GB/messages.en-GB.json        # technical fallback only
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
# put the plugin in custom/plugins/FahrzeugSchnellauswahl (folder name = plugin class name)
bin/console plugin:refresh
bin/console plugin:install --activate FahrzeugSchnellauswahl
bin/console cache:clear

# build storefront assets
bin/build-storefront.sh          # or: bin/console theme:compile
```

## Configuration (admin)

Settings → System → Plugins → *Fahrzeug-Schnellauswahl* → *Config*

1. **Pick the sales channel at the top** (not "All sales channels").
2. Enable "Enable in this sales channel".
3. Choose the "Suitable for" property group and optionally "Suitable for A-type".
4. Save. Repeat steps 1–3 for further shops; sales channels left unconfigured stay untouched.

All settings below are configurable **per sales channel**.

### Appearance & labelling

| Setting | Effect |
|---|---|
| **Vehicle sorting** | `Manual order` (default) = exactly the order you set in the property group via drag & drop. Or `A–Z` / `Z–A`. Multiple groups always stay as one block (order = group position). |
| **Maximum number of pills** | Upper limit for groups with many options. |
| **Show "all" pill** | The reset pill. Off = no reset pill (the filter can still be cleared by clicking the active pill). |
| **"All" pill label** | Free text, e.g. `All` or `Reset`. Empty = default text. |
| **Heading before group A / B** | Small text left of that group's pills. **Empty = no heading** (default – just the vehicle types). |

The pill captions themselves are the **names of the property options** – edit them directly in Shopware under *Catalogues → Properties* (per translation for multiple languages).

## Filling the properties

The plugin only reads `property_group` / `property_group_option` and the
`product.properties` assignment. Who maintains the values is up to you:

- manually in the admin (Catalogues → Properties, then assign on the product),
- via product import (CSV / API),
- from an ERP (e.g. an ERPNext multiselect field → Shopware property) through your
  existing product sync.

Once the options of a configured group are assigned to products, they show up as
pills automatically.

## Notes

- The filter is applied server-side via the session → the page reloads once after a click.
- There is always **exactly one** vehicle active or none (`""` = "All vehicles").
- If the option stored in the session is not (or no longer) part of the currently
  configured groups it is ignored, so the customer never gets stuck in an empty filter.
