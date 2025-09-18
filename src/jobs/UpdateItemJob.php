<?php

namespace furbo\museumplusforcraftcms\jobs;

use Craft;
use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\models\VolumeFolder;
use craft\queue\BaseJob;
use craft\queue\jobs\UpdateSearchIndex;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\events\ItemUpdatedFromMuseumPlusEvent;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use Yii;

/**
 * Job to update a MuseumPlusItem.
 */
class UpdateItemJob extends BaseJob
{
    public int $collectionId;
    private $settings;
    private $museumPlus;
    public $assets;
    public $ignoreAttachments;

    public $ignoreMultimedia;
    public $ignoreLiterature;

    private $queue = null;
    private $logger = null;

    public function execute($queue): void
    {
        $this->logger = MuseumPlusForCraftCms::getLogger();
        $this->queue = $queue;

        $this->settings = MuseumPlusForCraftCms::$plugin->getSettings();
        $this->museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        $this->assets = Craft::$app->getAssets();
        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $this->collectionId])
            ->one();
        $isNewItem = !$item;
        $message = $isNewItem
            ? "Creating new MuseumPlusItem (Collection ID: {$this->collectionId})."
            : "Updating MuseumPlusItem (Collection ID: {$this->collectionId}).";
        $this->logger->info($message);

        $this->setProgress($this->queue, 0.01, "Update initialized");

        try {
            $this->updateItemFromMuseumPlus($this->collectionId);
            $this->triggerUpdateEvent($this->collectionId, $isNewItem);
            $this->updateItemToItemRelationShips($this->collectionId);
            $this->updateItemParentChildRelationShips($this->collectionId);
            $this->updateItemSort($this->collectionId);
            $message = "Successfully processed MuseumPlusItem (ID: {$this->collectionId}).";
            $this->logger->info($message);
            $this->setProgress($this->queue, 1, "Update done");
        } catch (\Throwable $e) {
            $message = "Error processing MuseumPlusItem (ID: {$this->collectionId}): " . $e->getMessage();
            $this->logger->error($message);
            throw new \Exception("could not update item: " . $e->getMessage());
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', "Updating MuseumPlusItem collectionId: {$this->collectionId}");
    }

    private function updateItemFromMuseumPlus($collectionId)
    {
        $message = "Running updateItemFromMuseumPlus('{$this->collectionId}').";
        $this->logger->debug($message);

        try {

            $this->setProgress($this->queue, 0.1, "Retreiving item details");
            $o = $this->museumPlus->getObjectDetail($collectionId);
            $this->setProgress($this->queue, 0.15, "Updating item details");
            $item = $this->createOrUpdateItem($o);

            //add attachment
            //echo '- Main image'.PHP_EOL;
            if (!$this->ignoreAttachments) {
                $this->setProgress($this->queue, 0.2, "Updating item attachments");
                $assetId = $this->createAttachmentFromObjectId($item->collectionId);
                if ($assetId) {
                    //echo "Attachment for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                    $item->assetId = $assetId;
                    Craft::$app->elements->saveElement($item);
                    $this->logger->debug("Attachment for item " . $item->id . " AssetID: " . $assetId);
                } else {
                    $this->logger->debug("Attachment for item " . $item->id . " AssetID: NULL");
                }
            }

            $moduleRefs = $item->getDataAttribute('moduleReferences');
            //add multimedia
            if (!$this->ignoreMultimedia && isset($moduleRefs['ObjMultimediaRef'])) {
                $this->setProgress($this->queue, 0.3, "Updating item multimedia objects");
                $assetIds = [];
                $refs = $moduleRefs['ObjMultimediaRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                $count = 0;
                foreach ($refs as $mm) {
                    $assetId = $this->createMultimediaFromId($mm['id'], $collectionId);
                    if ($assetId) {
                        $assetIds[] = $assetId;
                        $this->logger->debug("Asset created: AssetID: " . $assetId);

                    }
                    $count++;
                    $this->setProgress($this->queue, 0.3 + ($count / (10 * count($refs))), "Updating item multimedia objects");
                }
                if (count($assetIds)) {
                    $this->logger->debug("At least one asset");

                    $item->syncMultimediaRelations($assetIds);
                    $this->logger->debug("syncMultimediaRelations() executed");

                    //echo "Multimedia assets for Item Id: " . $item->id . " Asset IDs: " . implode(",", $assetIds) . PHP_EOL;
                }
            }


            //add literature relations
            $literatureIds = [];
            if (isset($moduleRefs['ObjLiteratureRef'])) {
                $this->setProgress($this->queue, 0.4, "Updating item literature objects");
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                $count = 0;
                foreach ($refs as $l) {
                    try {
                        $data = $this->museumPlus->getLiterature($l['id']);
                        if ($data) {
                            $literature = $this->createOrUpdateLiterature($data);
                            if ($literature) {
                                $literatureIds[] = $literature->id;
                            }
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        echo "WARNING: " . $e->getMessage() . PHP_EOL;
                    }
                    $count++;
                    $this->setProgress($this->queue, 0.4 + ($count / (10 * count($refs))), "Updating item literature objects");
                }
            }

            //sync
            if (count($literatureIds)) {
                $item->syncLiteratureRelations($literatureIds);
                $this->logger->debug("Literatures added");
            }

            //add literature assets
            if (!$this->ignoreLiterature && isset($moduleRefs['ObjLiteratureRef'])) {
                $this->setProgress($this->queue, 0.5, "Updating item literature assets");
                $assetIds = [];
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                $count = 0;
                foreach ($refs as $l) {
                    $assetId = $this->createLiteratureFromId($l['id']);
                    $literature = $this->museumPlus->getLiterature($l['id']);
                    if ($assetId && $literature) {
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                        $literature->assetId = $assetId;
                        $literature->save();
                        $this->logger->debug("Literature for id " . $literature->id . " for item " . $item->id . " AssetID: " . $assetId);

                    } else {
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                    }
                    $count++;
                    $this->setProgress($this->queue, 0.5 + ($count / (10 * count($refs))), "Updating item literature assets");
                }
            }

            //add people refs
            $this->setProgress($this->queue, 0.6, "Updating item people");
            $peopleTypes = ['ObjAdministrationRef', 'ObjPerOwnerRef', 'ObjPerAssociationRef'];
            foreach ($peopleTypes as $peopleType) {
                if (isset($moduleRefs[$peopleType])) {
                    $refs = $moduleRefs[$peopleType]['items'];
                    $this->sortArray($refs, 'SortLnu');
                    $peopleIds = [];
                    foreach ($refs as $p) {

                        try {
                            $data = $this->museumPlus->getPerson($p['id']);
                            $person = $this->createOrUpdatePerson($data);
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            echo "WARNING: " . $e->getMessage() . PHP_EOL;
                        }
                        $peopleIds[] = $person->id;
                    }
                    //sync
                    if (count($peopleIds)) {
                        $item->syncPeopleRelations($peopleIds, $peopleType);
                        $this->logger->debug("People added to item");
                    }
                }
            }

            //add owenrship refs
            $ownershipIds = [];
            $this->setProgress($this->queue, 0.7, "Updating item ownerships");
            if (isset($moduleRefs['ObjOwnershipRef'])) {
                $refs = $moduleRefs['ObjOwnershipRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $o) {
                    try {
                        $data = $this->museumPlus->getOwnership($o['id']);
                        $ownership = $this->createOrUpdateOwnership($data);
                        if ($ownership) {
                            $ownershipIds[] = $ownership->id;
                            $ownershipData = json_decode($ownership->data);
                            $ownershipPersonRefs = $ownershipData->moduleReferences->OwsPersonRef->items;
                            $peopleIds = [];
                            foreach ($ownershipPersonRefs as $p) {
                                try {
                                    $data = $this->museumPlus->getPerson($p->id);
                                    $person = $this->createOrUpdatePerson($data);
                                    $peopleIds[] = $person->id;

                                } catch (\GuzzleHttp\Exception\ClientException $e) {
                                    echo "WARNING: " . $e->getMessage() . PHP_EOL;
                                }
                            }

                            //sync
                            $ownership->syncPeopleRelations($peopleIds);

                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        echo "WARNING: " . $e->getMessage() . PHP_EOL;
                    }
                }
            }
            //sync
            if (count($ownershipIds)) {
                $item->syncOwnershipRelations($ownershipIds);
                $this->logger->debug("Ownerships added to item");
            }

            $this->updateVocabularyRefs($item);

        } catch (\Exception $e) {
            //     echo $item->id . " could not be fully updated." . PHP_EOL;
            $this->logger->error($e->getMessage());
            throw new \Exception("could not update item from museum plus: " . $e->getMessage());
        } finally {
            gc_collect_cycles(); //force garbage collection
        }

    }

    private function triggerUpdateEvent($collectionItemId, $isNewItem = false)
    {
        $this->logger->debug('running triggerUpdateEvent()');
        $this->setProgress($this->queue, 0.85, "Trigger update event");

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionItemId])
            ->one();

        $event = new ItemUpdatedFromMuseumPlusEvent([
            'item' => $item,
            'isNewItem' => $isNewItem
        ]);

        MuseumPlusForCraftCms::$plugin->trigger(MuseumPlusForCraftCms::EVENT_ITEM_UPDATED_FROM_MUSEUM_PLUS, $event);
    }

    private function updateItemToItemRelationShips($collectionId)
    {
        $this->logger->debug('running updateItemToItemRelationShips()');
        $this->setProgress($this->queue, 0.9, "Update item to item relationships");

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        $moduleRefs = $item->getDataAttribute('moduleReferences');

        $types = ['ObjObjectARef', 'ObjObjectBRef',];

        foreach ($types as $type) {
            if (isset($moduleRefs[$type])) {
                $ids = [];
                foreach ($moduleRefs[$type]['items'] as $i) {
                    $tmp = MuseumPlusItem::find()
                        ->where(['collectionId' => $i['id']])
                        ->one();
                    if ($tmp) {
                        $ids[] = $tmp->id;
                    }
                }
                //sync
                if (count($ids)) {
                    $item->syncItemRelations($ids);
                    $this->logger->debug('syncItemRelations() executed');
                }
            }
        }
    }


    private function updateItemParentChildRelationShips($collectionId)
    {
        $this->logger->debug('running updateItemParentChildRelationShips()');
        $this->setProgress($this->queue, 0.92, "Update item parent child relationships");

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        //reset
        $db = Yii::$app->db;
        $rowsAffected = $db->createCommand()
            ->update('museumplus_items', ['parentId' => '0'], ['parentId' => $item->collectionId])
            ->execute();

        $moduleRefs = $item->getDataAttribute('moduleReferences');

        //check if there are any children
        if (isset($moduleRefs['ObjObjectPartRef']) && count($moduleRefs['ObjObjectPartRef']['items']) > 0) {
            $parts = $moduleRefs['ObjObjectPartRef']['items'];
            foreach ($parts as $part) {
                $child = MuseumPlusItemRecord::find()
                    ->where(['collectionId' => $part['id']])
                    ->one();
                if ($child) {
                    $child->parentId = $item->collectionId;
                    $savingChild = $child->save();
                }
            }
        }

        //check if it has a parent, if yes, update the parent
        if (isset($moduleRefs['ObjObjectMainRef']) && count($moduleRefs['ObjObjectMainRef']['items']) > 0) {
            $parents = $moduleRefs['ObjObjectPartRef']['items'];
            foreach ($parents as $parent) {
                $parentRecord = MuseumPlusItemRecord::find()
                    ->where(['collectionId' => $parent['id']])
                    ->one();
                if ($parentRecord && $parentRecord->collectionId != $item->collectionId) {
                    $this->logger->debug('Parent found: ' . $parentRecord->collectionId);
                    $this->updateItemParentChildRelationShips($parentRecord->collectionId);
                }
            }
        }
    }


    private function updateItemSort($collectionId)
    {
        $this->logger->debug('running updateItemSort()');
        $this->setProgress($this->queue, 0.95, "Update item sort");

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        try {
            $sort = $item->getDataAttribute('ObjObjectNumberSortedVrt');
            if ($sort) {
                $item->sort = $sort;
                if (Craft::$app->elements->saveElement($item)) {
                    $this->logger->debug($item->id . " - " . $sort);
                } else {
                    $this->logger->warning('Could not save item');
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            throw new \Exception("could not update item sort string");
        }
    }

    private function createOrUpdateItem($object)
    {
        $collectionId = $object->id;
        $this->logger->debug('running createOrUpdateItem()');

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($item)) {
            //create new
            $this->logger->info('running createOrUpdateItem(): create new item, collectionId: '.$collectionId.' - element id: not yet available');
            $item = new MuseumPlusItem();
            $item->collectionId = $collectionId;
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;
        } else {
            $this->logger->debug('running createOrUpdateItem(): update existing item');

            //update
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;

        }

        $inventoryNumber = $object->ObjObjectNumberVrt ?? '';//$item->getDataAttribute('ObjObjectNumberVrt');
        if (empty($inventoryNumber))
            $inventoryNumber = $object->ObjObjectNumberTxt ?? '';
        if ($inventoryNumber) {
            $item->inventoryNumber = $inventoryNumber;
        }

        $success = Craft::$app->elements->saveElement($item, false, true,true);

        if (!$success) {
            $this->logger->error('Could not save item: ' . print_r($item->getErrors(), true));
            return false;
        } else {
            $this->logger->debug('Item successfully saved ');
            $this->logger->info('new or already existing element id: '.$item->id.' -- collectionId: '.$item->collectionId);
        }

        //insert object relations if they do not exist
        $itemRecord = $item->getRecord();
        $itemRecord->unlinkAll('objectGroups', true);
        $moduleReferences = $item->getDataAttribute('moduleReferences');
        if (isset($moduleReferences['ObjObjectGroupsRef'])) {
            foreach ($moduleReferences['ObjObjectGroupsRef']['items'] as $og) {
                $objectGroup = ObjectGroupRecord::find()->where(['collectionId' => $og['id']])->one();
                if ($objectGroup)
                    $itemRecord->link('objectGroups', $objectGroup);
            }
        }
        //echo 'i';
        return $item;
    }

    private function createAttachmentFromObjectId($id)
    {
        $this->logger->debug('running createAttachmentFromObjectId()');

        $attachment = $this->museumPlus->getAttachmentByObjectId($id);

        $settings = MuseumPlusForCraftCms::$plugin->getSettings();
        // $folderId = $this->settings['attachmentVolumeId'];
        $folderId = $settings['attachmentVolumeId'];
        $this->logger->debug('attachmentVolumeId: ' . $folderId);

        $folder = $this->assets->findFolder(['id' => $folderId]);
        $parentFolder = $this->createFolder("Items");
        $itemFolder = $this->createFolder($id, $parentFolder->id, $parentFolder->path);
        if ($attachment) {
            $asset = $this->createAsset($id, $attachment, $itemFolder);
            if ($asset) {
                return $asset->id;
            }
        }
        $this->logger->debug('finished createAttachmentFromObjectId()');

        return false;
    }

    private function createFolder($folderName, $parentFolderId = null, $parentFolderPath = null)
    {
        $volumeId = $this->settings['attachmentVolumeId'];
        $volume = Craft::$app->volumes->getVolumeById($volumeId);
        if (!$volume) {
            $this->logger->error("Volume with ID {$volumeId} not found.");
            return false;
        }
        // Find the root folder for this volume
        $rootFolder = Craft::$app->assets->getRootFolderByVolumeId($volumeId);
        if (!$rootFolder) {
            $this->logger->error("Root folder for volume ID {$volumeId} not found.");
            return false;
        }

        if ($parentFolderId !== null) {
            // Check if the folder already exists
            $existingFolder = Craft::$app->assets->findFolder([
                'name' => $folderName,
                'parentId' => $parentFolderId
            ]);
            if ($existingFolder) {
                return $existingFolder;
            } else {
                $folder = new VolumeFolder();
                $folder->parentId = $parentFolderId;
                $folder->name = $folderName;
                $folder->volumeId = $volumeId;
                $folder->path = $parentFolderPath . $folderName . '/';
                $this->assets->createFolder($folder);
                return $folder;
            }
        } else {
// Check if the folder already exists
            $existingFolder = Craft::$app->assets->findFolder([
                'name' => $folderName,
                'parentId' => $rootFolder->id
            ]);
            if ($existingFolder) {
                return $existingFolder;
            } else {
                $folder = new VolumeFolder();
                $folder->parentId = $rootFolder->id;
                $folder->name = $folderName;
                $folder->volumeId = $volumeId;
                $folder->path = $folderName . '/';
                $this->assets->createFolder($folder);
                return $folder;
            }
        }

    }

    private function createAsset($id, $attachment, $parentFolder, $title=false)
    {
        $basename = pathinfo($attachment, PATHINFO_FILENAME);
        $basename = FileHelper::sanitizeFilename($basename, [true, '_']);
        $extension = pathinfo($attachment, PATHINFO_EXTENSION);
        $filename = $basename . '_' . $id . '.' . $extension;
        if(!$title) {
            $title = Assets::filename2Title($basename . '_' . $id);
        }
        try {
            $asset = Asset::find()->title($title)->folderId($parentFolder->id)->one();
            if (is_null($asset)) {
                $asset = new Asset();
            }
            $asset->title = $title;
            $asset->tempFilePath = $attachment;
            $asset->filename = $filename;
            $asset->newFolderId = $parentFolder->id;
            $asset->setVolumeId($parentFolder->volumeId);
            $asset->setScenario(Asset::SCENARIO_CREATE);
            $asset->avoidFilenameConflicts = true;

            $result = Craft::$app->getElements()->saveElement($asset);
            if ($result) {
                //echo '- File '.$id.PHP_EOL;
                return $asset;
            } else {
                return false;
            }
        } catch (\Throwable $e) {
            throw new \Exception("could not create asset: " . $e->getMessage());
        }
        return false;
    }

    private function sortArray(&$array, $key)
    {
        usort($array, function ($a, $b) use ($key) {

            if (!isset($a[$key]) || !isset($b[$key])) {
                return 0;
            } else if (!isset($a[$key]) && isset($b[$key])) {
                return -1;
            } else if (isset($a[$key]) && !isset($b[$key])) {
                return 1;
            } else if ($a[$key] == $b[$key]) {
                return 0;
            }
            return ($a[$key] < $b[$key]) ? -1 : 1;
        });
    }

    private function createMultimediaFromId($id, $itemId = null)
    {
        $attachment = $this->museumPlus->getMultimediaById($id);
        $folderId = $this->settings['attachmentVolumeId'];
        $folder = $this->assets->findFolder(['id' => $folderId]);
        $parentFolder = $this->createFolder("Multimedia");
        $itemFolder = $this->createFolder($itemId, $parentFolder->id, $parentFolder->path);
        if ($attachment) {
            $fileTypes = $this->settings['attachmentFileTypes'];
            if (!empty($fileTypes)) {
                // only allow file types defined in plugin settings.
                $pattern = '/\.(' . str_replace(', ', '|', $fileTypes) . ')$/i';
                if (preg_match($pattern, $attachment)) {
                    $asset = $this->createAsset($id, $attachment, $itemFolder);
                    if ($asset) {
                        return $asset->id;
                    }
                }
            } else {
                // allow any file type
                $asset = $this->createAsset($id, $attachment, $itemFolder);
                if ($asset) {
                    return $asset->id;
                }
            }
        }
        return false;
    }

    private function createLiteratureFromId($id)
    {
        $folderId = $this->settings['attachmentVolumeId'];
        $folder = $this->assets->findFolder(['id' => $folderId]);
        //$parentFolder = $this->createFolder("Literature", $folderId);
        $parentFolder = $this->createFolder("Literature");
        $attachment = $this->museumPlus->getLiteratureById($id);

        if ($attachment) {
            $asset = $this->createAsset($id, $attachment, $parentFolder);
            if ($asset) {
                return $asset->id;
            }
        }
        return false;
    }

    private function createOrUpdateOwnership($data)
    {
        $collectionId = $data->id;
        $ownerhsip = OwnershipRecord::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($ownerhsip)) {
            //create new
            $ownerhsip = new OwnershipRecord();
            $ownerhsip->id = 0;
            $ownerhsip->collectionId = $collectionId;
            $ownerhsip->title = $data->OwsOwnershipVrt;
        } else {
            //update
            $ownerhsip->title = $data->OwsOwnershipVrt;
        }
        $ownerhsip->data = json_encode($data);
        $success = $ownerhsip->save();
        return $ownerhsip;
    }

    private function createOrUpdateLiterature($data)
    {
        $collectionId = $data->id;
        $literature = LiteratureRecord::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($literature)) {
            //create new
            $literature = new LiteratureRecord();
            $literature->id = 0;
            $literature->collectionId = $collectionId;
            $literature->title = $data->LitLiteratureVrt;
        } else {
            //update
            $literature->title = $data->LitLiteratureVrt;
        }
        $literature->data = json_encode($data);
        $success = $literature->save();
        return $literature;
    }

    private function createOrUpdateVocabularyEntry($type, $data)
    {

        $collectionId = $data->id;
        $vocabularyEntry = MuseumPlusVocabulary::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($vocabularyEntry)) {
            //create new
            $vocabularyEntry = new MuseumPlusVocabulary();
            $vocabularyEntry->collectionId = $data->id;
            $vocabularyEntry->title = $data->content;
        } else {
            //update
            $vocabularyEntry->title = $data->content;
        }
        $vocabularyEntry->type = $type;
        $vocabularyEntry->parentId = $data->parentId;
        $vocabularyEntry->language = $data->isoLanguageCode;
        $vocabularyEntry->data = json_encode($data);
        $success = Craft::$app->elements->saveElement($vocabularyEntry, false);
        return $vocabularyEntry;
    }

    private function createOrUpdatePerson($data)
    {
        $collectionId = $data->id;
        $person = MuseumPlusPerson::find()
            ->where(['collectionId' => $collectionId])
            ->one();
        if (empty($person)) {
            $person = new MuseumPlusPerson();
            $person->collectionId = $collectionId;
            $success = Craft::$app->elements->saveElement($person, false);
        }
        //update
        //TODO: check last modified date if we need to update the person
        $person->data = json_encode($data);
        if (!empty($data->PerNameTxt))
            $person->title = $data->PerNameTxt;
        else if (!empty($data->PerNameTxt))
            $person->title = $data->PerPersonTxt;
        else if (!empty($data->PerNameVrt))
            $person->title = $data->PerNameVrt;
        else
            $person->title = 'Unknown';

        $moduleRefs = $person->getDataAttribute('moduleReferences');

        // TODO Paolo check PerLiteratureRef, get the id, check if literature entry exists, if not create it

        if (isset($moduleRefs['PerLiteratureRef'])) {
            $refs = $moduleRefs['PerLiteratureRef']['items'];
            //$this->sortArray($refs, 'SortLnu');
            $count = 0;
            foreach ($refs as $l) {
                try {
                    $data = $this->museumPlus->getLiterature($l['id']);
                    if ($data) {
                        $literature = $this->createOrUpdateLiterature($data);
                        echo "------- createOrUpdateLiterature: " .$l['id'] . PHP_EOL;
                        /*
                        if ($literature) {
                            $literatureIds[] = $literature->id;
                        }
                        */
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo "WARNING: " . $e->getMessage() . PHP_EOL;
                }
            }
        }


        if (!$this->ignoreMultimedia && isset($moduleRefs['PerMultimediaRef'])) {
            $assetIds = [];
            $refs = $moduleRefs['PerMultimediaRef']['items'];
            // $this->sortArray($refs, 'SortLnu');
            // TODO Paolo: before deleting the old assets, we should check if they are still in the new list
            // maybe first get an array of the current assets ids and then check with the ones ACTIVE from museum plus
            // also check if we already have the asset in the database
            $this->deletePersonMultimedia($person->id);
            foreach ($refs as $mm) {
                $assetId = $this->createPersonMultimediaFromId($mm['id'], $collectionId);
                if ($assetId) {
                    $assetIds[] = $assetId;
                }
            }
            if (count($assetIds)) {
                $person->syncPersonMultimediaRelations($assetIds);
            }
        }
        $success = Craft::$app->elements->saveElement($person, false);
        return $person;
    }


    private function createPersonMultimediaFromId($id, $personId = null)
    {

        //$attachment = $this->museumPlus->getMultimediaById($id);
        $attachmentWithTitle = $this->museumPlus->getCompleteMultimediaById($id);
        if($attachmentWithTitle) {
            $attachment = $attachmentWithTitle['file'];
            $title = $attachmentWithTitle['title'];
            $folderId = $this->settings['attachmentVolumeId'];
            $folder = $this->assets->findFolder(['id' => $folderId]);
            $parentFolder = $this->createFolder("People");
            $itemFolder = $this->createFolder($personId, $parentFolder->id, $parentFolder->path);
            // TODO: should we filter by file type?
            $asset = $this->createAsset($id, $attachment, $itemFolder, $title);
            if ($asset) {
                return $asset->id;
            }
            /*
            $fileTypes = $this->settings['attachmentFileTypes'];
            if (!empty($fileTypes)) {
                // only allow file types defined in plugin settings.
                $pattern = '/\.(' . str_replace(', ', '|', $fileTypes) . ')$/i';
                if (preg_match($pattern, $attachment)) {
                    $asset = $this->createAsset($id, $attachment, $itemFolder);
                    if ($asset) {
                        return $asset->id;
                    }
                }
            } else {
                // allow any file type
                $asset = $this->createAsset($id, $attachment, $itemFolder);
                if ($asset) {
                    return $asset->id;
                }
            }
            */
        }
        return false;
    }

    private function deletePersonMultimedia($peopleId){
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_people_assets}}', ['peopleId' => $peopleId])->execute();
    }


    private function updateVocabularyRefs(MuseumPlusItem $item)
    {
        //add vocabulary refs
        $vocabularyRefs = $item->getDataAttribute('vocabularyReferences');
        $syncData = [];
        foreach ($vocabularyRefs as $vocabularyRef) {
            $ids = [];
            $type = $vocabularyRef['instanceName'];
            //echo '<br><br>$vocabularyRef[items] ['.$type.']<br><textarea style="width:600px;height:100px;">'.print_r($vocabularyRef['items'],true).'</textarea><br>';
            foreach ($vocabularyRef['items'] as $vc) {
                try {
                    // Using the node id from above we get the vocabulary data for the entry: content, id, parentId (directly from the m+ server)
                    // this node id is not the collection id, but the id of the vocabulary node connection
                    $data = $this->museumPlus->getVocabularyNode($type, $vc['id']);
                    foreach ($data as $d) {
                        $vocabularyEntry = $this->createOrUpdateVocabularyEntry($type, $d);
                        if ($vocabularyEntry) {
                            if (!empty($vocabularyEntry->id)) {
                                $ids[] = $vocabularyEntry->id;
                            }
                            //probaly can be remooved to get all the tree
                            $currentParentId = $vocabularyEntry->parentId;
                            $currentParentNodeId = $vc['id'];
                            $counter = 0;
                            while ($currentParentId > 0) {
                                $counter++;
                                //echo '------- counter: ' . $counter. '<br>';
                                if ($counter > 15) {
                                    //die('infinite loop? '.$counter);
                                    break;
                                }
                                //echo 'we have a parent: ' . $currentParentId . ' ['.$counter.']<br>';
                                $parentNodeId = $this->museumPlus->getVocabularyParentNodeId($type, $currentParentNodeId);
                                //echo 'parentNodeId: ' . $parentNodeId . '<br>';
                                $dataParent = $this->museumPlus->getVocabularyNode($type, $parentNodeId);
                                $currentParentId = 0;
                                // its always one, but an array
                                foreach ($dataParent as $dp) {
                                    $vocabularyEntryParent = $this->createOrUpdateVocabularyEntry($type, $dp);
                                    $currentParentId = $vocabularyEntryParent->parentId;
                                    $currentParentNodeId = $parentNodeId;
                                }
                            }
                        }
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo "WARNING: " . $e->getMessage() . PHP_EOL;
                    throw new \Exception("could not update vocabulary refs: " . $e->getMessage());
                }
            }
            if (isset($syncData[$type])) {
                foreach ($ids as $id) {
                    $syncData[$type][] = $id;
                }
            } else {
                $syncData[$type] = $ids;
            }
        }
        if (count($syncData)) {
            $item->syncVocabularyRelations($syncData);
        }
    }
}