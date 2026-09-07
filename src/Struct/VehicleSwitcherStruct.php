<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Struct;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Framework\Struct\Struct;

/**
 * Wird als Extension "vehicleSwitcher" an das Header-Pagelet gehängt
 * und im Twig-Template gerendert.
 */
class VehicleSwitcherStruct extends Struct
{
    /**
     * @param array<string, string> $groupLabels groupId => Überschrift
     * @param list<string>           $groupOrder  groupIds in Anzeige-Reihenfolge
     */
    public function __construct(
        protected PropertyGroupOptionCollection $options,
        protected ?string $activeOptionId,
        protected bool $showAllOption = true,
        protected ?string $allOptionLabel = null,
        protected array $groupLabels = [],
        protected array $groupOrder = [],
        protected string $layout = 'bar'
    ) {
    }

    public function getOptions(): PropertyGroupOptionCollection
    {
        return $this->options;
    }

    public function getActiveOptionId(): ?string
    {
        return $this->activeOptionId;
    }

    public function isShowAllOption(): bool
    {
        return $this->showAllOption;
    }

    public function getAllOptionLabel(): ?string
    {
        return $this->allOptionLabel;
    }

    /**
     * @return array<string, string>
     */
    public function getGroupLabels(): array
    {
        return $this->groupLabels;
    }

    /**
     * @return list<string>
     */
    public function getGroupOrder(): array
    {
        return $this->groupOrder;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getApiAlias(): string
    {
        return 'vehicle_switcher';
    }
}
