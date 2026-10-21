<?php

declare(strict_types=1);

namespace Geensnor\Advies;

enum Kompasrichting: string
{
    case N = 'N';
    case NNO = 'NNO';
    case NO = 'NO';
    case ONO = 'ONO';
    case O = 'O';
    case OZO = 'OZO';
    case ZO = 'ZO';
    case ZZO = 'ZZO';
    case Z = 'Z';
    case ZZW = 'ZZW';
    case ZW = 'ZW';
    case WZW = 'WZW';
    case W = 'W';
    case WNW = 'WNW';
    case NW = 'NW';
    case NNW = 'NNW';

    public static function vanGraden(float $graden): self
    {
        $cases = self::cases();  // staat in volgorde van de windroos

        // elke richting beslaat 22,5°, gecentreerd rond zijn eigen punt
        $index = (int) round(fmod($graden, 360) / 22.5) % 16;

        return $cases[$index];
    }
}
