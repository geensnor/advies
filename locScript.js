var range;

function initiate_geolocation() {  
    navigator.geolocation.getCurrentPosition(handle_geolocation_query, handle_errors);  
}  
 

function handle_errors(error)  {
    $(".status").html("Locatie kan niet worden bepaald<br><br>Alle adviezen van geensnor.nl staan op Google maps:<br><br><a href='http://mapsengine.google.com/map/embed?mid=zm8_BTzve0-k.kDtxgaZuNueU'>http://mapsengine.google.com/map/embed?mid=zm8_BTzve0-k.kDtxgaZuNueU</a>");
  
/*
    switch(error.code)  
    {  
        case error.PERMISSION_DENIED: alert("user did not share geolocation data");  
        break;  

        case error.POSITION_UNAVAILABLE: alert("could not detect current position");  
        break;  

        case error.TIMEOUT: alert("retrieving position timed out");  
        break;  

        default: alert("unknown error");  
        break;  
    }  
*/
}  

function handle_geolocation_query(position){  
    if(range){
        $(".list").load("list.php?lat=" + position.coords.latitude + "&lon=" + position.coords.longitude + "&range=" + range);
    }
    else{
        $(".list").load("list.php?lat=" + position.coords.latitude + "&lon=" + position.coords.longitude);
    }
        
}  

$(document).ready( function() {
    initiate_geolocation();
    
    $(".rangeSelector").change(function() {
        range = $(".rangeSelector").val();
        initiate_geolocation();
    });
    
    $(".listTitle").live('click', function() {    
        $(".main").load("message.php?id=" + $(this).attr('id'));
    });
    

});


/*
jQuery(window).ready(function(){  
    //jQuery("#btnInit").click(initiate_geolocation);  
    initiate_geolocation();
});

*/