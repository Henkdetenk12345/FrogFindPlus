<?php

    $url = "";
    $loc = "US";

    //get the image url
    if (isset( $_GET['i'] ) ) {
        $url = $_GET[ 'i' ];
    } else {
        exit();
    }

    // FIX: original logic was broken (AND instead of OR, wrong checks)
    // Only allow jpg, jpeg, png
    $hasJpg  = strpos($url, ".jpg")  !== false;
    $hasJpeg = strpos($url, ".jpeg") !== false;
    $hasPng  = strpos($url, ".png")  !== false;

    if (!$hasJpg && !$hasJpeg && !$hasPng) {
        echo "Unsupported file type :(";
        exit();
    }

    //image needs to start with http
    if (substr( $url, 0, 4 ) != "http") {
        echo("Image failed :(");
        exit();
    }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
 <head>
     <title>FrogFind Image Viewer</title>
 </head>
 <body>
    <small><a href="<?php echo $_SERVER['HTTP_REFERER'] ?>">< Back to previous page</a></small>
    <p><small><b>Viewing image:</b> <?php echo $url ?></small></p>
    <img src="./image_compressed.php?i=<?php echo $url; ?>">
    <br><br>
    <small><a href="<?php echo $_SERVER['HTTP_REFERER'] ?>">< Back to previous page</a></small>
 </body>
 </html>
