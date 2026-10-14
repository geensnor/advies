<?php

declare(strict_types=1);

namespace Geensnor\Advies;

/**
 * Locatie
 *
 */
final readonly class Locatie
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    /** Afstand in km (haversine) */
    public function afstandTot(Locatie $andere): float
    {
        $r = 6371;
        $dLat = deg2rad($andere->latitude - $this->latitude);
        $dLon = deg2rad($andere->longitude - $this->longitude);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->latitude)) * cos(deg2rad($andere->latitude)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
