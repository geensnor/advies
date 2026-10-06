# Plaatsen van Belang - GeoJSON Location Manager

Een interactieve webapplicatie voor het beheren van plaatsen van belang met GeoJSON opslag.

## 📁 Projectstructuur

```
advies/
├── index.html                # Hoofdpagina - Kaart + Tabel met alle locaties
├── add-location.html         # Pagina om nieuwe locatie toe te voegen
├── data/
│   └── locations.geojson     # GeoJSON bestand met alle locaties
│   └── categories.json       # Categorieën met label en markerkleur
├── api/
│   └── locations.php         # PHP API voor CRUD operaties
├── doc.kml                   # Origineel KML bestand (voor referentie)
└── README.md                 # Dit bestand
```

## 🚀 Functionaliteit

### Index pagina (index.html)
- **Interactieve kaart** met alle GeoJSON punten
- **Tabel** met alle locaties
- Filter voor **Actieve locaties**, **Alles** en **Gearchiveerde locaties** (standaard actief)
- **Sync functie**: Klik op tabel-item → kaart centreert op locatie
- **Marker klikken** → tabel markeert corresponderende rij
- **Zoeken** - Zoekt alleen in opgeslagen locatiegegevens en beschrijvingen
- **Beheer ontgrendelen** - Na servercontrole verschijnen toevoegen, wijzigen en verwijderen
- **Archiveren** - Verplaatst een locatie uit `locations.geojson` naar `data/archived-locations.geojson`
- **Exporteren** - Downloadt de actieve locaties als GeoJSON of GPX 1.1-waypoints met naam, beschrijving, plaats, categorie en eventuele datum/website
- **Wijzigen** - Beheerders kunnen naam, plaatsnaam, beschrijving en coördinaten aanpassen
- Zoeken doorzoekt ook `properties.placeName`

### Add Location pagina (add-location.html)
- **Kaart** om een locatie te selecteren (klik op kaart)
- **Plaatsen zoeken** via Nominatim API (OpenStreetMap) om een nieuw kaartpunt te kiezen
- **Coördinaten** worden automatisch ingevuld
- **Notities** toevoegen bij locatie
- **Opslaan** - Slaat op in GeoJSON via API
- **Terug** - Keert terug naar index

## 💾 Data Opslag

Alle locaties worden opgeslagen in **data/locations.geojson** in GeoJSON format:

```json
{
  "$schema": "https://geensnor.nl/schemas/geensnor-hotspots-schema.json",
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [5.565301, 51.957834]
      },
      "properties": {
        "name": "Plaatsnaam",
        "oneliner": "Korte samenvatting",
        "placeName": "Utrecht",
        "description": "Volledige beschrijving",
        "category": "bar",
        "avoid": false,
        "createdAt": "2026-10-05T12:00:00+00:00",
        "image": "https://voorbeeld.nl/afbeelding.jpg",
        "website": "https://voorbeeld.nl"
      }
    }
  ]
}
```

`placeName` wordt automatisch voorgesteld bij het prikken of zoeken van een locatie en kan handmatig worden aangepast. De index doorzoekt deze property samen met de overige opgeslagen locatiegegevens.

De bezoekdatum in het formulier wordt als `YYYY-MM-DD` verstuurd en opgeslagen als `properties.createdAt` (ISO date-time volgens het schema). Op bestaande locaties zonder datum toont de lijst `Bezocht op: onbekend`; die waarde wordt niet automatisch verzonnen bij het bewerken.

De structuur wordt gevalideerd volgens [geensnor-hotspots-schema.json](geensnor-hotspots-schema.json). `image` en `website` zijn optionele HTTP(S)-URL's; `avoid` is optioneel. Categorieën zijn beperkt tot de enum in het schema en kunnen niet vanuit de app worden toegevoegd of verwijderd. De vorige dataset is lokaal bewaard in `data/locations.geojson.before-schema-v2`.

## 🔌 PHP API (api/locations.php)

### GET - Alle locaties ophalen
```bash
GET /api/locations.php
```
Retourneert het volledige GeoJSON FeatureCollection.

Het menu **Exporteren** biedt GeoJSON en GPX. GeoJSON downloadt rechtstreeks `data/locations.geojson`; GPX genereert GPX 1.1-waypoints voor alle actieve locaties, onafhankelijk van de huidige zoek- of archieffilter.

Archiveren is een beheeractie naast verwijderen. Het archiefbestand `data/archived-locations.geojson` wordt bij de eerste archivering aangemaakt als GeoJSON FeatureCollection met dezelfde schema-conforme features. Archiveren verwijdert de locatie uit de actieve lijst; verwijderen wist deze definitief uit de actieve collectie. Via de lijstfilter kun je actieve locaties, het totaal of alleen het archief bekijken; gearchiveerde locaties zijn vanuit de lijst alleen-lezen.

### POST - Nieuwe locatie toevoegen
```bash
POST /api/locations.php
Content-Type: application/json
X-Edit-Password: <wachtwoord>

{
  "name": "Plaatsnaam",
  "oneliner": "Korte samenvatting",
  "placeName": "Utrecht",
  "date": "2026-10-05",
  "latitude": 52.52,
  "longitude": 13.405,
  "description": "Volledige beschrijving",
  "avoid": false,
  "category": "lunch",
  "image": "https://voorbeeld.nl/afbeelding.jpg",
  "website": "https://voorbeeld.nl"
}
```

### PUT - Bestaande locatie wijzigen
```bash
PUT /api/locations.php
Content-Type: application/json
X-Edit-Password: <wachtwoord>

{
  "featureIndex": 0,
  "name": "Plaatsnaam",
  "oneliner": "Korte samenvatting",
  "placeName": "Utrecht",
  "date": "2026-10-05",
  "latitude": 52.52,
  "longitude": 5.405,
  "description": "Volledige beschrijving",
  "avoid": false,
  "category": "lunch",
  "image": "https://voorbeeld.nl/afbeelding.jpg",
  "website": "https://voorbeeld.nl"
}
```

### DELETE - Locatie verwijderen
```bash
DELETE /api/locations.php
Content-Type: application/json
X-Edit-Password: <wachtwoord>

{
  "featureIndex": 0
}
```

## 🔄 Conversie KML → GeoJSON

Het originele KML bestand (doc.kml) is geconverteerd naar GeoJSON met Python script:
- **232** placemarks geconverteerd
- Inclusief: naam, beschrijving, coördinaten en categorieën
- Opgeslagen in `data/locations.geojson`

## Plaatsnamen aanvullen

Bestaande locaties zijn verrijkt met `properties.placeName` via Nominatim reverse geocoding. Om later ontbrekende waarden aan te vullen, voer eerst de controlemodus uit en daarna de schrijfmodus:

```bash
node scripts/backfill-place-names.js
node scripts/backfill-place-names.js --write
```

De schrijfmodus bewaart eerst `data/locations.geojson.before-place-name-backfill` (genegeerd door Git), vraagt Nominatim sequentieel op met minimaal 1,1 seconde tussen verzoeken en vervangt het databestand pas als de hele run slaagt.

## 🎨 Categorieën

- `bar`, `restaurant`, `trailerhelling`, `overig`, `overnachten`, `koffie`, `muziek`, `strand`, `snackbar`, `lunch`

Elke categorie heeft een eigen markerkleur. De schema-enum is leidend; categorieën kunnen niet los van een schemawijziging worden toegevoegd of verwijderd.

Labels en kleuren staan in `data/categories.json`.

## 🌍 Kaarten

- Kaart gebruikt **Leaflet** library
- Kaarttegels: **Esri World Street Map** (geen API-key nodig)
- Plaatsselectie voor nieuwe locaties: **Nominatim** (OpenStreetMap)

## 📋 Vereisten

- PHP 7.0+
- Webserver (Apache, Nginx, etc.)
- Moderne browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installatie

1. Plaats alle bestanden in de webroot
2. Zorg dat `data/` map schrijfbaar is (chmod 755)
3. Open `index.html` in browser
4. Locaties verschijnen automatisch op de kaart

## ⚙️ Configuratie

De API leest het beheerderswachtwoord uit `config.local.php` in de projectroot. Dit bestand staat in `.gitignore` en wordt dus niet naar Git gecommit. `config.example.php` is een lege template voor nieuwe clones.

Zet in `config.local.php` een lang, uniek wachtwoord in `location_edit_password`, bijvoorbeeld:

```php
<?php
return [
  'location_edit_password' => 'vul-hier-een-lang-uniek-wachtwoord-in',
];
?>
```
```

Bij een nieuwe checkout maak je dit bestand met `cp config.example.php config.local.php` en vul je daarna het wachtwoord in. Zolang de config ontbreekt of de waarde leeg is, weigert de API toevoegen en verwijderen. Locaties uitlezen blijft wel mogelijk. Commit `config.local.php` nooit.

Gebruik de site in productie uitsluitend via HTTPS, omdat het wachtwoord anders tijdens verzending onderschept kan worden.

Op `index.html` verschijnt toevoegen/wijzigen/verwijderen pas nadat **Beheer** door de API is bevestigd. Het wachtwoord wordt tijdelijk in `sessionStorage` van dezelfde tab bewaard, zodat `add-location.html` geen tweede keer om het wachtwoord vraagt. Vergrendelen wist de sessiesleutel; sluiten van de tab beëindigt de sessie eveneens.

## 📝 Opmerkingen

- Alle data wordt opgeslagen in `data/locations.geojson`
- Backup van dit bestand is aanbevolen
- Coördinaten gebruiken WGS84 (EPSG:4326) standaard
- API ondersteunt CORS headers voor cross-origin requests
