<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Persistiert die aktuell gewählte Fahrzeug-OptionId in der SalesChannel-Session.
 * Es gibt bewusst nur EINEN Wert (Single-Select).
 */
class VehicleSelectionStorage
{
    public const SESSION_KEY = 'vehicleSwitcherOptionId';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function get(): ?string
    {
        $session = $this->getSession();

        if (!$session instanceof SessionInterface) {
            return null;
        }

        $value = $session->get(self::SESSION_KEY);

        return \is_string($value) && $value !== '' ? $value : null;
    }

    public function set(?string $optionId): void
    {
        $session = $this->getSession();

        if (!$session instanceof SessionInterface) {
            return;
        }

        if ($optionId === null || $optionId === '') {
            $session->remove(self::SESSION_KEY);

            return;
        }

        $session->set(self::SESSION_KEY, $optionId);
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
