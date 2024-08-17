<?php

spl_autoload_register(function ($class) {
    static $classmap;
    // DIRECTORY_SEPARATOR is not defined here (?)

    if (!isset($classmap)) {
        $classmap = [
            "H5PVT2ER\H5PFileHandler" => "H5PFileHandler.php",
            "H5PVT2ER\FileUtils" => "utils/FileUtils.php",
            "H5PVT2ER\GeneralUtils" => "utils/GeneralUtils.php",
            "H5PVT2ER\H5PUtils" => "utils/H5PUtils.php",
            "H5PVT2ER\JSONUtils" => "utils/JSONUtils.php",
            "H5PVT2ER\LocaleUtils" => "utils/LocaleUtils.php"
        ];
    }

    if (isset($classmap[$class])) {
        require_once __DIR__ . "/" . $classmap[$class];
    }
});
