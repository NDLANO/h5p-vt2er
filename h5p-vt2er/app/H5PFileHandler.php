<?php

/**
 * Tool for helping people to take Caretaker of H5P content.
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
 * Class for generating HTML for H5P content.
 *
 * @category Tool
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */
class H5PFileHandler
{
    protected $uploadsDirectory;
    protected $cacheDirectory;
    protected $filesDirectory;
    protected $h5pInfo;

    /**
     * Constructor.
     *
     * @param string $file        The H5P file to handle.
     * @param string $uploadsPath The path to the uploads directory.
     *                            Will default to "uploads" in current directory.
     */
    public function __construct($file, $uploadsPath)
    {
        $this->uploadsDirectory = $uploadsPath;

        try {
            $this->filesDirectory = $this->extractContent($file);
        } catch (\Exception $error) {
            throw new \Exception($error->getMessage());
        }

        try {
            $this->h5pInfo = $this->extractH5PInformation();
        } catch (\Exception $error) {
            throw new \Exception($error->getMessage());
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (!isset($this->filesDirectory)) {
            return;
        }

        $this->collectGarbage();
        $this->deleteDirectory($this->filesDirectory);
    }

    /**
     * Get the libraries information.
     *
     * @return array The libraries information.
     */
    public function getLibrariesInformation()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return [];
        }

        $dirNames = $this->getLibrariesDirNames();
        $libraryDetails = array_map([$this, "getLibraryDetails"], $dirNames);

        return array_reduce(
            $libraryDetails,
            function ($results, $details) {
                if (isset($details->machineName)) {
                    $results[$details->machineName] = $details;
                }
                return $results;
            },
            []
        );
    }

    /**
     * Check if the H5P file is okay.
     *
     * @return bool True if the file is okay, false otherwise.
     */
    public function isFileOkay()
    {
        return isset($this->filesDirectory) && $this->filesDirectory !== false;
    }

    /**
     * Get the uploads directory for the H5P files.
     *
     * @return string The upload directory for the H5P files.
     */
    public function getUploadsDirectory()
    {
        return $this->uploadsDirectory;
    }

    /**
     * Get the file directory for the H5P files.
     *
     * @return string The file directory for the H5P files.
     */
    public function getFilesDirectory()
    {
        return $this->filesDirectory;
    }

    /**
     * Get the H5P content informaton from h5p.json.
     *
     * @param string $property The property to get or null to get full information.
     *
     * @return string|array|null  H5P content type CSS, null if not available.
     */
    public function getH5PInformation($property = null)
    {
        if (!isset($this->h5pInfo)) {
            return null;
        }

        return isset($property)
            ? $this->h5pInfo[$property] ?? null
            : $this->h5pInfo;
    }

    /**
     * Write the H5P content informaton to h5p.json.
     *
     * @param array $h5pInfo The H5P content information to write.
     *
     * @return bool True if writing was successful, false otherwise.
     */
    public function writeH5PInformation($h5pInfo)
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $h5pJsonFile = $extractDir . DIRECTORY_SEPARATOR . "h5p.json";
        $h5pContents = json_encode($h5pInfo, JSON_PRETTY_PRINT);

        return file_put_contents($h5pJsonFile, $h5pContents) !== false;
    }

    /**
     * Get the H5P content parameters from the content.json file.
     *
     * @return array|bool Content parameters if file exists, false otherwise.
     */
    public function getH5PContentParams()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $contentDir = $extractDir . DIRECTORY_SEPARATOR . "content";
        if (!is_dir($contentDir)) {
            return false;
        }

        $contentJsonFile = $contentDir . DIRECTORY_SEPARATOR . "content.json";
        if (!file_exists($contentJsonFile)) {
            return false;
        }

        $contentContents = file_get_contents($contentJsonFile);
        $jsonData = json_decode($contentContents, true);

        if ($jsonData === null) {
            return false;
        }

        return $jsonData;
    }

    /**
     * Write the H5P content parameters to the content.json file.
     *
     * @param array $contentParams The content parameters to write.
     *
     * @return bool True if writing was successful, false otherwise.
     */
    public function writeH5PContentParams($contentParams)
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $contentDir = $extractDir . DIRECTORY_SEPARATOR . "content";
        if (!is_dir($contentDir)) {
            if (!mkdir($contentDir, 0777, true) && !is_dir($contentDir)) {
                return false;
            }
        }

        $contentJsonFile = $contentDir . DIRECTORY_SEPARATOR . "content.json";
        $contentContents = json_encode($contentParams, JSON_PRETTY_PRINT);

        return file_put_contents($contentJsonFile, $contentContents) !== false;
    }

    /**
     * Remove H5P libraries of Virtual Tour.
     *
     * @return bool True if removing was successful, false otherwise.
     */
    public function removeAssets()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $dirs = ["H5P.ThreeImage-0.5", "H5PEditor.ThreeImage-0.5", "H5P.ThreeSixty-0.3"];
        foreach ($dirs as $dir) {
            $dirPath = $extractDir . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($dirPath)) {
                $this->deleteDirectory($dir);
            }
        }
    }

    /**
     * Write the library files for Escape Room content.
     *
     * @return bool True if writing was successful, false otherwise.
     */
    public function writeAssets()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $assetsDir = __DIR__ . DIRECTORY_SEPARATOR . "assets";

        $files = scandir($assetsDir);
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $source = $assetsDir . DIRECTORY_SEPARATOR . $file;
            $destination = $extractDir . DIRECTORY_SEPARATOR . $file;

            if (!$this->copyFilesAndDirectories($source, $destination)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copy files and directories.
     *
     * @param string $source      The source file or directory.
     * @param string $destination The destination file or directory.
     *
     * @return bool True if copying was successful, false otherwise.
     */
    private function copyFilesAndDirectories($source, $destination)
    {
        if (is_dir($source)) {
            @mkdir($destination);
            $files = scandir($source);
            foreach ($files as $file) {
                if ($file == "." || $file == "..") {
                    continue;
                }
                $this->copyFilesAndDirectories(
                    $source . DIRECTORY_SEPARATOR . $file,
                    $destination . DIRECTORY_SEPARATOR . $file
                );
            }
        } else {
            if (!copy($source, $destination)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the directories of the libraries.
     *
     * @return array The directories of the libraries or false.
     */
    private function getLibrariesDirNames()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return [];
        }

        $entries = scandir($extractDir);
        $dirs = array_filter($entries, function ($entry) use ($extractDir) {
            return $entry !== "." &&
                $entry !== ".." &&
                $entry !== "content" &&
                is_dir($extractDir . DIRECTORY_SEPARATOR . $entry);
        });

        return array_values($dirs);
    }

    /**
     * Create a ZIP archive/H5P file.
     *
     * @param string $filename The name for the H5P file.
     *
     * @return string The path to the ZIP archive.
     */
    public function createH5Pfile($filename)
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;

        $zip = new \ZipArchive();
        $zipFile = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $filename . ".h5p";

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception(_("Error creating H5P file ZIP archive."));
        }

        function addFilesToZip($dir, $zip, $extractDir)
        {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    addFilesToZip($filePath, $zip, $extractDir);
                } else {
                    $relativePath = substr($filePath, strlen($extractDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        // Add all directories and files from $extractDir into the ZIP file without changing the directory structure
        addFilesToZip($extractDir, $zip, $extractDir);

        $zip->close();

        return $zipFile;
    }

    /**
     * Extract the content of the H5P file to a temporary directory.
     *
     * @param string $file The H5P file to extract.
     *
     * @return string|false Name of temporary directory or false.
     */
    private function extractContent($file)
    {
        // Create temporary directory with time stamp+uuid for garbage collection
        $directoryName = time() . "-" . GeneralUtils::createUUID();

        $extractDir =
            $this->uploadsDirectory . DIRECTORY_SEPARATOR . $directoryName;
        if (!is_dir($extractDir)) {
            if (!is_writable($this->uploadsDirectory)) {
                throw new \Exception(
                    sprintf(
                        _("Upload directory %s is not writable."),
                        $extractDir
                    )
                );
            }

            if (!mkdir($extractDir, 0777, true) && !is_dir($extractDir)) {
                throw new \Exception(
                    sprintf(
                        _("Could not create upload directory %s."),
                        $extractDir
                    )
                );
            }
        }

        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            throw new \Exception(_("Error extracting H5P file ZIP archive."));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $zip->extractTo($extractDir, $filename);
        }
        $zip->close();

        return $directoryName;
    }

    /**
     * Get the H5P content informaton from h5p.json.
     *
     * @return string|null The H5P content type CSS if it exists, null otherwise.
     */
    private function extractH5PInformation()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;

        if (!is_dir($extractDir)) {
            throw new \Exception(
                _("Directory with extracted H5P files does not exist.")
            );
        }

        $h5pJsonFile = $extractDir . DIRECTORY_SEPARATOR . "h5p.json";

        if (!file_exists($h5pJsonFile)) {
            throw new \Exception(
                _("h5p.json file does not exist in the archive.")
            );
        }

        $jsonContents = file_get_contents($h5pJsonFile);
        $jsonData = json_decode($jsonContents, true);

        if ($jsonData === null) {
            throw new \Exception(_("Error decoding h5p.json file."));
        }

        return $jsonData;
    }

    /**
     * Delete a directory and its contents.
     *
     * @param string $dir The directory to delete.
     *
     * @return void
     */
    private function deleteDirectory($dir)
    {
        $dirWithBase = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($dirWithBase)) {
            return;
        }

        $files = array_diff(scandir($dirWithBase), [".", ".."]);
        foreach ($files as $file) {
            if (is_dir($dirWithBase . DIRECTORY_SEPARATOR . $file)) {
                $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $file);
            } else {
                unlink($dirWithBase . DIRECTORY_SEPARATOR . $file);
            }
        }

        rmdir($dirWithBase);
    }

    /**
     * Delete directories in uploads directory that are older than time difference.
     *
     * @param int $timediff The time difference in seconds.
     *
     * @return void
     */
    private function collectGarbage($timediff = 60)
    {
        $currentTimestamp = time();

        $directories = glob(
            $this->uploadsDirectory . DIRECTORY_SEPARATOR . "*",
            GLOB_ONLYDIR
        );

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $timestamp = intval(explode("-", $dirName)[0] ?? $currentTimestamp);

            if ($currentTimestamp - $timestamp >= $timediff) {
                $this->deleteDirectory($dirName);
            }
        }
    }
}
