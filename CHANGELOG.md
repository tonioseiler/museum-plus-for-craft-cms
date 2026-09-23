# MuseumPlus for CraftCMS Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.0.0-beta.43 - 2026-09-23
### Fixed
* Item element queries no longer group by the LONGTEXT `data` column. Grouping by it forced MySQL to build huge on-disk temporary tables, which filled the MySQL temp directory (`Error writing file … (Errcode: 28 "No space left on device")`, e.g. on the plugin's general settings page) and made every item query (listings, search, sitemaps, imports) much slower. The `GROUP BY` itself, including `elements_sites.id`, is kept, so items are still returned once per site. Measured: settings page count 13 s → 1.8 s, `collectionId` lookup during imports 2.2 s → 0.01 s.

## 1.0.4 - 2025-03-03
### Added
* import / update / delete of items jobs in craft queue

## 1.0.3 - 2024-06-13
### Added
* Version number


## 1.0.2 - 2024-06-13
### Added
* Version number



## 1.0.0 - 2024-06-11
- Initial release
