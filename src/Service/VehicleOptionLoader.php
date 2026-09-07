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
     */
    public function load(array $groupIds, int $limit, Context $context): PropertyGroupOptionCollection
    {
        if ($groupIds === []) {
            return new PropertyGroupOptionCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('groupId', $groupIds));
        $criteria->addAssociation('group');
        $criteria->addSorting(new FieldSorting('group.position'));
        $criteria->addSorting(new FieldSorting('position'));
        $criteria->addSorting(new FieldSorting('name'));
        $criteria->setLimit($limit);
        $criteria->setTitle('vehicle-switcher::options');

        /** @var PropertyGroupOptionCollection $result */
        $result = $this->propertyGroupOptionRepository->search($criteria, $context)->getEntities();

        return $result;
    }
}
