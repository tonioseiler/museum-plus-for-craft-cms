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
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\events\ItemUpdatedFromMuseumPlusEvent;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;

/**
 * Job to update a MuseumPlusItem.
 */
class DeleteRemovedItemsJob extends BaseJob
{
    private $settings;
    private $museumPlus;
    public $assets;
    public $ignoreAttachments;

    public $ignoreMultimedia;
    public $ignoreLiterature;

    private $showDetailedLog = true;

    private $queue = null;
    private $logger = null;


    public function execute($queue): void
    {
        $this->settings = MuseumPlusForCraftCms::$plugin->getSettings();
        $this->museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        $this->assets = Craft::$app->getAssets();
        $this->logger = MuseumPlusForCraftCms::getLogger();
        $this->queue = $queue;
        $this->logger->info('---- Deleting removed items START ---------');
        $this->logger->info('---- Deleting removed items END ---------');
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', "Updating MuseumPlusItem collectionId: {$this->collectionId}");
    }

    private function updateItemFromMuseumPlus($collectionId)
    {
        if ($this->showDetailedLog) {
            $message = "Running updateItemFromMuseumPlus('{$this->collectionId}').";
            $this->logger->info($message);
        }
        try {

            /*
            $museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
            $o = $museumPlus->getObjectDetail($collectionId);
            $item = $this->createOrUpdateItem($o);
            */

            $this->setProgress($this->queue, 0.1, "Retreiving item details");
            $o = $this->museumPlus->getObjectDetail($collectionId);
            $this->setProgress($this->queue, 0.2, "Updating item details");
            $item = $this->createOrUpdateItem($o);

            //add attachment
            //echo '- Main image'.PHP_EOL;
            if (!$this->ignoreAttachments) {
                $this->setProgress($this->queue, 0.3, "Updating item attachments");
                $assetId = $this->createAttachmentFromObjectId($item->collectionId);
                if ($assetId) {
                    //echo "Attachment for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                    $item->assetId = $assetId;
                    Craft::$app->elements->saveElement($item);
                    if ($this->showDetailedLog) {
                        $this->logger->info("Attachment for item " . $item->id . " AssetID: " . $assetId);
                    }
                } else {
                    //echo "Attachment for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                    if ($this->showDetailedLog) {
                        $this->logger->info("Attachment for item " . $item->id . " AssetID: NULL");
                    }
                }
            }

            $moduleRefs = $item->getDataAttribute('moduleReferences');
            //add multimedia
            //echo '- Multimedia files'.PHP_EOL;
            if (!$this->ignoreMultimedia && isset($moduleRefs['ObjMultimediaRef'])) {
                $this->setProgress($this->queue, 0.4, "Updating item multimedia objects");
                $assetIds = [];
                $refs = $moduleRefs['ObjMultimediaRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $mm) {
                    $assetId = $this->createMultimediaFromId($mm['id'], $collectionId);
                    if ($assetId) {
                        $assetIds[] = $assetId;
                        if ($this->showDetailedLog) {
                            $this->logger->info("Asset created: AssetID: " . $assetId);
                        }

                    }
                }
                if (count($assetIds)) {
                    if ($this->showDetailedLog) {
                        $this->logger->info("At least one asset");
                    }

                    $item->syncMultimediaRelations($assetIds);
                    if ($this->showDetailedLog) {
                        $this->logger->info("syncMultimediaRelations() executed");
                    }

                    //echo "Multimedia assets for Item Id: " . $item->id . " Asset IDs: " . implode(",", $assetIds) . PHP_EOL;
                }
            }


            //add literature relations
            $literatureIds = [];
            if (isset($moduleRefs['ObjLiteratureRef'])) {
                $this->setProgress($this->queue, 0.6, "Updating item literature objects");
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
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
                }
            }

            //sync
            if (count($literatureIds)) {
                $item->syncLiteratureRelations($literatureIds);
                if ($this->showDetailedLog) {
                    $this->logger->info("Literatures added");
                }
                //echo 'l';
            }

            //add literature assets
            if (!$this->ignoreLiterature && isset($moduleRefs['ObjLiteratureRef'])) {
                $this->setProgress($this->queue, 0.7, "Updating item literature assets");
                $assetIds = [];
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $l) {
                    $assetId = $this->createLiteratureFromId($l['id']);
                    $literature = $this->museumPlus->getLiterature($l['id']);
                    if ($assetId && $literature) {
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                        $literature->assetId = $assetId;
                        $literature->save();
                        if ($this->showDetailedLog) {
                            $this->logger->info("Literature for id " . $literature->id . " for item " . $item->id . " AssetID: " . $assetId);
                        }

                    } else {
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                    }
                }
            }


            //add people refs
            $this->setProgress($this->queue, 0.8, "Updating item people");
            $peopleTypes = ['ObjAdministrationRef', 'ObjPerOwnerRef', 'ObjPerAssociationRef'];
            foreach ($peopleTypes as $peopleType) {
                if (isset($moduleRefs[$peopleType])) {
                    $peopleIds = [];
                    $refs = $moduleRefs[$peopleType]['items'];
                    $this->sortArray($refs, 'SortLnu');
                    foreach ($refs as $p) {
                        try {
                            $data = $this->museumPlus->getPerson($p['id']);
                            $person = $this->createOrUpdatePerson($data);
                            if ($person) {
                                $peopleIds[] = $person->id;
                            }
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            echo "WARNING: " . $e->getMessage() . PHP_EOL;
                        }
                    }
                    //sync
                    if (count($peopleIds)) {
                        $item->syncPeopleRelations($peopleIds, $peopleType);
                        if ($this->showDetailedLog) {
                            $this->logger->info("People added");
                        }

                        //echo 'p';
                    }
                }
            }


            //add owenrship refs
            $ownershipIds = [];
            if (isset($moduleRefs['ObjOwnershipRef'])) {
                $refs = $moduleRefs['ObjOwnershipRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $o) {
                    try {
                        $data = $this->museumPlus->getOwnership($o['id']);
                        $ownership = $this->createOrUpdateOwnership($data);
                        if ($ownership) {
                            $ownershipIds[] = $ownership->id;
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        echo "WARNING: " . $e->getMessage() . PHP_EOL;
                    }
                }
            }
            //sync
            if (count($ownershipIds)) {
                $item->syncOwnershipRelations($ownershipIds);
                if ($this->showDetailedLog) {
                    $this->logger->info("Ownerships added");
                }
                //echo 'o';
            }

            $this->updateVocabularyRefs($item);

        } catch (\Exception $e) {
            //     echo $item->id . " could not be fully updated." . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        } finally {
            gc_collect_cycles(); //force garbage collection
        }

    }

    private function triggerUpdateEvent($collectionItemId, $isNewItem = false)
    {
        if ($this->showDetailedLog) {
            $this->logger->info('running triggerUpdateEvent()');
        }

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
        if ($this->showDetailedLog) {
            $this->logger->info('running updateItemToItemRelationShips()');
        }

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
                    echo '.';
                }
            }
        }
    }


    private function updateItemSort($collectionId)
    {
        if ($this->showDetailedLog) {
            $this->logger->info('running updateItemSort()');
        }

        $this->setProgress($this->queue, 0.95, "Update item sort");

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        try {
            $sort = $item->getDataAttribute('ObjObjectNumberSortedVrt');
            if ($sort) {
                $item->sort = $sort;
                if (Craft::$app->elements->saveElement($item)) {
                    echo $item->id . " - " . $sort;
                    echo "\n";
                } else {
                    echo 'Could not save item';
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    private function createOrUpdateItem($object)
    {
        $collectionId = $object->id;

        if ($this->showDetailedLog) {
            $this->logger->info('running createOrUpdateItem()');
        }

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($item)) {

            //create new
            $item = new MuseumPlusItem();
            $item->collectionId = $collectionId;
            
            $this->logger->info('running createOrUpdateItem(): create new item, collectionId: '.$item->collectionId.' - element id: not yet available');



            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;
        } else {
            if ($this->showDetailedLog) {
                $this->logger->info('running createOrUpdateItem(): update existing item');
            }

            //update
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;


        }
//dd($item->data);
        $inventoryNumber = $object->ObjObjectNumberVrt ?? '';//$item->getDataAttribute('ObjObjectNumberVrt');
        if (empty($inventoryNumber))
            $inventoryNumber = $object->ObjObjectNumberTxt ?? '';
        if ($inventoryNumber) {
            $item->inventoryNumber = $inventoryNumber;
        }


        $success = Craft::$app->elements->saveElement($item, false, true,true);


        //$success = Craft::$app->elements->saveElement($item, false,false);
        if (!$success) {
            $this->logger->error('Could not save item: ' . print_r($item->getErrors(), true));
            return false;
        } else {
            if ($this->showDetailedLog) {
                $this->logger->info('Item successfully saved ');
            }
            $this->logger->info('new or already existing element id: '.$item->id.' -- collectionId: '.$item->collectionId.' - now updating search index');

            /*
            Craft::$app->getQueue()->push(new UpdateSearchIndex([
                'elementType' => MuseumPlusItem::class,
                'elementId' => $item->id,
            ]));
            */

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
        if ($this->showDetailedLog) {
            $this->logger->info('running createAttachmentFromObjectId()');
        }


        $museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        // Paolo search this $this->museumPlus
        $attachment = $museumPlus->getAttachmentByObjectId($id);

        $settings = MuseumPlusForCraftCms::$plugin->getSettings();
        // $folderId = $this->settings['attachmentVolumeId'];
        $folderId = $settings['attachmentVolumeId'];
        if ($this->showDetailedLog) {
            $this->logger->info('attachmentVolumeId: ' . $folderId);
        }

        $folder = $this->assets->findFolder(['id' => $folderId]);
        $parentFolder = $this->createFolder("Items");
        $itemFolder = $this->createFolder($id, $parentFolder->id, $parentFolder->path);
        if ($attachment) {
            $asset = $this->createAsset($id, $attachment, $itemFolder);
            if ($asset) {
                return $asset->id;
            }
        }
        if ($this->showDetailedLog) {
            $this->logger->info('finished createAttachmentFromObjectId()');
        }

        return false;
    }

    private function createFolder($folderName, $parentFolderId = null, $parentFolderPath = null)
    {
        $volumeId = $this->settings['attachmentVolumeId'];
        $volume = Craft::$app->volumes->getVolumeById($volumeId);
        if (!$volume) {
            Craft::error("Volume with ID {$volumeId} not found.", __METHOD__);
            return false;
        }
        // Find the root folder for this volume
        $rootFolder = Craft::$app->assets->getRootFolderByVolumeId($volumeId);
        if (!$rootFolder) {
            Craft::error("Root folder for volume ID {$volumeId} not found.", __METHOD__);
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

    private function createAsset($id, $attachment, $parentFolder)
    {
        $basename = pathinfo($attachment, PATHINFO_FILENAME);
        $basename = FileHelper::sanitizeFilename($basename, [true, '_']);
        $extension = pathinfo($attachment, PATHINFO_EXTENSION);
        $filename = $basename . '_' . $id . '.' . $extension;
        $title = Assets::filename2Title($basename . '_' . $id);
        try {
            $asset = Asset::find()->title($title)->folderId($parentFolder->id)->one();
            if (is_null($asset)) {
                $asset = new Asset();
            }
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
            return false;
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

        $person = PersonRecord::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($person)) {
            //create new
            $person = new PersonRecord();
            $person->id = 0;
            $person->collectionId = $collectionId;

            $success = $person->save();
        }
        //update
        $person->data = json_encode($data);
        if (!empty($data->PerNameTxt))
            $person->title = $data->PerNameTxt;
        else if (!empty($data->PerNameTxt))
            $person->title = $data->PerPersonTxt;
        else if (!empty($data->PerNameVrt))
            $person->title = $data->PerNameVrt;
        else
            $person->title = 'Unknown';

        $success = $person->save();
        return $person;
    }

    public function actionUpdateItemsInventory()
    {
        App::maxPowerCaptain();
        $itemIds = MuseumPlusItem::find()->ids();
        foreach ($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            echo $itemId . " => ";
            try {
                $this->updateItemInventory($item->collectionId);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    private function updateVocabularyRefs(MuseumPlusItem $item)
    {
        //add vocabulary refs
        $vocabularyRefs = $item->getDataAttribute('vocabularyReferences');
        // '$vocabularyRefs:<br><textarea style="width:600px;height:500px;">'.print_r($vocabularyRefs,true).'</textarea><br>';
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
                    //echo '<textarea style="width:600px;height:500px;">Type: '.$type.' ['.$vc['id'].'] '.print_r($data,true).'</textarea>';
                    foreach ($data as $d) {
                        $vocabularyEntry = $this->createOrUpdateVocabularyEntry($type, $d);
                        if ($vocabularyEntry) {
                            if (!empty($vocabularyEntry->id)) {
                                $ids[] = $vocabularyEntry->id;
                            }
                            //echo 'vocabularyEntry id: ' . $vocabularyEntry->id . ' pid: ' . $vocabularyEntry->parentId . '<br>';
                            //probaly can be remooved to get all the tree
                            //if(($type=='GenGeoCultureVgr')||($type=='GenGeoPoliticalVgr')||($type=='GenGeoGeographyVgr')||($type=='GenGeoHistoryVgr') ){
                            // for geo vocabularies we need the whole tree
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
                            /*} else {
                                // we get only the direct parent
                                if($vocabularyEntry->parentId > 0) {
                                    //echo 'we have a parent: '.$vocabularyEntry->parentId.'<br>';
                                    $parentNodeId = $this->museumPlus->getVocabularyParentNodeId($type,$vc['id']);
                                    //echo 'parentNodeId: '.$parentNodeId.'<br>';
                                    $dataParent = $this->museumPlus->getVocabularyNode($type,$parentNodeId);
                                    foreach ($dataParent as $dp) {
                                        $vocabularyEntryParent = $this->createOrUpdateVocabularyEntry($type, $dp);
                                    }
                                }
                            }*/
                        }
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo "WARNING: " . $e->getMessage() . PHP_EOL;
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
            //echo 'v';
        }
    }


}