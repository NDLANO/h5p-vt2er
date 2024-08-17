<?php

/**
 * Library for migrating Virtual Tour content to Escape Room content
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */

namespace H5PVT2ER;

/**
 * Main class.
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-vt2er
 */
class H5PVT2ER
{
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config The configuration.
     */
    public function __construct($config = [])
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . "autoloader.php";

        $config["locale"] = $config["locale"] ?? 'en';

        $language = LocaleUtils::getCompleteLocale($config["locale"]);
        if (isset($language)) {
            putenv("LANG=" . $language);
            putenv("LANGUAGE=" . $language);

            $domain = "h5p_vt2er";
            $bindPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . "locale");
            bindtextdomain($domain, $bindPath);
            textdomain($domain);
        }

        if (!isset($config["uploadsPath"])) {
            $config["uploadsPath"] =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                ".." .
                DIRECTORY_SEPARATOR .
                "uploads";
        }

        if (!isset($config["fileSizeLimit"])) {
            $config["fileSizeLimit"] = INF;
        }

        $this->config = $config;
    }

    /**
     * Done.
     *
     * @param string|null $result The result. Should be null if there is an error.
     * @param string|null $error  The error. Should be null if there is no error.
     *
     * @return array The result or error.
     */
    private function done($result, $error = null)
    {
        if (isset($error)) {
            $result = null;
        } elseif (!isset($result)) {
            $error = _("Something went wrong, but I dunno what, sorry!");
        }

        return [
            "result" => $result,
            "error" => $error,
        ];
    }

    /**
     * Migrate content.
     *
     * @param array $params The parameters. file (tmp file), format (html, text).
     *
     * @return array The result or error.
     */
    public function migrate($params)
    {
        if (!isset($params["file"])) {
            $this->done(null, _("It seems that no file was provided."));
        }

        $file = $params["file"];

        $fileSize = filesize($file);
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $file);

        if ($fileSize === 0) {
            return $this->done(null, _("The file is empty."));
        }

        if ($fileSize > $this->config["fileSizeLimit"]) {
            return $this->done(
                null,
                sprintf(
                    _("The file is larger than the limit of %s bytes."),
                    $this->config["fileSizeLimit"]
                )
            );
        }

        if ($fileType !== "application/zip") {
            return $this->done(
                null,
                _("The file is not a valid H5P file / ZIP archive.")
            );
        }

        try {
            $h5pFileHandler = new H5PFileHandler(
                $file,
                $this->config["uploadsPath"]
            );
        } catch (\Exception $error) {
            return $this->done(null, $error->getMessage());
        }

        if (!$h5pFileHandler->isFileOkay()) {
            return $this->done(
                null,
                _("The file does not seem to follow the H5P specification.")
            );
        }

        try {
            $h5pJson = $this->migrateH5PJson($h5pFileHandler);
        } catch (\Exception $error) {
            return $this->done(null, $error->getMessage());
        }

        $h5pFileHandler->writeH5PInformation($h5pJson);

        $h5pFileHandler->removeAssets();
        $h5pFileHandler->writeAssets();

        // Writing assets before migrating content parameters to ensure language file is available
        $contentJson = $this->migrateH5PContentParams(
            $h5pFileHandler,
            $h5pJson["mainLibrary"] ?? "H5P.EscapeRoom",
            $h5pJson["language"] ?? "en"
        );
        $h5pFileHandler->writeH5PContentParams($contentJson);

        $filename = "escape-room-" . $params["filename"];
        if (str_ends_with($filename, ".h5p")) {
            $filename = substr($filename, 0, -4);
        }

        try {
            $zipFileName = $h5pFileHandler->createH5PFile($filename);
        } catch (\Exception $error) {
            $h5pFileHandler = null;
            return $this->done(null, $error->getMessage());
        }

        $h5pFileHandler = null;

        return $this->done($zipFileName);
    }

    /**
     * Migrate H5P JSON.
     *
     * @param H5PFileHandler $h5pFileHandler The H5P file handler.
     *
     * @return array The migrated H5P JSON.
     */
    private function migrateH5PJson($h5pFileHandler)
    {
        $h5pJson = $h5pFileHandler->getH5PInformation();

        if ($h5pJson["mainLibrary"] !== "H5P.ThreeImage") {
            throw new \Exception(_("The content type is not a Virtual Tour."));
        }

        if (isset($h5pJson["preloadedDependencies"]) && is_array($h5pJson["preloadedDependencies"])) {
            $filteredDependencies = array_filter($h5pJson["preloadedDependencies"], function ($dependency) {
                return $dependency["machineName"] === "H5P.ThreeImage";
            });
            $versionInfo = array_shift($filteredDependencies);
        } else {
            $versionInfo = null;
        }

        $majorVersion = intval($versionInfo["majorVersion"] ?? '');
        $minorVersion = intval($versionInfo["minorVersion"] ?? '');

        if (
            !isset($versionInfo) ||
            gettype($majorVersion) !== "integer" ||
            gettype($minorVersion) !== "integer"
        ) {
            throw new \Exception(
                _("There is no version information for the Virtual Tour library.")
            );
        }

        if ($majorVersion === 0 && $minorVersion < 5) {
            throw new \Exception(
                _("Please upgrade your Virtual Tour content to version 0.5.")
            );
        }

        if ($majorVersion !== 0 || $minorVersion > 5) {
            throw new \Exception(
                _("The version of the Virtual Tour content is not supported yet.")
            );
        }

        $h5pJson["mainLibrary"] = "H5P.EscapeRoom";

        $h5pJson["preloadedDependencies"] = [
            ["machineName" => "FontAwesome", "majorVersion" => 4, "minorVersion" => 5],
            ["machineName" => "H5P.Transition", "majorVersion" => 1, "minorVersion" => 0],
            ["machineName" => "H5P.FontIcons", "majorVersion" => 1, "minorVersion" => 0],
            ["machineName" => "H5P.JoubelUI", "majorVersion" => 1, "minorVersion" => 3],
            ["machineName" => "H5P.ThreeJS", "majorVersion" => 1, "minorVersion" => 0],
            ["machineName" => "H5P.Question", "majorVersion" => 1, "minorVersion" => 5],
            ["machineName" => "H5P.TextUtilities", "majorVersion" => 1, "minorVersion" => 3],
            ["machineName" => "H5P.Image", "majorVersion" => 1, "minorVersion" => 1],
            ["machineName" => "H5P.MaterialDesignIcons", "majorVersion" => 1, "minorVersion" => 0],
            ["machineName" => "H5P.NDLAThreeSixty", "majorVersion" => 0, "minorVersion" => 5],
            ["machineName" => "H5PEditor.TableList", "majorVersion" => 1, "minorVersion" => 0],
            ["machineName" => "H5P.AdvancedText", "majorVersion" => 1, "minorVersion" => 1],
            ["machineName" => "H5P.Audio", "majorVersion" => 1, "minorVersion" => 5],
            ["machineName" => "H5P.Video", "majorVersion" => 1, "minorVersion" => 6],
            ["machineName" => "H5P.Summary", "majorVersion" => 1, "minorVersion" => 10],
            ["machineName" => "H5P.SingleChoiceSet", "majorVersion" => 1, "minorVersion" => 11],
            ["machineName" => "H5P.MultiChoice", "majorVersion" => 1, "minorVersion" => 16],
            ["machineName" => "H5P.Blanks", "majorVersion" => 1, "minorVersion" => 14],
            ["machineName" => "H5P.Crossword", "majorVersion" => 0, "minorVersion" => 5],
            ["machineName" => "H5P.EscapeRoom", "majorVersion" => 0, "minorVersion" => 5]
        ];

        return $h5pJson;
    }

    /**
     * Migrate H5P content parameters.
     *
     * @param H5PFileHandler $h5pFileHandler The H5P file handler.
     * @param string         $language       The language.
     *
     * @return array The migrated H5P content parameters.
     */
    private function migrateH5PContentParams($h5pFileHandler, $machineName = "H5P.EscapeRoom", $language = "en")
    {
        $contentJson = $h5pFileHandler->getH5PContentParams();

        for ($i = 0; $i < count($contentJson["threeImage"]["scenes"] ?? []); $i++) {
            $contentJson["threeImage"]["scenes"][$i]["enableZoom"] = false;

            for ($j = 0; $j < count($contentJson["threeImage"]["scenes"][$i]["interactions"] ?? []); $j++) {
                $contentJson["threeImage"]["scenes"][$i]["interactions"][$j]["iconTypeTextBox"] = "text-icon";
                $contentJson["threeImage"]["scenes"][$i]["interactions"][$j]["showAsHotspot"] = false;
                $contentJson["threeImage"]["scenes"][$i]["interactions"][$j]["showAsOpenSceneContent"] = false;
            }
        }

        $newKeys = [
            "title" => _("Title"),
            "playAudioTrack" => _("Play audio track"),
            "pauseAudioTrack" => _("Pause audio track"),
            "sceneDescription" => _("Scene description"),
            "resetCamera" => _("Reset camera"),
            "submitDialog" => _("Submit dialog"),
            "closeDialog" => _("Close dialog"),
            "expandButtonAriaLabel" => _("Expand the visual label"),
            "backgroundLoading" => _("Loading background image ..."),
            "noContent" => _("No content"),
            "goToScene" => _("Go to scene"),
            "edit" => _("Edit"),
            "delete" => _("Delete"),
            "score" => _("Score"),
            "assignment" => _("Assignment"),
            "total" => _("Total"),
            "scoreSummary" => _("Show score summary"),
            "scene" => _("Scene"),
            "untitled" => _("Untitled"),
            "userIsAtStartScene" => _("You are at the start scene"),
            "unlocked" => _("Unlocked"),
            "locked" => _("Locked"),
            "searchRoomForCode" => _("Search the room until you find the code"),
            "wrongCode" => _("The code was wrong, try again."),
            "contentUnlocked" => _("The content has been unlocked!"),
            "code" => _("Code"),
            "lockedStateAction" => _("Unlock"),
            "hotspotDragHorizAlt" => _("Drag horizontally to scale"),
            "hotspotDragVertiAlt" => _("Drag vertically to scale"),
            "hint" => _("Hint"),
            "lockedContent" => _("Locked content"),
            "back" => _("Back"),
            "buttonFullscreenEnter" => _("Enter fullscreen mode"),
            "buttonFullscreenExit" => _("Exit fullscreen mode"),
            "mainToolbar" => _("Main toolbar"),
            "noValidSceneSet" => _("No valid scenes have been set."),
            "buttonZoomIn" => _("Zoom in"),
            "buttonZoomOut" => _("Zoom out"),
            "zoomToolbar" => _("Zoom toolbar"),
            "zoomAriaLabel" => _("num% zoomed in"),
        ];

        foreach ($newKeys as $key => $translation) {
            $contentJson["l10n"][$key] = $contentJson["l10n"][$key] ?? $translation;
        }

        return $contentJson;
    }
}
