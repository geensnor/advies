<?php
include_once("include.php");

/*
echo"<pre>";
print_r($_GET);
echo"</pre>";  
//*/



$al = new adviesList;
$adviezen = $al->get($_GET["lat"], $_GET["lon"]);


$plaatsData = json_decode(file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=".$_GET["lat"].",".$_GET["lon"]."&sensor=false"));



echo "<h2>in de buurt van de ".$plaatsData->results[0]->address_components[1]->short_name." in ".$plaatsData->results[0]->address_components[4]->short_name."</h2>";

echo"

<table>";
foreach($adviezen  as $key => $value){

if(stristr($_SERVER['HTTP_USER_AGENT'],'ipad') || stristr($_SERVER['HTTP_USER_AGENT'],'iphone'))
  $mapAppString = " - <a href='comgooglemaps://?q=".urlencode($adviezen[$key]["name"])."&center=".$adviezen[$key]["lon"].",".$adviezen[$key]["lat"]."&zoom=18'>google maps app</a>";
if(stristr($_SERVER['HTTP_USER_AGENT'],'Android'))
  $mapAppString = " - <a href='geo:".$adviezen[$key]["lat"].",".$adviezen[$key]["lon"]."?q=".urlencode($adviezen[$key]["name"])."'>google maps app</a>";

echo"    
    <tr>
        <td>
            <div class='listTitle'><img src='".getIconByStyle($adviezen[$key]["styleUrl"])."'><a href='http://maps.google.com/maps?q=".urlencode($adviezen[$key]["name"])."&ll=".$adviezen[$key]["lat"].",".$adviezen[$key]["lon"]."'>".$adviezen[$key]["name"]."</a></div>
            <div class='listDescription'>".$adviezen[$key]["description"]."</div>
            <div class='listSub'>
              bekijk met: <a href='http://maps.google.com/maps?q=".urlencode($adviezen[$key]["name"])."&ll=".$adviezen[$key]["lat"].",".$adviezen[$key]["lon"]."'>maps.google.com</a>";
              if($mapAppString)
                echo $mapAppString; 
              
              
             echo " - ".number_format(($adviezen[$key]["distance"]/10), 1)." km
             </div>
        <td>
    </tr>";
}
echo"
</table>";


//$urlencodedSearchString = urlencode("Rob's Place Culitaria");
//echo"<a href='comgooglemaps://?q=".$urlencodedSearchString."&center=51.810834,5.728519000000006&zoom=18'>Robs in googlemaps!!</a>";


/*
    echo"<pre>";
       print_r($ml->get(1000));
       echo"</pre>";  
  */   
//echo "leeftijd sessie ".(time() - $_SESSION["loc"]["age"]) / 60;
       
       

?>