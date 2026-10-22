<?php
require __DIR__ . '../../vendor/autoload.php';

use Geensnor\Advies\AdviesCollectie;
use Geensnor\Advies\Locatie;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    if (!isset($_GET['latitude'], $_GET['longitude'])) {
        throw new InvalidArgumentException('Missing latitude or longitude parameters.');
    }

    $latitude = filter_var($_GET['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_GET['longitude'], FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        throw new InvalidArgumentException('Invalid latitude, longitude or limit values.');
    }

    if (isset($_GET['limit'])) {
        $limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT);
        if ($limit === false || $limit <= 0) {
            throw new InvalidArgumentException('Invalid limit value.');
        }
    } else {
        $limit = 1;
    }

    $location = ['latitude' => $latitude, 'longitude' => $longitude, 'limit' => $limit];
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_input', 'message' => $e->getMessage()]);
    exit;
}
http_response_code(200);

$collectie = AdviesCollectie::vanBestand(__DIR__ . '/../data/locations.geojson');
$adviesDichtbij = $collectie->dichtstbijzijnde(new Locatie($location['latitude'], $location['longitude']), $location['limit']);

echo json_encode($adviesDichtbij);
