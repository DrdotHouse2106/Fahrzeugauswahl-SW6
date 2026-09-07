<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Lädt die Fahrzeug-Optionen der konfigurierten Eigenschaftsgruppen.
 */
class VehicleOptionLoader
{
    public function __construct(private readonly EntityRepository $propertyGroupOptionRepository)
    {
    }

    /**
     * @param list<string> $groupIds
     * @param VehicleSwitcherConfig::SORT_* $sortMode
     */
    public function load(array $groupIds, int $limit, string $sortMode, Context $context): PropertyGroupOptionCollection
    {
        if ($groupIds === []) {
            return new PropertyGroupOptionCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('groupId', $groupIds));
        $criteria->addAssociation('group');
        $criteria->setLimit($limit);
        $criteria->setTitle('vehicle-switcher::options');

        // Gruppen bleiben immer als Block zusammen (Reihenfolge = Gruppen-Position).
        $criteria->addSorting(new FieldSorting('group.position', FieldSorting::ASCENDING));

        // Sortierung innerhalb der Gruppe.
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

        /** @var PropertyGroupOptionCollection $result */
        $result = $this->propertyGroupOptionRepository->search($criteria, $context)->getEntities();

        return $result;
    }
}
