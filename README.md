# H5P VT2ER
Tool that allows to migrate Virtual Tour contents to Escape Room contents.

## Prerequisites
- Server running with PHP 8+ and ZipArchive module installed.

## Setup
### Installation
- Download the contents of this archive.
- Copy the files to your server or a subdirectory of your server.
- Ensure that the files can be accessed by your server. Usually the respective owner of all the files and its
  directories should be `daemon` or `www-data`. In particular, the server will be able to write to the `uploads`
  folder for temporarily storing the files that need to be processed.

### Configuration
You can add a `config.json` file to this tool's top directory to tweak settings to your needs.

You can decide to set:
- __uploadsPath__ in order to let the tool use a specific path for uploads. By default, it will try to use its own `uploads` directory.
- __fileSiteLimit__ in order to let the tool limit the size of the file that can be uploaded in bytes. By default, there is no limit.

A configuration file could look like this.
```
{
  "uploadsPath": "<some_absolute_path>"
  "fileSizeLimit": 52428800
}
```

## Usage
Point a browser to the location of where you installed the files, the `index.html` file in particular. You should see a brief explanatory text and a button that you can use to pick a "H5P Virtual Tour" content file from your local storage. Once chosen the tool will try to convert the file and either display an error message or offer you the converted file for download.
