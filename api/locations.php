<?php
// Suppress PHP warnings/notices so the API always returns clean JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dataFile = __DIR__ . '/../data/locations.geojson';

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

if ($method === 'GET') {
    // Retrieve all locations
    $data = getLocations();
    echo json_encode($data);
    
} elseif ($method === 'POST') {
    // Add a new location
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['latitude']) || !isset($input['longitude'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
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
            "description" => $input['description'] ?? substr($input['notes'] ?? '', 0, 200),
            "fullDescription" => $input['notes'] ?? '',
            "images" => $input['images'] ?? [],
            "category" => $input['category'] ?? 'custom',
            "date" => $input['date'] ?? date('d-m-Y H:i:s')
        ]
    ];
    
    // Add to features array
    $geojson['features'][] = $feature;
    
    saveLocations($geojson);
    echo json_encode($feature);
    
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
