<?php

declare(strict_types=1);

namespace Geensnor\Advies;

/**
 * Hele lijst van Adviezen.
 *
 */
final readonly class AdviesCollectie
{
    /** @param list<Advies> $Advies */
    public function __construct(
        public string $naam,
        private array $Advies,
    ) {}

    public static function vanBestand(string $pad): self
    {
        $data = json_decode(file_get_contents($pad), true, flags: JSON_THROW_ON_ERROR);

        return new self(
            $data['name'],
            array_map(Advies::vanFeature(...), $data['features']),
        );
    }

    /**
     * Het $aantal dichtstbijzijnde Adviezen, gesorteerd op afstand.
     *
     * @return list<LocatieBezoek>
     */
    public function dichtstbijzijnde(Locatie $auidig, int $aantal = 5): array
    {
        $bezoeken = array_map(
            fn(Advies $a) => LocatieBezoek::vanuit($auidig, $a),
            $this->Advies
        );

        usort($bezoeken, fn($a, $b) => $a->afstand <=> $b->afstand);

        return array_slice($bezoeken, 0, $aantal);
    }

    /**
     * De random Advies met afstand.
     *
     *
     */
    public function geefRandom(): Advies
    {
        return $this->Advies[array_rand($this->Advies)];
    }
}
