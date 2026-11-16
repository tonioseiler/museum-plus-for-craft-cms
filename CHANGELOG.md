# MuseumPlus for CraftCMS Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.0.0-beta.45 - 2026-11-16
### Added
* Settings **Vocabulary Languages** (e.g. `de, en`) and **Vocabulary Fallback Language** (e.g. `de`): vocabulary terms are imported in all these languages. Each site gets the term in its own language as the vocabulary entry's title, otherwise the fallback term. Vocabulary entries are only stored for sites with an item or people URL format.
* Group rows (e.g. `ObjMaterialTechniqueGrp`) keep the M+ node ID of every vocabulary field (`<Field>Id`, and `<Field>Items` if there are several). `MuseumPlusItem::getRepeatableGroupRows($groupName)` returns the rows sorted by `SortLnu` with their vocabulary entry, e.g. to link materials and techniques.
* `update-items`: `--limit=<n>` (queue at most n changed objects per run), a summary at the end (counts, already queued, pending, runs left, memory, CPU time), `--details` (one line per queued object with both dates) and `--fullDetails` (changed fields per object, logged by the job).
* `update-items --forceAll --updatedBefore=<date>`: forces only items not updated since that date, so a forced re-import in batches (`--limit`) takes the next items each run.
* Migration: indexes on `museumplus_items.collectionId`, `museumplus_items.parentId` and `museumplus_vocabulary.collectionId` (schema version 5.0.1). Run `craft migrate/all` after updating.

### Changed
* **Language Header** is validated and required: one language code only (e.g. `de`), not a list like `de, en`. Check the setting after updating and save it again.
* Item syncs are about 7× faster (measured with ~31,000 objects, images skipped: ~17.5 s → ~2.3 s per object). Unchanged vocabulary entries are no longer saved again, vocabulary parent lookups are cached for 24 h instead of 1 s, the parent/child reset only runs when needed, objects get their `sort` on the first save, and the local item dates for `update-items` are loaded in one query.
* `update-items` skips objects that are already waiting in the queue, and handles an object in several object groups once.

### Fixed
* `update-items` / `delete-removed-items` no longer delete items when M+ returns an empty object group, and refuse to delete more than 5 % (at least 50) of the items without `--forceDelete`. Empty object lists are no longer cached, and the cache key includes the M+ host.
* The item `sort` value was never stored (overwritten with null when the site copies were saved).
* Parent objects were read from `ObjObjectPartRef` instead of `ObjObjectMainRef`.
* Two objects that are each other's main object in M+ made the job recurse until it ran out of memory. The job now stops at the loop and logs a warning ("Parent/child loop in M+ data").
* Ownerships without persons (no `OwsPersonRef`) no longer make the job fail.
* M+ references with one, several or no `vocabularyReferenceItem` are all parsed.
* PHP 8.5: explicit nullable parameters.

## 5.0.0-beta.44 - 2026-09-23
### Changed
* TIFF files (`.tif`, `.tiff`) are no longer imported as object attachments (`Items/`) or person multimedia (`People/`). They can be huge (e.g. 7087 × 6980 px, 142 MB uncompressed) and their image transforms could not be generated within the hosting's memory limits. The downloaded temp file is deleted. All other file types (JPG, PNG, PDF, …) are imported as before; object multimedia still use the `attachmentFileTypes` setting.

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
