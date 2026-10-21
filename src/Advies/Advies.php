<?php

declare(strict_types=1);

namespace Geensnor\Advies;

/**
 * Advies van Geensnor. Geladen uit een GeoJSON-bestand.
 *
 */


final readonly class Advies
{
    public function __construct(
        public Locatie $locatie,
        public string $naam,
        public string $categorie,
        public string $omschrijving,
        public string $plaatsnaam,
        public bool $vermijden = false,
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
