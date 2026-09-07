<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Liest die (Sales-Channel-spezifische) Plugin-Konfiguration aus.
 */
class VehicleSwitcherConfig
{
    private const PREFIX = 'FahrzeugSchnellauswahl.config.';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function isActive(?string $salesChannelId): bool
    {
        return (bool) $this->systemConfigService->get(self::PREFIX . 'active', $salesChannelId);
    }

    /**
     * @return list<string>
     */
    public function getPropertyGroupIds(?string $salesChannelId): array
    {
        $ids = [];

        foreach (['propertyGroupIdA', 'propertyGroupIdB'] as $key) {
            $value = $this->systemConfigService->get(self::PREFIX . $key, $salesChannelId);

            if (\is_string($value) && $value !== '') {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    public function getMaxOptions(?string $salesChannelId): int
    {
        $value = (int) $this->systemConfigService->get(self::PREFIX . 'maxOptions', $salesChannelId);

        return $value > 0 ? $value : 60;
    }

    public function showGroupLabel(?string $salesChannelId): bool
    {
        return (bool) $this->systemConfigService->get(self::PREFIX . 'showGroupLabel', $salesChannelId);
    }
}
