<?php

declare(strict_types=1);

namespace Geensnor\Advies;

use OpenApi\Attributes as OA;

/**
 * Een lokatiebezoek
 * 
 * Een locatie die kan bezoeken met een afstand enzo.
 *
 */

#[OA\Schema(schema: 'LocatieBezoek')]
final readonly class LocatieBezoek
{
    public function __construct(
        #[OA\Property(ref: Advies::class)]
        public Advies $advies,
        #[OA\Property(description: 'Afstand tot het advies in kilometer met heel veel decimalen', example: '12.425528619505124')]
        public float $afstand,
        #[OA\Property(description: 'Kompasrichting tot het advies in hele graden', example: '44')]
        public float $richting,
        #[OA\Property(ref: Kompasrichting::class)]
        public Kompasrichting $kompasrichting,
        #[OA\Property(description: 'URL van de OpenStreetMap-route naar het advies', example: 'https://www.openstreetmap.org/directions?from=52.3667%2C4.8945&to=52.3719%2C4.8934')]
        public string $OpenStreetMapRoute

    ) {}

    public static function vanuit(
        Locatie $huidig,
        Advies $advies,
    ): self {
        return new self(
            $advies,
            $huidig->afstandTot($advies->locatie),
            $huidig->richtingNaar($advies->locatie),
            Kompasrichting::vanGraden($huidig->richtingNaar($advies->locatie)),
            $huidig->OpenstreetviewRoute($advies->locatie)
        );
    }

    public function kompasrichting(): Kompasrichting
    {
        return Kompasrichting::vanGraden($this->richting);
    }
}
