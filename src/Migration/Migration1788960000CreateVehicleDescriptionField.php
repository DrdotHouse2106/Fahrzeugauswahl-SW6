<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Legt ein Custom-Field an `property_group_option` an:
 * "vehicle_switcher_description" (HTML-Editor). Dort trägt man pro Fahrzeug
 * die Spezifikation / Modellgeschichte ein, die im Storefront angezeigt wird,
 * sobald das Fahrzeug in der Schnellauswahl aktiv ist.
 */
class Migration1788960000CreateVehicleDescriptionField extends MigrationStep
{
    public const CUSTOM_FIELD_NAME = 'vehicle_switcher_description';

    public function getCreationTimestamp(): int
    {
        return 1788960000;
    }

    public function update(Connection $connection): void
    {
        $existing = $connection->fetchOne(
            'SELECT id FROM custom_field_set WHERE name = :name',
            ['name' => 'vehicle_switcher']
        );

        if ($existing !== false) {
            return;
        }

        $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $setId = Uuid::randomBytes();

        $connection->insert('custom_field_set', [
            'id' => $setId,
            'name' => 'vehicle_switcher',
            'config' => json_encode([
                'label' => [
                    'de-DE' => 'Fahrzeug-Schnellauswahl',
                    'en-GB' => 'Vehicle quick switcher',
                ],
                'translated' => true,
            ], \JSON_THROW_ON_ERROR),
            'active' => 1,
            'created_at' => $now,
        ]);

        $connection->insert('custom_field_set_relation', [
            'id' => Uuid::randomBytes(),
            'set_id' => $setId,
            'entity_name' => 'property_group_option',
            'created_at' => $now,
        ]);

        $connection->insert('custom_field', [
            'id' => Uuid::randomBytes(),
            'name' => self::CUSTOM_FIELD_NAME,
            'type' => 'html',
            'config' => json_encode([
                'label' => [
                    'de-DE' => 'Fahrzeug-Beschreibung (Spezifikation)',
                    'en-GB' => 'Vehicle description (specification)',
                ],
                'helpText' => [
                    'de-DE' => 'Wird im Storefront angezeigt, wenn dieses Fahrzeug in der Schnellauswahl aktiv ist.',
                    'en-GB' => 'Shown in the storefront when this vehicle is active in the quick switcher.',
                ],
                'componentName' => 'sw-text-editor',
                'customFieldType' => 'textEditor',
                'customFieldPosition' => 1,
            ], \JSON_THROW_ON_ERROR),
            'active' => 1,
            'set_id' => $setId,
            'created_at' => $now,
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Custom-Field + Inhalte bewusst NICHT automatisch löschen.
    }
}
