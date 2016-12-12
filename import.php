<?php
//KML: https://www.google.com/maps/d/u/0/kml?hl=nl&authuser=0&mid=1v6xr6gJ0SiwsTdkcrZKjNtgf2Z0&forcekml=1&cid=mp&cv=55LuS3XVbjg.nl
//Korte KML: https://goo.gl/Tn299k

include("include.php");

function kmztoxml($kmz){
		$newfile = dirname(__FILE__)."/temp.kmz";
		copy($kmz, $newfile);

		$zip = new ZipArchive;
		$res = $zip->open("temp.kmz");
		if ($res === TRUE) {
		  // extract it to the path we determined above
		  $zip->extractTo(realpath(dirname(__FILE__)));
		  $zip->close();
			
			return simplexml_load_file("doc.kml", "SimpleXMLElement", LIBXML_NOCDATA);
		} else {
		  echo "Kan bestand niet openen";
		}
}

if(!$_GET["kml"] && !$_GET["kmz"]){//Pagina laden
	$aantalAdviezen = R::count('advies');
	$laatsteUpdate = R::getAll("SELECT max(creationDate) as maxCreationDate FROM advies");

	echo "Adviezen in de database: ".$aantalAdviezen."<br>
			Laatste update: ".$laatsteUpdate[0]["maxCreationDate"]."
			<br><br>Geen KMZ url<br>Voer achter http://advies.geensnor.nl/import.php?kml= de url van de KMZ file in. KMZ kan ook. Dan moet je ?kmz= achter de url plakken. Maar doe maar KML want dat is beter.<br><br>Bijvoorbeeld:<br>https://goo.gl/Tn299k

			<br><br>Als er niets is gewijzigd zou hieronder klikken voldoende moeten zijn<br>
			<a href='http://advies.geensnor.nl/import.php?kml=https://goo.gl/Tn299k'>http://advies.geensnor.nl/import.php?kml=https://goo.gl/Tn299k</a>";
}
else{
	if($_GET["kml"]){
		$geensnorObject = simplexml_load_file($_GET["kml"], "SimpleXMLElement", LIBXML_NOCDATA);
	}
	if($_GET["kmz"]){
		$locationXML = kmztoxml($_GET["kmz"]);
		$geensnorObject = kmztoxml($locationXML->Document->NetworkLink->Link->href);

	}

//$kmlFiles = glob("kmlhiero/*.kml");
/*		echo"<pre>";
		print_r($geensnorObject);
		echo"</pre>";*/

	if(!$geensnorObject)
		echo"<br>Import KML bestand niet gelukt. Kan KML niet laden. Zorg dat er een .kml file in de map kmlhiero staat.";
	else{

		$adviesArray = $geensnorObject->Document->Placemark;

		if($adviesArray){
			R::getAll("DELETE FROM advies");
			$i=0;
			foreach ($adviesArray as $element) {
				//echo $element->ExtendedData->Data->value."<br>";
				$locArray = explode(",", $element->Point->coordinates);


				$a = R::dispense('advies');     
				$a->name = (string)$element->name;
				//$a->description = (string)$element->description;
				$descriptionString = (string)$element->description;
				
				//$a->description = substr($descriptionString, 0, strpos($descriptionString, "<br><br>gx_image_links:"));

				$a->description = strip_tags($descriptionString);

				//$a->image = (string)$element->ExtendedData->Data->value;
				$a->lat = $locArray[1];
				$a->lon = $locArray[0];
				$a->styleUrl = (string)$element->styleUrl;
				$a->creationDate = date("Y-m-d H:i:s");
			   	R::store($a);
			    $i++;
			}
			echo"Import gelukt!<br><br> Er staan nu ".$i." adviezen in de database";
		}
		else{
			echo"<br>Import KML bestand niet gelukt. KML heeft niet de goede structuur:";
/*			echo"<pre>";
			print_r($geensnorObject);
			echo"</pre>";*/
		}

	}


}


/*

$adviesArray = $geensnorObject->Document->Folder->Placemark;

R::getAll("DELETE FROM advies");

foreach ($adviesArray as $element) {
	echo $element->ExtendedData->Data->value."<br>";
	$locArray = explode(",", $element->Point->coordinates);


	$a = R::dispense('advies');     
	$a->name = (string)$element->name;
	$a->description = (string)$element->ExtendedData->Data->value;
	$a->lat = $locArray[1];
	$a->lon = $locArray[0];
	$a->styleUrl = (string)$element->styleUrl;
	$a->creationDate = date("Y-m-d H:i:s");
    R::store($a);
}

*/

?>