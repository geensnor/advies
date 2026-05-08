# Plaatsen van Belang - GeoJSON Location Manager

Een interactieve webapplicatie voor het beheren van plaatsen van belang met GeoJSON opslag.

## 📁 Projectstructuur

```
advies/
├── index.html                # Hoofdpagina - Kaart + Tabel met alle locaties
├── add-location.html         # Pagina om nieuwe locatie toe te voegen
├── data/
│   └── locations.geojson     # GeoJSON bestand met alle locaties
├── api/
│   └── locations.php         # PHP API voor CRUD operaties
├── doc.kml                   # Origineel KML bestand (voor referentie)
└── README.md                 # Dit bestand
```

## 🚀 Functionaliteit

### Index pagina (index.html)
- **Interactieve kaart** met alle GeoJSON punten
- **Tabel** met alle locaties
- **Sync functie**: Klik op tabel-item → kaart centreert op locatie
- **Marker klikken** → tabel markeert corresponderende rij
- **Verwijderen** - Locaties kunnen uit de tabel worden verwijderd
- **Toevoegen** - Knop om naar add-location pagina te gaan

### Add Location pagina (add-location.html)
- **Kaart** om een locatie te selecteren (klik op kaart)
- **Zoek functionaliteit** via Nominatim API (OpenStreetMap)
- **Coördinaten** worden automatisch ingevuld
- **Notities** toevoegen bij locatie
- **Opslaan** - Slaat op in GeoJSON via API
- **Terug** - Keert terug naar index

## 💾 Data Opslag

Alle locaties worden opgeslagen in **data/locations.geojson** in GeoJSON format:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [5.565301, 51.957834]
      },
      "properties": {
        "id": 1,
        "name": "Plaatsnaam",
        "description": "Korte beschrijving",
        "fullDescription": "Volledige beschrijving",
        "images": ["url1", "url2"],
        "category": "poi",
        "date": "08-05-2026 12:00:00"
      }
    }
  ]
}
```

## 🔌 PHP API (api/locations.php)

### GET - Alle locaties ophalen
```bash
GET /api/locations.php
```
Retourneert het volledige GeoJSON FeatureCollection.

### POST - Nieuwe locatie toevoegen
```bash
POST /api/locations.php
Content-Type: application/json

{
  "name": "Plaatsnaam",
  "latitude": 52.52,
  "longitude": 13.405,
  "notes": "Beschrijving",
  "images": [],
  "category": "custom"
}
```

### DELETE - Locatie verwijderen
```bash
DELETE /api/locations.php
Content-Type: application/json

{
  "id": 1234567890
}
```

## 🔄 Conversie KML → GeoJSON

Het originele KML bestand (doc.kml) is geconverteerd naar GeoJSON met Python script:
- **232** placemarks geconverteerd
- Inclusief: naam, beschrijving, coördinaten, afbeeldingen, categorieën
- Opgeslagen in `data/locations.geojson`

## 🎨 Categorieën

- **POI** (Point of Interest) - Originele locaties uit KML
- **Custom** - Handmatig toegevoegde locaties

Elke categorie heeft een ander marker-kleur op de kaart.

## 🌍 Kaarten

- Kaart gebruikt **Leaflet** library
- Tile layer: **OpenStreetMap**
- Zoek API: **Nominatim** (OpenStreetMap)

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

Geen externe configuratie nodig. Alles werkt out-of-the-box.

## 📝 Opmerkingen

- Alle data wordt opgeslagen in `data/locations.geojson`
- Backup van dit bestand is aanbevolen
- Coördinaten gebruiken WGS84 (EPSG:4326) standaard
- API ondersteunt CORS headers voor cross-origin requests
