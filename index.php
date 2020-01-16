<?php
// ============================
// ==  Bootstrap app script  ==
// ============================

/**
 * Classes autoloader
 */
spl_autoload_register(function ($class) {
    require_once('Classes/' . $class . '.php');
});

/**
 * Get sources config from file
 */
include 'config/sources.php';

/**
 * Initialize vars and set default values
 */
$totalVisits = [];
$error = false;
$errorMsg = '';

try {
    $stats = new Stats($setup);
    $totalVisits = $stats->getTotalNumberOfVisits();
} catch (\Exception $e) {
    $error = true;
    $errorMsg = $e->getMessage();
}

$response = [
    'error' => $error,
    'message' => $errorMsg,
    'data' => $totalVisits,
];

return (json_encode($response));
