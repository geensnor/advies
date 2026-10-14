<?php

declare(strict_types=1);

namespace Geensnor\Advies;

final readonly class LocatieBezoek
{
    public function __construct(
        public Advies $advies,
        public float $afstand,
    ) {}

    public static function vanuit(
        Locatie $huidig,
        Advies $advies,
    ): self {
        return new self(
            $advies,
            $huidig->afstandTot($advies->locatie),
        );
    }
}
