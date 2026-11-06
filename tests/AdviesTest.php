<?php

use Geensnor\Advies\AdviesCollectie;
use Geensnor\Advies\Locatie;

test('Hotspot met de op twee na kortste afstand kan gevonden worden', function () {
    $collectie = AdviesCollectie::vanBestand(__DIR__ . '/fixtures/advies-test.geojson');
    expect($collectie->dichtstbijzijnde(new Locatie(50.862330, 4.874687), 3)[2]->advies->naam)->toBe('Cafeterapie');
});

test('Random hotspot kan gevonden worden', function () {
    $collectie = AdviesCollectie::vanBestand(__DIR__ . '/fixtures/advies-test.geojson');
    expect($collectie->geefRandom()->naam)->not->toBeNull();
});

test('Ongeldig bestand geeft mooie error', function () {
    $collectie = AdviesCollectie::vanBestand(__DIR__ . '/fixtures/bestandbestaatniet.geojson');
})->throws(RuntimeException::class, 'Kan bestand niet lezen');
