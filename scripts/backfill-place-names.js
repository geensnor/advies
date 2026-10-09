const fs = require('node:fs');
const path = require('node:path');

const fileOptionIndex = process.argv.indexOf('--file');
const fileOptionValue = fileOptionIndex >= 0 ? process.argv[fileOptionIndex + 1] : null;
if (fileOptionIndex >= 0 && (!fileOptionValue || fileOptionValue.startsWith('--'))) {
    throw new Error('Usage: node scripts/backfill-place-names.js [--file <geojson>] [--write]');
}

const dataPath = fileOptionValue
    ? path.resolve(fileOptionValue)
    : path.join(__dirname, '..', 'data', 'locations.geojson');
const backupPath = `${dataPath}.before-place-name-backfill`;
const temporaryPath = `${dataPath}.tmp`;
const writeChanges = process.argv.includes('--write');
const minimumIntervalMs = 1100;

function delay(milliseconds) {
    return new Promise(resolve => setTimeout(resolve, milliseconds));
}

function localityFromAddress(address) {
    return address.city || address.town || address.village || address.municipality ||
        address.hamlet || address.city_district || address.suburb || address.borough ||
        address.county || address.state_district || address.country || '';
}

async function main() {
    const geojson = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
    const features = geojson.features || [];
    const missing = features.filter(feature => !String(feature.properties?.placeName || '').trim());

    if (!writeChanges) {
        console.log(`${missing.length} locaties missen placeName. Voer opnieuw uit met --write om ze aan te vullen.`);
        return;
    }

    if (!fs.existsSync(backupPath)) fs.copyFileSync(dataPath, backupPath);
    let lastRequestAt = 0;

    for (let index = 0; index < missing.length; index += 1) {
        const feature = missing[index];
        const [longitude, latitude] = feature.geometry.coordinates;
        let placeName = '';

        for (let attempt = 1; attempt <= 4; attempt += 1) {
            const waitMs = minimumIntervalMs - (Date.now() - lastRequestAt);
            if (waitMs > 0) await delay(waitMs);

            const url = new URL('https://nominatim.openstreetmap.org/reverse');
            url.search = new URLSearchParams({
                format: 'jsonv2',
                addressdetails: '1',
                zoom: '10',
                lat: String(latitude),
                lon: String(longitude)
            }).toString();

            let response;
            try {
                response = await fetch(url, {
                    headers: {
                        'User-Agent': 'GeensnorLocationManager/1.0 (https://advies.geensnor.nl)',
                        'Accept-Language': 'nl'
                    }
                });
            } catch (error) {
                if (attempt === 4) throw error;
                await delay(attempt * 2000);
                continue;
            }
            lastRequestAt = Date.now();

            if (response.ok) {
                const result = await response.json();
                placeName = localityFromAddress(result.address || '');
                break;
            }

            if (attempt === 4) {
                throw new Error(`Reverse geocoding failed with HTTP ${response.status}. No data was changed.`);
            }
            const retryAfter = Number(response.headers.get('retry-after'));
            await delay(Number.isFinite(retryAfter) && retryAfter > 0 ? retryAfter * 1000 : attempt * 3000);
        }

        if (!placeName) {
            throw new Error(`No locality returned for feature ${feature.properties?.id}. No data was changed.`);
        }
        feature.properties.placeName = placeName;

        if ((index + 1) % 25 === 0 || index + 1 === missing.length) {
            console.log(`Geocode voortgang: ${index + 1}/${missing.length}`);
        }
    }

    fs.writeFileSync(temporaryPath, `${JSON.stringify(geojson, null, 4)}\n`);
    fs.renameSync(temporaryPath, dataPath);
    console.log(`${missing.length} locaties aangevuld. Back-up staat naast het GeoJSON-bestand.`);
}

main().catch(error => {
    if (fs.existsSync(temporaryPath)) fs.unlinkSync(temporaryPath);
    console.error(error.message);
    process.exitCode = 1;
});