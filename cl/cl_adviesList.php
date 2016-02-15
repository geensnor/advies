<?php
class adviesList{
    function get($lat, $lon){//maxDistance is maximale afstand in meters
        $_SESSION["loc"]["lon"] = $lon;
        $_SESSION["loc"]["lat"] = $lat;
        
    
        if($maxDistance){
            if(MAXDISTANCE < $maxDistance)  
                $maxDistance = MAXDISTANCE;
        }
        else{
            $maxDistance = DEFAULTDISTANCE;
        }

        $query = "SELECT *, round(( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin( radians( lat ) ) ) ) * 10) AS distance 
        FROM advies ORDER BY distance LIMIT 0 , 20";
        //echo $query;
        //HAVING distance < ".$maxDistance."
        return R::getAll($query);
        
    }
}

?>