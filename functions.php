<?php

/**
 * Exit the script with an optional HTTP status code.
 *
 * @param int    $code    The HTTP status code to send.
 * @param string $message The message to display.
 *
 * @return void
 */
function done($code, $message)
{
    if (isset($message)) {
        if ($code !== 200) {
            echo $message;
        } else {
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"" . basename($message) . "\"");
            header("Content-Length: " . filesize($message));

            // Clear output buffer and flush system output buffer
            ob_clean();
            flush();

            // Read the file and send it to the output
            readfile($message);

            // Optionally, delete the ZIP file after download
            unlink($message);
        }
    }

    if (isset($code)) {
        http_response_code($code);
    };

    exit();
}
