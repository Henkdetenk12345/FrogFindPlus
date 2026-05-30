<?php

$url = "";
$filetype = "";
$raw_image = NULL;

if (isset($_GET['i'])) {
    $url = $_GET['i'];
} else {
    exit();
}

if (substr($url, 0, 4) != "http") {
    exit();
}

if (strpos($url, ".jpg") !== false || strpos($url, ".jpeg") !== false) {
    $filetype = "jpg";
} elseif (strpos($url, ".png") !== false) {
    $filetype = "png";
} else {
    exit();
}

// Fetch image with cURL so we can set a User-Agent (imagecreatefrom* gets blocked by many servers)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) ' .
    'Chrome/120.0.0.0 Safari/537.36'
);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$image_data = curl_exec($ch);
$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$image_data || $http_code >= 400) {
    exit();
}

// Create image from the downloaded bytes
$raw_image = imagecreatefromstring($image_data);
if (!$raw_image) {
    exit();
}

$raw_imagex = imagesx($raw_image);
$raw_imagey = imagesy($raw_image);

if ($raw_imagex >= $raw_imagey) {
    $dest_imagex = 300;
    $dest_imagey = intval(($raw_imagey / $raw_imagex) * $dest_imagex);
} else {
    $dest_imagey = 200;
    $dest_imagex = intval(($raw_imagex / $raw_imagey) * $dest_imagey);
}

// Minimum 1px to avoid errors
$dest_imagex = max(1, $dest_imagex);
$dest_imagey = max(1, $dest_imagey);

$dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);

imagecopyresampled($dest_image, $raw_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $raw_imagex, $raw_imagey);

header('Content-type: image/' . $filetype);
if ($filetype == "jpg") {
    imagejpeg($dest_image, NULL, 80);
} elseif ($filetype == "png") {
    imagepng($dest_image, NULL, 8);
}

imagedestroy($raw_image);
imagedestroy($dest_image);
