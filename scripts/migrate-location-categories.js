const fs = require('node:fs');
const path = require('node:path');

const dataPath = path.join(__dirname, '..', 'data', 'locations.geojson');
const backupPath = path.join(__dirname, '..', 'data', 'locations.geojson.before-category-migration');
const temporaryPath = `${dataPath}.tmp`;

const geojson = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
let migrated = 0;

for (const feature of geojson.features || []) {
    if (feature.properties.category === 'poi' || feature.properties.category === 'custom') {
        feature.properties.category = 'overig';
        migrated += 1;
    }
}

if (!fs.existsSync(backupPath)) fs.copyFileSync(dataPath, backupPath);
fs.writeFileSync(temporaryPath, `${JSON.stringify(geojson, null, 4)}\n`);
fs.renameSync(temporaryPath, dataPath);
console.log(`${migrated} legacy categories moved to Overig. Backup saved next to the GeoJSON file.`);