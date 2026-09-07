<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Lädt die Fahrzeug-Optionen der konfigurierten Filtergruppen.
 * Die Reihenfolge der Gruppen entspricht der Konfigurations-Reihenfolge
 * (Filtergruppe 1, dann Filtergruppe 2) – nicht der property_group.position.
 */
class VehicleOptionLoader
{
    public function __construct(private readonly EntityRepository $propertyGroupOptionRepository)
    {
    }

    /**
     * @param list<string>                 $groupIds Gruppen in gewünschter Anzeige-Reihenfolge
     * @param VehicleSwitcherConfig::SORT_* $sortMode
     */
    public function load(array $groupIds, int $limit, string $sortMode, Context $context): PropertyGroupOptionCollection
    {
        if ($groupIds === []) {
            return new PropertyGroupOptionCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('groupId', $groupIds));
        $criteria->setLimit(max($limit, 1));
        $criteria->setTitle('vehicle-switcher::options');

        // Sortierung innerhalb einer Gruppe.
        match ($sortMode) {
            VehicleSwitcherConfig::SORT_NAME_ASC => $criteria->addSorting(
                new FieldSorting('name', FieldSorting::ASCENDING)
            ),
            VehicleSwitcherConfig::SORT_NAME_DESC => $criteria->addSorting(
                new FieldSorting('name', FieldSorting::DESCENDING)
            ),
            // SORT_POSITION: manuelle Reihenfolge aus der Eigenschaftsgruppe,
            // Name nur als Tie-Breaker bei gleicher Position.
            default => $criteria
                ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING))
                ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING)),
        };

        /** @var PropertyGroupOptionCollection $found */
        $found = $this->propertyGroupOptionRepository->search($criteria, $context)->getEntities();

        // Nach Gruppen-Reihenfolge umsortieren; die DAL-Sortierung bleibt
        // innerhalb jeder Gruppe erhalten.
        $ordered = new PropertyGroupOptionCollection();

        foreach ($groupIds as $groupId) {
            foreach ($found as $option) {
                if ($option->getGroupId() === $groupId) {
                    $ordered->add($option);
                }
            }
        }

        return $ordered;
    }
}
