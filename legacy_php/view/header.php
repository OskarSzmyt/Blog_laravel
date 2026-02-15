<!DOCTYPE html>
<html>
<head>
    <title>Demo - Zadanie 7 - WWW i jzyki skryptowe</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="zadanie7.js"></script>
</head>
<body>
<header>
  <h1>
      Demo - Zadanie 7
  </h1>
  <h2>
      Forum - dynamiczne modyfikacje strony, JavaScript, jQuery i AJAX
  </h2>
</header>
<nav>
        <a href="../">Home</a>
        <?php for($n=1;$n<=10;$n++) { if( is_dir("../zadanie".$n) ) { ?>
        <a href="../zadanie<?=$n?>">Zadanie <?=$n?></a>
        <?php } } ?>
</nav>