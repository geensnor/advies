<?php

declare(strict_types=1);

namespace Geensnor\Advies;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Geensnor Advies API',
    version: '1.0.0',
    description: 'Een API voor adviezen. Altijd dichtbij, altijd relevant.'
)]
#[OA\Tag(name: 'Advies', description: 'Endpoints voor het ophalen van adviezen')]
#[OA\Schema(schema: 'Latitude', type: 'number', format: 'float', minimum: -90, maximum: 90, example: 52.3676)]
#[OA\Schema(schema: 'Longitude', type: 'number', format: 'float', minimum: -180, maximum: 180, example: 4.9041)]
class AdviesApi
{
    private const MAX_LIMIT = 100;

    private AdviesCollectie $collectie;

    public function __construct(?string $bestand = null)
    {
        $this->collectie = AdviesCollectie::vanBestand(
            $bestand ?? __DIR__ . '/../data/locations.geojson'
        );
    }

    #[OA\Get(
        path: '/api/random.php',
        operationId: 'getRandomAdvies',
        summary: 'Haalt een willekeurig advies op',
        tags: ['Advies'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Een willekeurig advies',
                content: new OA\JsonContent(ref: Advies::class),
            ),
        ]
    )]
    public function geefRandom(): void
    {
        $this->json($this->collectie->geefRandom());
    }

    #[OA\Get(
        path: '/api/adviesDichtbij.php',
        operationId: 'getAdviesDichtbij',
        summary: 'Haal adviezen in de buurt van een locatie op',
        tags: ['Advies'],
        parameters: [
            new OA\Parameter(
                name: 'latitude',
                in: 'query',
                required: true,
                schema: new OA\Schema(ref: '#/components/schemas/Latitude'),
            ),
            new OA\Parameter(
                name: 'longitude',
                in: 'query',
                required: true,
                schema: new OA\Schema(ref: '#/components/schemas/Longitude'),
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Maximaal aantal adviezen',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: self::MAX_LIMIT, default: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Adviezen in de buurt',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: LocatieBezoek::class)
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ontbrekende of ongeldige parameters',
                content: new OA\JsonContent(ref: ApiFout::class)
            ),
        ]
    )]
    public function geefDichtbij(): void
    {
        try {
            $invoer = $this->leesDichtbijInvoer();
        } catch (\InvalidArgumentException $e) {
            $this->json(new ApiFout('invalid_input', $e->getMessage()), 400);
            return;
        }

        $this->json($this->collectie->dichtstbijzijnde(
            new Locatie($invoer['latitude'], $invoer['longitude']),
            $invoer['limit'],
        ));
    }

    /** @return array{latitude: float, longitude: float, limit: int} */
    private function leesDichtbijInvoer(): array
    {
        if (!isset($_GET['latitude'], $_GET['longitude'])) {
            throw new \InvalidArgumentException('Missing latitude or longitude parameters.');
        }

        $latitude = filter_var($_GET['latitude'], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($_GET['longitude'], FILTER_VALIDATE_FLOAT);

        if (
            $latitude === false || $latitude < -90 || $latitude > 90
            || $longitude === false || $longitude < -180 || $longitude > 180
        ) {
            throw new \InvalidArgumentException('Invalid latitude or longitude values.');
        }

        $limit = 1;
        if (isset($_GET['limit'])) {
            $limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT);
            if ($limit === false || $limit < 1 || $limit > self::MAX_LIMIT) {
                throw new \InvalidArgumentException('Invalid limit value.');
            }
        }

        return ['latitude' => $latitude, 'longitude' => $longitude, 'limit' => $limit];
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}
