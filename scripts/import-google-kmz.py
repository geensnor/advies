import html
import json
import re
import sys
import zipfile
import xml.etree.ElementTree as ET
from collections import Counter, defaultdict
from html.parser import HTMLParser
from pathlib import Path


ROOT = Path(__file__).resolve().parent.parent
DEFAULT_SOURCE = ROOT / "backup_google" / "Meer geensnor.kmz"
DEFAULT_OUTPUT = ROOT / "backup_google" / "Meer geensnor.geojson"
SCHEMA_URL = "https://geensnor.nl/schemas/geensnor-hotspots-schema.json"
KML_NS = "http://www.opengis.net/kml/2.2"
NS = {"k": KML_NS}
URL_PATTERN = re.compile(r"https?://[^\s<>\"\\]+")


class DescriptionParser(HTMLParser):
    block_tags = {"br", "div", "li", "p", "tr"}

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.parts = []
        self.images = []

    def handle_starttag(self, tag, attrs):
        attributes = dict(attrs)
        if tag.lower() in self.block_tags:
            self.parts.append("\n")
        if tag.lower() == "img" and attributes.get("src"):
            self.images.append(attributes["src"].strip())

    def handle_endtag(self, tag):
        if tag.lower() in self.block_tags:
            self.parts.append("\n")

    def handle_data(self, data):
        self.parts.append(data)

    def text(self):
        lines = [re.sub(r"\s+", " ", line).strip() for line in "".join(self.parts).splitlines()]
        return "\n".join(line for line in lines if line)


def locality_index():
    by_coordinate = defaultdict(list)
    for filename in ("locations.geojson", "archived-locations.geojson"):
        filepath = ROOT / "data" / filename
        collection = json.loads(filepath.read_text(encoding="utf-8"))
        for feature in collection.get("features", []):
            longitude, latitude = feature["geometry"]["coordinates"][:2]
            key = (round(longitude, 5), round(latitude, 5))
            by_coordinate[key].append(feature.get("properties", {}))
    return by_coordinate


def style_icons(root):
    styles = {}
    for style in root.findall(".//k:Style", NS):
        icon = style.findtext("k:IconStyle/k:Icon/k:href", default="", namespaces=NS)
        styles[f"#{style.get('id', '')}"] = icon.strip()
    for style_map in root.findall(".//k:StyleMap", NS):
        for pair in style_map.findall("k:Pair", NS):
            if pair.findtext("k:key", namespaces=NS) == "normal":
                styles[f"#{style_map.get('id', '')}"] = pair.findtext("k:styleUrl", default="", namespaces=NS)
    return styles


def resolve_icon(style_url, styles):
    seen = set()
    while style_url in styles and style_url not in seen:
        seen.add(style_url)
        style_url = styles[style_url]
    return styles.get(style_url, style_url)


def map_category(icon_url):
    mappings = (
        ("1085-biz-restaurant-generic", "restaurant"),
        ("979-biz-bar", "bar"),
        ("1035-biz-hotel", "overnachten"),
        ("1081-biz-restaurant-fastfood", "snackbar"),
        ("991-biz-cafe", "koffie"),
        ("1355-rec-beach", "strand"),
        ("1421-trans-boat-launch", "trailerhelling"),
        ("1053-biz-music", "muziek"),
    )
    for icon_marker, category in mappings:
        if icon_marker in icon_url:
            return category
    return "overig"


def description_parts(placemark):
    raw_description = placemark.findtext("k:description", default="", namespaces=NS)
    parser = DescriptionParser()
    parser.feed(html.unescape(raw_description))

    media_value = placemark.findtext(
        './/k:Data[@name="gx_media_links"]/k:value', default="", namespaces=NS
    )
    media_urls = list(dict.fromkeys(URL_PATTERN.findall(media_value)))
    image_urls = list(dict.fromkeys(media_urls + parser.images))

    description = parser.text()

    links = list(dict.fromkeys(
        url.rstrip(".,;:!?)]}")
        for url in URL_PATTERN.findall(html.unescape(raw_description))
        if url.rstrip(".,;:!?)]}") not in image_urls
    ))
    website = links[0] if links else None
    if len(links) > 1:
        extras = "\n".join(f"Link: {url}" for url in links[1:])
        description = f"{description}\n\n{extras}" if description else extras
    return description, image_urls, website


def main():
    source_path = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else DEFAULT_SOURCE.resolve()
    output_path = Path(sys.argv[2]).resolve() if len(sys.argv) > 2 else DEFAULT_OUTPUT.resolve()
    if source_path == output_path:
        raise ValueError("Input KMZ and output GeoJSON must be different files.")

    with zipfile.ZipFile(source_path) as archive:
        kml_files = [name for name in archive.namelist() if name.lower().endswith(".kml")]
        if len(kml_files) != 1:
            raise ValueError(f"Expected one KML file in the KMZ, found {len(kml_files)}.")
        root = ET.fromstring(archive.read(kml_files[0]))

    styles = style_icons(root)
    known_locations = locality_index()
    category_counts = Counter()
    icon_counts = Counter()
    extra_image_count = 0
    website_count = 0
    matched_locality_count = 0
    missing_locality_count = 0
    features = []

    for placemark in root.findall(".//k:Placemark", NS):
        name = placemark.findtext("k:name", default="", namespaces=NS).strip()
        coordinates_text = placemark.findtext(".//k:Point/k:coordinates", default="", namespaces=NS)
        coordinates = [float(value) for value in coordinates_text.strip().split(",")[:2]]
        if len(coordinates) != 2:
            raise ValueError(f"Placemark {name!r} has invalid point coordinates.")
        longitude, latitude = coordinates
        if not -180 <= longitude <= 180 or not -90 <= latitude <= 90:
            raise ValueError(f"Placemark {name!r} has out-of-range coordinates.")

        style_url = placemark.findtext("k:styleUrl", default="", namespaces=NS)
        icon_url = resolve_icon(style_url, styles)
        is_avoid = "crisis-death" in icon_url
        category = map_category(icon_url)
        category_counts[category] += 1
        icon_counts[icon_url] += 1

        key = (round(longitude, 5), round(latitude, 5))
        matches = known_locations.get(key, [])
        properties = matches[0] if len(matches) == 1 else {}
        place_name = properties.get("placeName", "")
        matched_locality_count += bool(place_name)
        missing_locality_count += not bool(place_name)

        description, image_urls, website = description_parts(placemark)
        feature_properties = {
            "name": name or "Onbekende locatie",
            "category": category,
            "description": description,
            "avoid": is_avoid,
            "placeName": place_name,
        }
        if image_urls:
            feature_properties["image"] = image_urls[0]
            extra_image_count += len(image_urls) - 1
        if website:
            feature_properties["website"] = website
            website_count += 1

        features.append({
            "type": "Feature",
            "geometry": {"type": "Point", "coordinates": [longitude, latitude]},
            "properties": feature_properties,
        })

    document = {
        "$schema": SCHEMA_URL,
        "type": "FeatureCollection",
        "name": "Meer geensnor",
        "features": features,
    }
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(document, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    print(f"Wrote {len(features)} locations to {output_path}")
    print(f"Categories: {dict(sorted(category_counts.items()))}")
    print(f"Primary photo URLs: {sum('image' in feature['properties'] for feature in features)}; additional photo URLs omitted: {extra_image_count}")
    print(f"Websites: {website_count}; place names reused: {matched_locality_count}; place names unknown: {missing_locality_count}")
    print(f"Avoid markers from death icons: {sum('crisis-death' in icon for icon in icon_counts for _ in range(icon_counts[icon]))}")


if __name__ == "__main__":
    main()