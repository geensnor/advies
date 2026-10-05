<?php
// Suppress PHP warnings/notices so the API always returns clean JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Edit-Password');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $configFile = __DIR__ . '/../config.local.php';
    $providedPassword = $_SERVER['HTTP_X_EDIT_PASSWORD'] ?? '';

    if (!is_file($configFile) || !is_readable($configFile)) {
        http_response_code(503);
        echo json_encode(['error' => 'Location editing is not configured. Create config.local.php.']);
        exit();
    }

    $config = require $configFile;
    $editPassword = is_array($config) ? ($config['location_edit_password'] ?? '') : '';

    if (!is_string($editPassword) || $editPassword === '') {
        http_response_code(503);
        echo json_encode(['error' => 'Location editing is not configured. Set location_edit_password in config.local.php.']);
        exit();
    }

    if (!hash_equals($editPassword, $providedPassword)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or missing edit password']);
        exit();
    }
}

$dataFile = __DIR__ . '/../data/locations.geojson';
$categoriesFile = __DIR__ . '/../data/categories.json';

// Check if file exists, if not return empty collection
if (!file_exists($dataFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Data file not found', 'path' => $dataFile]);
    exit();
}

// Helper function to read GeoJSON
function getLocations() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        return ["type" => "FeatureCollection", "features" => []];
    }
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    return is_array($data) ? $data : ["type" => "FeatureCollection", "features" => []];
}

// Helper function to save GeoJSON
function saveLocations($geojson) {
    global $dataFile;
    $json = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($dataFile, $json, LOCK_EX) !== false;
}

function getCategories() {
    global $categoriesFile;
    $json = is_file($categoriesFile) ? file_get_contents($categoriesFile) : false;
    $categories = $json === false ? null : json_decode($json, true);
    return is_array($categories) ? $categories : [];
}

function categoryExists($categoryId) {
    $schemaCategories = ['bar', 'restaurant', 'trailerhelling', 'overig', 'overnachten', 'koffie', 'muziek', 'strand', 'snackbar'];
    if (!in_array($categoryId, $schemaCategories, true)) return false;
    foreach (getCategories() as $category) {
        if (($category['id'] ?? null) === $categoryId) return true;
    }
    return false;
}

function isValidDateOnly($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    [$year, $month, $day] = array_map('intval', explode('-', $date));
    return checkdate($month, $day, $year);
}

if ($method === 'GET') {
    if (($_GET['resource'] ?? '') === 'categories') {
        echo json_encode(getCategories());
        exit();
    }

    // Retrieve all locations
    $data = getLocations();
    echo json_encode($data);
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'authorize') {
        echo json_encode(['success' => true]);
        exit();
    }

    // Add a new location
    if (!isset($input['name']) || !isset($input['placeName']) || !isset($input['latitude']) || !isset($input['longitude']) || !isset($input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, placeName, latitude, longitude, category']);
        exit();
    }

    if (!categoryExists((string)$input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown category']);
        exit();
    }

    $locationDate = $input['date'] ?? gmdate('Y-m-d');
    if (!isValidDateOnly($locationDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date must use YYYY-MM-DD format']);
        exit();
    }
    
    $geojson = getLocations();
    
    $properties = [
        'name' => trim((string)$input['name']),
        'category' => $input['category'],
        'description' => (string)($input['description'] ?? $input['notes'] ?? ''),
        'avoid' => filter_var($input['avoid'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'placeName' => trim((string)$input['placeName']),
        'createdAt' => $locationDate . 'T00:00:00+00:00'
    ];
    if (isset($input['oneliner'])) $properties['oneliner'] = (string)$input['oneliner'];

    $feature = [
        "type" => "Feature",
        "geometry" => [
            "type" => "Point",
            "coordinates" => [floatval($input['longitude']), floatval($input['latitude'])]
        ],
        "properties" => $properties
    ];
    
    // Add to features array
    $geojson['features'][] = $feature;
    
    if (!saveLocations($geojson)) {
        http_response_code(500);
        echo json_encode(['error' => 'Location could not be saved']);
        exit();
    }
    echo json_encode($feature);

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['featureIndex'], $input['name'], $input['placeName'], $input['latitude'], $input['longitude'], $input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: featureIndex, name, placeName, latitude, longitude, category']);
        exit();
    }

    if (!categoryExists((string)$input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown category']);
        exit();
    }

    $geojson = getLocations();
    $featureIndex = filter_var($input['featureIndex'], FILTER_VALIDATE_INT);
    if ($featureIndex === false || $featureIndex < 0 || $featureIndex >= count($geojson['features'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Location not found']);
        exit();
    }

    $previousProperties = $geojson['features'][$featureIndex]['properties'] ?? [];
    $locationDate = isset($input['date']) && $input['date'] !== '' ? $input['date'] : null;
    if ($locationDate !== null && !isValidDateOnly($locationDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date must use YYYY-MM-DD format']);
        exit();
    }

    $properties = [
        'name' => trim((string)$input['name']),
        'category' => $input['category'],
        'description' => (string)($input['description'] ?? $input['notes'] ?? ''),
        'avoid' => filter_var($input['avoid'] ?? ($previousProperties['avoid'] ?? false), FILTER_VALIDATE_BOOLEAN),
        'placeName' => trim((string)$input['placeName']),
        'updatedAt' => gmdate('c')
    ];
    if ($locationDate !== null) $properties['createdAt'] = $locationDate . 'T00:00:00+00:00';
    elseif (isset($previousProperties['createdAt'])) $properties['createdAt'] = $previousProperties['createdAt'];
    if (isset($input['oneliner'])) $properties['oneliner'] = (string)$input['oneliner'];
    elseif (isset($previousProperties['oneliner'])) $properties['oneliner'] = $previousProperties['oneliner'];

    $updatedFeature = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [floatval($input['longitude']), floatval($input['latitude'])]
        ],
        'properties' => $properties
    ];
    $geojson['features'][$featureIndex] = $updatedFeature;

    if (!saveLocations($geojson)) {
        http_response_code(500);
        echo json_encode(['error' => 'Location could not be saved']);
        exit();
    }
    echo json_encode($updatedFeature);
    
} elseif ($method === 'DELETE') {
    // Delete a location
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['featureIndex'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing feature index']);
        exit();
    }

    $geojson = getLocations();
    $featureIndex = filter_var($input['featureIndex'], FILTER_VALIDATE_INT);
    if ($featureIndex === false || $featureIndex < 0 || $featureIndex >= count($geojson['features'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Location not found']);
        exit();
    }
    array_splice($geojson['features'], $featureIndex, 1);

    if (!saveLocations($geojson)) {
        http_response_code(500);
        echo json_encode(['error' => 'Location could not be saved']);
        exit();
    }
    echo json_encode(['success' => true]);
}
?>
