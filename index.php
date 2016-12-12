<?php
include("include.php");

echo"
<!DOCTYPE html>
<head>
    <meta charset='UTF-8'>
    <title>Geensnor adviseert...(20)</title>
    <link href='main.css' rel='stylesheet' type='text/css'>
    <link rel='apple-touch-icon' href='gsadviseertlogo128.jpg'/>

    <script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js'></script>
    <script type='text/javascript' src='locScript.js'></script>
    <script type='text/javascript' src='script.js'></script>
    <script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

          ga('create', 'UA-3132441-3', 'geensnor.nl');
          ga('send', 'pageview');

    </script>
    <meta name='viewport' content='width=device-width, initial-scale = 1.0, maximum-scale = 1.0'/>
    <meta name='apple-mobile-web-app-capable' content='yes' />

  
</head>
<body>
    <div class='header'><a href='http://www.geensnor.nl/geensnor/index.php?page=bericht&iid=12125'>Wat is dit?</a> | <a href='https://goo.gl/maps/5u5FzBgm1a82'>Alle adviezen op Google maps</a> | <a href='https://plus.google.com/communities/105570140054811466646'>Geensnor op Google+</a></div>
    <div class='main'> 
        <h1>Geensnor adviseert</h1>
        <div class='status'></div>
        <div class='list'></div>        
    </div>
</body>
</html>
";
R::close();
?>
