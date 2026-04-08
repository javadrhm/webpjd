<?php

// ---- BASIC SECURITY (optional but recommended) ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Only POST allowed');
}

// ---- CHECK REQUIRED FIELDS ----
if (!isset($_POST['action']) || $_POST['action'] !== 'convert') {
    http_response_code(400);
    exit('Invalid action');
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    exit('No file');
}

// optional API key check (plugin uses zhaketweb)
if (isset($_POST['api-key']) && $_POST['api-key'] !== 'zhaketweb') {
    http_response_code(403);
    exit('Invalid API key');
}

// ---- QUALITY ----
$quality = 85;
if (isset($_POST['options'])) {
    $opts = json_decode($_POST['options'], true);
    if (isset($opts['quality'])) {
        $quality = max(10, min(100, (int)$opts['quality']));
    }
}

// ---- LOAD IMAGE ----
$tmp = $_FILES['file']['tmp_name'];
$mime = mime_content_type($tmp);

switch ($mime) {
    case 'image/jpeg':
        $img = imagecreatefromjpeg($tmp);
        break;

    case 'image/png':
        $img = imagecreatefrompng($tmp);
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        break;

    case 'image/gif':
        $img = imagecreatefromgif($tmp);
        break;

    default:
        http_response_code(415);
        exit('Unsupported format');
}

if (!$img) {
    http_response_code(500);
    exit('Image load failed');
}

// ---- OUTPUT WEBP (CRITICAL PART) ----
header('Content-Type: image/webp');

// capture output buffer (IMPORTANT for plugin compatibility)
ob_start();
imagewebp($img, null, $quality);
$data = ob_get_clean();

imagedestroy($img);

// ---- ENSURE VALID WEBP SIGNATURE ----
// plugin checks for "WEBPVP8"
if (strpos($data, 'WEBP') === false) {
    http_response_code(500);
    exit('Conversion failed');
}

// ---- RETURN RAW BINARY ----
echo $data;
exit;
