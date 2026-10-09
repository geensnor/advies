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
$archiveFile = __DIR__ . '/../data/archived-locations.geojson';
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
        return ['$schema' => 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json', 'type' => 'FeatureCollection', 'name' => 'Places of Interest', 'features' => []];
    }
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    return is_array($data) ? $data : ['$schema' => 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json', 'type' => 'FeatureCollection', 'name' => 'Places of Interest', 'features' => []];
}

// Helper function to save GeoJSON
function saveLocations($geojson) {
    global $dataFile;
    $geojson['$schema'] = 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json';
    $json = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && file_put_contents($dataFile, $json, LOCK_EX) !== false;
}

function getCategories() {
    global $categoriesFile;
    $json = is_file($categoriesFile) ? file_get_contents($categoriesFile) : false;
    $categories = $json === false ? null : json_decode($json, true);
    return is_array($categories) ? $categories : [];
}

function categoryExists($categoryId) {
    $schemaCategories = ['bar', 'restaurant', 'trailerhelling', 'overig', 'overnachten', 'koffie', 'muziek', 'strand', 'snackbar', 'lunch'];
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

function isValidWebUrl($url) {
    if (!is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) return false;
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'http' || $scheme === 'https';
}

if ($method === 'GET') {
    if (($_GET['resource'] ?? '') === 'categories') {
        echo json_encode(getCategories());
        exit();
    }

    if (($_GET['resource'] ?? '') === 'archived') {
        $archive = is_file($archiveFile) ? json_decode(file_get_contents($archiveFile), true) : null;
        if (!is_array($archive) || ($archive['type'] ?? null) !== 'FeatureCollection' || !is_array($archive['features'] ?? null)) {
            $archive = ['$schema' => 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json', 'type' => 'FeatureCollection', 'name' => 'Archived Places of Interest', 'features' => []];
        }
        $archive['$schema'] = 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json';
        echo json_encode($archive, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    // Retrieve all locations
    $data = getLocations();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'authorize') {
        echo json_encode(['success' => true]);
        exit();
    }

    if (isset($input['action']) && $input['action'] === 'archiveLocation') {
        global $archiveFile;
        if (!isset($input['featureIndex'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing feature index']);
            exit();
        }

        $geojson = getLocations();
        $featureIndex = filter_var($input['featureIndex'], FILTER_VALIDATE_INT);
        if ($featureIndex === false || $featureIndex < 0 || $featureIndex >= count($geojson['features'] ?? [])) {
            http_response_code(404);
            echo json_encode(['error' => 'Location not found']);
            exit();
        }

        $archiveExisted = is_file($archiveFile);
        $previousArchive = $archiveExisted ? file_get_contents($archiveFile) : false;
        if ($archiveExisted && $previousArchive === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Archive data could not be read']);
            exit();
        }
        $archive = $archiveExisted
            ? json_decode($previousArchive, true)
            : ['$schema' => 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json', 'type' => 'FeatureCollection', 'name' => 'Archived Places of Interest', 'features' => []];
        if (!is_array($archive) || ($archive['type'] ?? null) !== 'FeatureCollection' || !is_array($archive['features'] ?? null)) {
            http_response_code(500);
            echo json_encode(['error' => 'Archive data is invalid']);
            exit();
        }
        $archive['$schema'] = 'https://geensnor.nl/schemas/geensnor-hotspots-schema.json';

        $archivedFeature = $geojson['features'][$featureIndex];
        $archive['features'][] = $archivedFeature;
        $archiveJson = json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($archiveJson === false || file_put_contents($archiveFile, $archiveJson, LOCK_EX) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Location could not be archived']);
            exit();
        }

        array_splice($geojson['features'], $featureIndex, 1);
        if (!saveLocations($geojson)) {
            if ($archiveExisted && $previousArchive !== false) {
                file_put_contents($archiveFile, $previousArchive, LOCK_EX);
            } else {
                unlink($archiveFile);
            }
            http_response_code(500);
            echo json_encode(['error' => 'Active locations could not be saved']);
            exit();
        }

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

    $locationDate = $input['date'] ?? '';
    if ($locationDate !== '' && !isValidDateOnly($locationDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date must use YYYY-MM-DD format']);
        exit();
    }
    foreach (['image', 'website'] as $urlField) {
        if (!empty($input[$urlField]) && !isValidWebUrl($input[$urlField])) {
            http_response_code(400);
            echo json_encode(['error' => ucfirst($urlField) . ' must be an http or https URL']);
            exit();
        }
    }
    
    $geojson = getLocations();
    
    $properties = [
        'name' => trim((string)$input['name']),
        'category' => $input['category'],
        'description' => (string)($input['description'] ?? $input['notes'] ?? ''),
        'avoid' => filter_var($input['avoid'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'placeName' => trim((string)$input['placeName'])
    ];
    if ($locationDate !== '') $properties['visitedAt'] = $locationDate . 'T00:00:00+00:00';
    foreach (['image', 'website'] as $urlField) {
        if (!empty($input[$urlField])) $properties[$urlField] = trim((string)$input[$urlField]);
    }

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
    $hasLocationDate = array_key_exists('date', $input);
    $locationDate = $hasLocationDate && $input['date'] !== '' ? $input['date'] : null;
    if ($locationDate !== null && !isValidDateOnly($locationDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date must use YYYY-MM-DD format']);
        exit();
    }
    foreach (['image', 'website'] as $urlField) {
        if (isset($input[$urlField]) && $input[$urlField] !== '' && !isValidWebUrl($input[$urlField])) {
            http_response_code(400);
            echo json_encode(['error' => ucfirst($urlField) . ' must be an http or https URL']);
            exit();
        }
    }

    $properties = [
        'name' => trim((string)$input['name']),
        'category' => $input['category'],
        'description' => (string)($input['description'] ?? $input['notes'] ?? ''),
        'avoid' => filter_var($input['avoid'] ?? ($previousProperties['avoid'] ?? false), FILTER_VALIDATE_BOOLEAN),
        'placeName' => trim((string)$input['placeName']),
        'updatedAt' => gmdate('c')
    ];
    if ($locationDate !== null) $properties['visitedAt'] = $locationDate . 'T00:00:00+00:00';
    elseif (!$hasLocationDate && isset($previousProperties['visitedAt'])) $properties['visitedAt'] = $previousProperties['visitedAt'];
    foreach (['image', 'website'] as $urlField) {
        if (!isset($input[$urlField]) && isset($previousProperties[$urlField])) {
            $properties[$urlField] = $previousProperties[$urlField];
        } elseif (isset($input[$urlField]) && $input[$urlField] !== '') {
            $properties[$urlField] = trim((string)$input[$urlField]);
        }
    }

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
