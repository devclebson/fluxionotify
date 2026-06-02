<?php
$glpi_root = realpath(__DIR__ . '/../../../../');
if (file_exists($glpi_root . "/inc/includes.php")) {
    include_once($glpi_root . "/inc/includes.php");
} else {
    die("Error locating includes.php");
}

try {
    $reflector = new ReflectionMethod('CommonDBTM', 'canCreateItem');
    echo "CommonDBTM::canCreateItem signature:\n";
    echo "Is Static: " . ($reflector->isStatic() ? 'Yes' : 'No') . "\n";
    echo "Return Type: " . $reflector->getReturnType() . "\n";
    echo "Parameters:\n";
    foreach ($reflector->getParameters() as $param) {
        echo "  - " . $param->getName() . "\n";
    }
} catch (Exception $e) {
    echo "Error reflecting method: " . $e->getMessage() . "\n";
}
