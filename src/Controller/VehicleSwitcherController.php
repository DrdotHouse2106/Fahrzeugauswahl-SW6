<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Ulber\FahrzeugSchnellauswahl\Service\VehicleSelectionStorage;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class VehicleSwitcherController extends StorefrontController
{
    public function __construct(private readonly VehicleSelectionStorage $selectionStorage)
    {
    }

    /**
     * Setzt (oder leert) die aktive Fahrzeug-OptionId in der Session.
     * Single-Select: ein neuer Wert ersetzt den alten, leerer Wert = "Alle anzeigen".
     */
    #[Route(
        path: '/vehicle-switcher/select',
        name: 'frontend.vehicle-switcher.select',
        methods: ['POST'],
        defaults: [
            'XmlHttpRequest' => true,
            '_loginRequired' => false,
        ]
    )]
    public function select(Request $request, SalesChannelContext $context): JsonResponse
    {
        $optionId = $request->request->get('optionId');

        if (!\is_string($optionId)) {
            $payload = json_decode((string) $request->getContent(), true);
            $optionId = \is_array($payload) ? ($payload['optionId'] ?? null) : null;
        }

        $optionId = \is_string($optionId) && $optionId !== '' ? $optionId : null;

        $this->selectionStorage->set($optionId);

        return new JsonResponse([
            'success' => true,
            'activeOptionId' => $this->selectionStorage->get(),
        ]);
    }
}
