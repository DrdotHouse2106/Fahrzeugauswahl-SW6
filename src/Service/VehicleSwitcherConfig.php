<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Liest die (Sales-Channel-spezifische) Plugin-Konfiguration aus.
 */
class VehicleSwitcherConfig
{
    private const PREFIX = 'FahrzeugSchnellauswahl.config.';

    public const SORT_POSITION = 'position';
    public const SORT_NAME_ASC = 'nameAsc';
    public const SORT_NAME_DESC = 'nameDesc';

    public const LAYOUT_BAR = 'bar';
    public const LAYOUT_STACKED = 'stacked';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function isActive(?string $salesChannelId): bool
    {
        if ($salesChannelId === null) {
            return false;
        }

        $ids = $this->systemConfigService->get(self::PREFIX . 'activeSalesChannels', $salesChannelId);

        return \is_array($ids) && \in_array($salesChannelId, $ids, true);
    }

    /**
     * Reihenfolge: erst Gruppe A, dann Gruppe B.
     *
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

    public function getLayout(?string $salesChannelId): string
    {
        $value = $this->systemConfigService->get(self::PREFIX . 'layout', $salesChannelId);

        return $value === self::LAYOUT_STACKED ? self::LAYOUT_STACKED : self::LAYOUT_BAR;
    }

    public function getSortMode(?string $salesChannelId): string
    {
        $value = $this->systemConfigService->get(self::PREFIX . 'sortMode', $salesChannelId);

        return \in_array($value, [self::SORT_POSITION, self::SORT_NAME_ASC, self::SORT_NAME_DESC], true)
            ? $value
            : self::SORT_POSITION;
    }

    public function showAllOption(?string $salesChannelId): bool
    {
        $value = $this->systemConfigService->get(self::PREFIX . 'showAllOption', $salesChannelId);

        // Default: anzeigen (auch wenn der Wert noch nie gesetzt wurde).
        return $value === null ? true : (bool) $value;
    }

    /**
     * Eigene Beschriftung der „Alle"-Kachel. Null = Standardtext (Snippet).
     */
    public function getAllOptionLabel(?string $salesChannelId): ?string
    {
        return $this->trimToNull($this->systemConfigService->get(self::PREFIX . 'allOptionLabel', $salesChannelId));
    }

    /**
     * Optionale Überschrift je konfigurierter Gruppe.
     * Leerer Wert = keine Überschrift.
     *
     * @return array<string, string> groupId => Überschrift
     */
    public function getGroupLabels(?string $salesChannelId): array
    {
        $labels = [];

        $map = [
            'propertyGroupIdA' => 'groupLabelA',
            'propertyGroupIdB' => 'groupLabelB',
        ];

        foreach ($map as $groupKey => $labelKey) {
            $groupId = $this->systemConfigService->get(self::PREFIX . $groupKey, $salesChannelId);
            $label = $this->trimToNull($this->systemConfigService->get(self::PREFIX . $labelKey, $salesChannelId));

            if (\is_string($groupId) && $groupId !== '' && $label !== null) {
                $labels[$groupId] = $label;
            }
        }

        return $labels;
    }

    private function trimToNull(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
