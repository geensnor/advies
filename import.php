<?php
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
			
			return simplexml_load_file("doc.kml");
		} else {
		  echo "Kan bestand niet openen";
		}
}

$aantalAdviezen = R::count('advies');
$laatsteUpdate = R::getAll("SELECT max(creationDate) as maxCreationDate FROM advies");


if(!$_GET["kml"] && !$_GET["kmz"])
	echo "Adviezen in de database: ".$aantalAdviezen."<br>
			Laatste update: ".$laatsteUpdate[0]["maxCreationDate"]."
			<br><br>Geen KMZ url<br>Voer achter http://advies.geensnor.nl/import.php?kmz= de url van de KMZ file in. KML kan ook. Dan moet je ?kml= achter de url plakken<br><br>Bijvoorbeeld:<br>https://www.google.com/maps/d/kml?mid=zm8_BTzve0-k.kDtxgaZuNueU&nl=1&lid=zm8_BTzve0-k.kNkvvj2O99Xk&cid=mp&cv=5HRqu9noLVs.nl.

	<br><br>Als er niets is gewijzigd zou hieronder klikken voldoende moeten zijn<br>
	<a href='http://advies.geensnor.nl/import.php?kmz=https://www.google.com/maps/d/kml?mid=zm8_BTzve0-k.kDtxgaZuNueU&nl=1&lid=zm8_BTzve0-k.kNkvvj2O99Xk&cid=mp&cv=5HRqu9noLVs.nl.'>http://advies.geensnor.nl/import.php?kmz=https://www.google.com/maps/d/kml?mid=zm8_BTzve0-k.kDtxgaZuNueU&nl=1&lid=zm8_BTzve0-k.kNkvvj2O99Xk&cid=mp&cv=5HRqu9noLVs.nl.</a>";
else{
	if($_GET["kml"])
		$geensnorObject = simplexml_load_file($_GET["kml"]);
	if($_GET["kmz"]){
		//https://www.google.com/maps/d/kml?mid=zm8_BTzve0-k.kDtxgaZuNueU&nl=1&lid=zm8_BTzve0-k.kNkvvj2O99Xk&cid=mp&cv=5HRqu9noLVs.nl.


		$locationXML = kmztoxml($_GET["kmz"]);

		$geensnorObject = kmztoxml($locationXML->Document->NetworkLink->Link->href);


	}
	if(!$geensnorObject)
		echo"<br>Import KML bestand niet gelukt. Kan KML niet laden";
	else{

		$adviesArray = $geensnorObject->Document->Folder->Placemark;

/*		echo"<pre>";
		print_r($adviesArray);
		echo"</pre>";*/

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
				
				$a->description = substr($descriptionString, 0, strpos($descriptionString, "<br><br>gx_image_links:"));

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
		else
			echo"<br>Import KML bestand niet gelukt. KML heeft niet de goede structuur:";
/*			echo"<pre>";
			print_r($geensnorObject);
			echo"</pre>";*/

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