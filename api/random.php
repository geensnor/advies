<?php
require __DIR__ . '../../vendor/autoload.php';

use Geensnor\Advies\AdviesCollectie;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');


$collectie = AdviesCollectie::vanBestand(__DIR__ . '/../data/locations.geojson');
$randomAdvies = $collectie->geefRandom();

echo json_encode($randomAdvies);
