<?php declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * PHP statt XML: Symfony 7.4 / Shopware 6.8 markieren das XML-Routing-Format
 * als deprecated (Meldung bei jedem Route-Cache-Aufbau).
 */
return static function (RoutingConfigurator $routes): void {
    $routes->import(__DIR__ . '/../../Controller/', 'attribute');
};
