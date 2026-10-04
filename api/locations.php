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
    file_put_contents($dataFile, $json);
}

function getCategories() {
    global $categoriesFile;
    $json = is_file($categoriesFile) ? file_get_contents($categoriesFile) : false;
    $categories = $json === false ? null : json_decode($json, true);
    return is_array($categories) ? $categories : [];
}

function saveCategories($categories) {
    global $categoriesFile;
    $json = json_encode(array_values($categories), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($categoriesFile, $json, LOCK_EX) !== false;
}

function categoryExists($categoryId) {
    foreach (getCategories() as $category) {
        if (($category['id'] ?? null) === $categoryId) return true;
    }
    return false;
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

    if (isset($input['action']) && $input['action'] === 'addCategory') {
        $name = trim((string)($input['name'] ?? ''));
        $color = (string)($input['color'] ?? '');
        if ($name === '' || strlen($name) > 40 || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            http_response_code(400);
            echo json_encode(['error' => 'Geef een categorienaam (max. 40 tekens) en geldige kleur op.']);
            exit();
        }

        $categories = getCategories();
        foreach ($categories as $category) {
            if (mb_strtolower($category['label'] ?? '') === mb_strtolower($name)) {
                http_response_code(409);
                echo json_encode(['error' => 'Deze categorie bestaat al.']);
                exit();
            }
        }

        $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name));
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
        if ($slug === '') $slug = 'categorie';
        $baseSlug = $slug;
        $suffix = 2;
        while (categoryExists($slug)) $slug = $baseSlug . '-' . $suffix++;

        $newCategory = ['id' => $slug, 'label' => $name, 'color' => strtolower($color)];
        $categories[] = $newCategory;
        if (!saveCategories($categories)) {
            http_response_code(500);
            echo json_encode(['error' => 'Categorie kon niet worden opgeslagen.']);
            exit();
        }
        echo json_encode($newCategory);
        exit();
    }

    if (isset($input['action']) && $input['action'] === 'deleteCategory') {
        $categoryId = (string)($input['id'] ?? '');
        $categories = getCategories();
        $categoryIndex = null;
        foreach ($categories as $index => $category) {
            if (($category['id'] ?? null) === $categoryId) $categoryIndex = $index;
        }
        if ($categoryIndex === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Categorie niet gevonden.']);
            exit();
        }
        if (count($categories) <= 1) {
            http_response_code(409);
            echo json_encode(['error' => 'De laatste categorie kan niet worden verwijderd.']);
            exit();
        }

        foreach (getLocations()['features'] ?? [] as $feature) {
            if (($feature['properties']['category'] ?? '') === $categoryId) {
                http_response_code(409);
                echo json_encode(['error' => 'Deze categorie is nog in gebruik. Wijs de locaties eerst een andere categorie toe.']);
                exit();
            }
        }

        array_splice($categories, $categoryIndex, 1);
        if (!saveCategories($categories)) {
            http_response_code(500);
            echo json_encode(['error' => 'Categorie kon niet worden verwijderd.']);
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
    
    $geojson = getLocations();
    
    // Create new feature
    $feature = [
        "type" => "Feature",
        "geometry" => [
            "type" => "Point",
            "coordinates" => [floatval($input['longitude']), floatval($input['latitude'])]
        ],
        "properties" => [
            "id" => isset($input['id']) ? $input['id'] : (int)(microtime(true) * 1000),
            "name" => $input['name'],
            "placeName" => $input['placeName'],
            "description" => $input['description'] ?? substr($input['notes'] ?? '', 0, 200),
            "fullDescription" => $input['notes'] ?? '',
            "images" => $input['images'] ?? [],
            "category" => $input['category'],
            "date" => $input['date'] ?? date('d-m-Y H:i:s')
        ]
    ];
    
    // Add to features array
    $geojson['features'][] = $feature;
    
    saveLocations($geojson);
    echo json_encode($feature);

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || !isset($input['name']) || !isset($input['placeName']) || !isset($input['latitude']) || !isset($input['longitude']) || !isset($input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: id, name, placeName, latitude, longitude, category']);
        exit();
    }

    if (!categoryExists((string)$input['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown category']);
        exit();
    }

    $geojson = getLocations();
    $updatedFeature = null;
    foreach ($geojson['features'] as &$feature) {
        if ((string)($feature['properties']['id'] ?? '') !== (string)$input['id']) {
            continue;
        }

        $feature['geometry'] = [
            'type' => 'Point',
            'coordinates' => [floatval($input['longitude']), floatval($input['latitude'])]
        ];
        $feature['properties'] = array_merge($feature['properties'] ?? [], [
            'name' => $input['name'],
            'placeName' => $input['placeName'],
            'category' => $input['category'],
            'description' => $input['description'] ?? substr($input['notes'] ?? '', 0, 200),
            'fullDescription' => $input['notes'] ?? '',
            'date' => date('d-m-Y H:i:s')
        ]);
        $updatedFeature = $feature;
        break;
    }
    unset($feature);

    if ($updatedFeature === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Location not found']);
        exit();
    }

    saveLocations($geojson);
    echo json_encode($updatedFeature);
    
} elseif ($method === 'DELETE') {
    // Delete a location
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing location id']);
        exit();
    }
    
    $geojson = getLocations();
    $geojson['features'] = array_filter($geojson['features'], function($feature) use ($input) {
        return $feature['properties']['id'] != $input['id'];
    });
    $geojson['features'] = array_values($geojson['features']);
    
    saveLocations($geojson);
    echo json_encode(['success' => true]);
}
?>
