# Craft CMS Plugins Analyzer
This CLI tool allows you to analyze data about available plugins in the Craft CMS ecosystem.

## Requirements
- PHP 8+
- Composer

## Installation (Requires PHP 8+)
The tool is not currently available on packagist but it can be installed from this repo directly. 

```shell
composer global config repositories.craft-plugins-analyzer vcs https://github.com/pheeque/craft-plugins-analyzer

composer global require pheeque/craft-plugins-analyzer:dev-master
```

To upgrade to the most recent version, run:
```shell
composer global update pheeque/craft-plugins-analyzer:dev-master
```

## Usage
After installation, you can run this program with the following options:

```
Usage:
  craft-plugins-analyzer list-plugins [options]

Options:
      --limit=LIMIT      Number of items to display [default: 50]
      --output=OUTPUT    Path to the JSON file
      --orderBy=ORDERBY  Field to sort results by, allowed values: downloads, favers, dependents, updated  [default: "downloads"]
      --order=ORDER      ASC or DESC [default: "DESC"]
  -h, --help             Display help for the list-plugins command
```

## Caching
The first run of the program takes a while as it attempts to populate the cache with package data from packagist. Subsequent runs should be much faster. 

## Testing
Tests can be run from the root of this repo as follows:
```shell
./vendor/bin/pest
```
