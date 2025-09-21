<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Routing\RoutingConfigurator;

$getRoutePaths = static function (): array {
    $prefixes = ['mymoduleprefix'];
    $routes = [];

    foreach (ModuleManager::getInstalledModules() as $module) {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($module['ID'], $prefix . '.')) {
                $route = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $module['ID'] . '/routes/api.php';
                if (file_exists($route)) {
                    $routes[] = $route;
                }
            }
        }
    }

    return $routes;
};

return function (RoutingConfigurator $routingConfigurator) use ($getRoutePaths) {
    foreach ($getRoutePaths() as $route) {
        $callback = include_once $route;

        if ($callback instanceof Closure) {
            $callback($routingConfigurator);
        }
    }
};
