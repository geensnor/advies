<?php
session_start();
header("Content-Type: text/html; charset=utf-8");
define("MAXDISTANCE", 500001); //Maximale zoekstraal in meters
define("DEFAULTDISTANCE", 100); //Default zoekstraal in meters

//DB Spullen
include_once("cl/rb.php");

/*$database = "";
$username = "";
$password = "";*/

$database = "advies";
$username = "root";
$password = "root";

R::setup('mysql:host=localhost;dbname='.$database, $username, $password);

include_once("cl/cl_adviesList.php");

function getIconByStyle($styleUrl){

	switch ($styleUrl) {
    case "#icon-979":
        return "https://www.gstatic.com/mapspro/images/stock/979-biz-bar.png";
        break;
    case "#icon-1023":
        return "https://www.gstatic.com/mapspro/images/stock/1023-biz-food.png";
        break;
    case "#icon-1035":
        return "https://www.gstatic.com/mapspro/images/stock/1035-biz-hotel.png";
        break;
    case "#icon-1081":
        return "https://www.gstatic.com/mapspro/images/stock/1081-biz-restaurant-fastfood.png";
        break;
    case "#icon-1085":
        return "https://www.gstatic.com/mapspro/images/stock/1085-biz-restaurant-generic.png";
        break;
    case "#icon-1101":
        return "https://www.gstatic.com/mapspro/images/stock/1101-biz-supermarket.png";
        break;
    case "#icon-1115":
        return "https://www.gstatic.com/mapspro/images/stock/1115-biz-video.png";
        break;
    case "#icon-1137":
        return "https://www.gstatic.com/mapspro/images/stock/1137-crisis-death.png";
        break;
    case "#icon-1409":
        return "https://www.gstatic.com/mapspro/images/stock/1409-rec-winter-ski.png";
        break;
    case "#icon-1421":
        return "https://www.gstatic.com/mapspro/images/stock/1421-trans-boat-launch.png";
        break;
//    case "#icon-991":
//        return "https://www.gstatic.com/mapspro/images/stock/1421-trans-boat-launch.png"; <- klopt niet, maar ik kan het icoon niet vinden
//        break;
    default:
        return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAiCAYAAABfqvm9AAADYElEQVRIS2NkwAEKFBQEmLnZ/P8z/HdgZGRQACn7/5/hASMD44G/X39tnPDgwQdsWhmxCRbpqDUwMTAU/Wdg4OXlYP8hzMPNAlL39svXP59//OQAavr8j4Ght+/KrUZ0/SgGgl3Fw7qegYHRwURelsFOTYVBSlAARc+z9x8YDty8/evcoydsQDcf+PvldyCya1EMLNJRPcDOzGIdaKTPYqoojys0wOKn7z9kWHfu4u/ff/8c7b1y2xGmGG4gyJtATn24qREDIcNgmkGGrjx9jgEYNI1A7zeAxMEGgrzKwsP2yFheljfC3ASvy9All504/ev8oyc//3z5JQfyOtjAIm3VBEZGxvlFro4YYUbIdFCY9u3eD0wB/xP7rt5eADawWFt1AR8HR0Sdvxc7IQOwyVet2/j316+/U3qv3S6AuBAYGUoiIjbZTnbM5Bg4Zd/BP/ffvD3ad+W2A1UMnLrv0N97b94cgRsI8jIvJ0d4vZ8XBzkuxPQytSMFmmweG8rKsEdZmrKS4splx0//Pv8YnGxk4ckGEjFUTNgwF4Fim5+dwyLFzoodPQ+juxqe/hj+HwRFBkbWgwmU6KhfZmNh0qrx9mDiZAfmfyzg+89fDC1bd/z79efftT9fftriLBxAeku1VQ3+MzIeluDn48xysGVGNxRk2LQDh/+++PjpO+P//7bdV29fQLYTa3lYrKkWwMDMsB5bQQErEBgZ/jn2XLlzAN0DWA0Eu1RP7QgfO6dlja8HsKxFgNbNO/6/+/7tEHK4EXQhONahaTPTwYZBWUwUrOfuq9cM0w8cgRcE2MIXpwvBrtRVe6IpKSmdaGMB1jv/yAmG68+fP+2+fEsGa2wBBfEaWKylOoGBiTG/JywQrL9kFbB2+Pd/IqhUIc9AaOSAvA0CIO/iigyYBXhdCKm02N77GegwfP/9h2H31RsMf7/8EsRVhYIMxWsg2Js6qo+0pKRkQexrz5497rlyWw6Xd4kyEFS08XNxRf/4/Yv55+8/i3qv3k6gyMAibZUCRkamfpAh////K+y7emcCRQaW6Kg4/Gdg2g/xDvbcgWwBEWFIZQNhMQ1yBaEYJipSQIqKddSAjQNg6+jKLYI+IqgAknTUPoPoniu3ePFFCNEuBJXkIMW4ShiSIoWQi9DlAcbRhzKmZU8mAAAAAElFTkSuQmCC";
     }    
}
?>