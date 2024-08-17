<?php

/**
 * Tool for migrating Virtual Tour content to Escape Room content
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PVT2ER
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "functions.php";

$basePath = __DIR__ . DIRECTORY_SEPARATOR . "h5p-vt2er" . DIRECTORY_SEPARATOR . "app";
require_once $basePath . DIRECTORY_SEPARATOR . "H5PVT2ER.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_FILES["file"])) {
    done(422, "It seems that no file was provided.");
}

$file = $_FILES["file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    done(500, "Something went wrong with the file upload.");
}

$config = [
    "fileSizeLimit" => INF
];

$configFile = __DIR__ . DIRECTORY_SEPARATOR . "config.json";
if (file_exists($configFile)) {
    try {
        $userConfig = json_decode(file_get_contents($configFile), true);
    }
    catch (\Exception $error) {
        // Intentionally left blank
    }
}

if (isset($userConfig)) {
    $config = array_merge($config, $userConfig);
}

$h5pVT2ER = new H5PVT2ER\H5PVT2ER($config);

$result = $h5pVT2ER->migrate([
    "file" => $file["tmp_name"],
    "filename" => $file["name"]
]);

if (isset($result["error"])) {
    done(422, $result["error"]);
}

done(200, $result["result"]);
