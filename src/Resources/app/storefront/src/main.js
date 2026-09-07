import VehicleSwitcherPlugin from './plugin/vehicle-switcher/vehicle-switcher.plugin';

const PluginManager = window.PluginManager;

PluginManager.register(
    'VehicleSwitcher',
    VehicleSwitcherPlugin,
    '[data-vehicle-switcher]'
);
