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

    public function richtingNaar(Locatie $andere): float
    {
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($andere->latitude);
        $dLon = deg2rad($andere->longitude - $this->longitude);

        $y = sin($dLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLon);

        return round(fmod(rad2deg(atan2($y, $x)) + 360, 360));
    }
}
