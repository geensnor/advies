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


        if (! is_file($pad) || ! is_readable($pad)) {
            throw new \RuntimeException("Kan bestand niet lezen: {$pad}");
        }
        $inhoud = file_get_contents($pad);
        $data = json_decode($inhoud, true, flags: JSON_THROW_ON_ERROR);

        if (! isset($data['name'], $data['features']) || ! is_array($data['features'])) {
            throw new \InvalidArgumentException("Ongeldige structuur in {$pad}: 'name' en 'features' zijn verplicht.");
        }

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
