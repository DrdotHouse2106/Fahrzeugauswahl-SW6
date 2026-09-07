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
    public function __construct(
        protected PropertyGroupOptionCollection $options,
        protected ?string $activeOptionId,
        protected bool $showGroupLabel = false
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

    public function isShowGroupLabel(): bool
    {
        return $this->showGroupLabel;
    }

    public function getApiAlias(): string
    {
        return 'vehicle_switcher';
    }
}
