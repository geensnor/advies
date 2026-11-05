<?php

declare(strict_types=1);

namespace Geensnor\Advies;

use OpenApi\Attributes as OA;

/**
 * Advies van Geensnor. Geladen uit een GeoJSON-bestand.
 *
 */
#[OA\Schema(schema: 'Advies')]
final readonly class Advies
{
    public function __construct(

        #[OA\Property(ref: Locatie::class)]
        public Locatie $locatie,
        #[OA\Property(description: 'Naam van het advies', example: 'Café De Brakke Emmer')]
        public string $naam,
        #[OA\Property(description: 'Categorie van het advies', example: 'Café')]
        public string $categorie,
        #[OA\Property(description: 'Omschrijving van het advies', example: 'Lekker bier, brakke kutbediening')]
        public string $omschrijving,
        #[OA\Property(description: 'Plaatsnaam', example: 'Diepenveen')]
        public string $plaatsnaam,
        #[OA\Property(description: 'Is dit een advies om juist niet heen te gaan?', example: true)]
        public bool $vermijden = false,
        #[OA\Property(description: 'URL van mooie afbeelding bij het advies', example: 'https://example.com/echt-mooi-dit.jpg')]
        public ?string $afbeelding = null,
        public ?string $website = null,
        public ?\DateTimeImmutable $bezochtOp = null,
        public ?\DateTimeImmutable $gewijzigdOp = null,
    ) {}

    /** @param array<string, mixed> $feature Eén GeoJSON Feature uit het schema */
    public static function vanFeature(array $feature): self
    {
        $p = $feature['properties'];
        [$lon, $lat] = $feature['geometry']['coordinates']; // GeoJSON: lon eerst!

        return new self(
            locatie: new Locatie((float) $lat, (float) $lon),
            naam: $p['name'],
            categorie: $p['category'],
            omschrijving: $p['description'],
            plaatsnaam: $p['placeName'],
            vermijden: $p['avoid'] ?? false,
            afbeelding: $p['image'] ?? null,
            website: $p['website'] ?? null,
            bezochtOp: isset($p['visitedAt']) ? new \DateTimeImmutable($p['visitedAt']) : null,
            gewijzigdOp: isset($p['updatedAt']) ? new \DateTimeImmutable($p['updatedAt']) : null,
        );
    }
}
