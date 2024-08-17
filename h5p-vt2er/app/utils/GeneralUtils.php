<?php

/**
 * Proof of concept code for extracting and displaying H5P content server-side.
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */

namespace H5PVT2ER;

/**
 * Class for general utility functions.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */
class GeneralUtils
{
    /**
     * Create a UUID.
     *
     * @return string The UUID.
     */
    public static function createUUID()
    {
        return preg_replace_callback(
            "/[xy]/",
            function ($match) {
                $random = random_int(0, 15);
                $newChar = $match[0] === "x" ? $random : ($random & 0x3) | 0x8;
                return dechex($newChar);
            },
            "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
        );
    }
}
