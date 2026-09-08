<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Liest die aktuell gewählte Fahrzeug-OptionId aus dem Cookie.
 *
 * Bewusst ein Cookie (nicht die Session): Der Wert muss schon beim Aufbau des
 * HTTP-Cache-Keys verfügbar sein – die Session ist zu dem Zeitpunkt nicht
 * zuverlässig gestartet. Gesetzt wird das Cookie clientseitig im Storefront-JS.
 */
class VehicleSelectionStorage
{
    public const COOKIE_NAME = 'vehicle-switcher-option';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function get(): ?string
    {
        $request = $this->requestStack->getMainRequest() ?? $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        $value = $request->cookies->get(self::COOKIE_NAME);

        return \is_string($value) && $value !== '' ? $value : null;
    }
}
