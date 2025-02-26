<?php

namespace furbo\museumplusforcraftcms\jobs;

use Craft;
use craft\queue\BaseJob;
use furbo\museumplusforcraftcms\events\ItemUpdatedFromMuseumPlusEvent;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\services\MuseumPlusService;
/**
 * Job to update a MuseumPlusItem.
 */
class UpdateItemJob extends BaseJob
{
    public int $collectionId;

    public function execute($queue): void
    {
        $logger = MuseumPlusForCraftCms::getLogger();

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $this->collectionId])
            ->one();

        $isNewItem = !$item;
        $message = $isNewItem
            ? "Creating new MuseumPlusItem (ID: {$this->collectionId})."
            : "Updating MuseumPlusItem (ID: {$this->collectionId}).";

        Craft::info($message, 'museumplus');
        $logger->info($message);
        try {
            // Call the correct update functions
            $this->updateItemFromMuseumPlus($this->collectionId);
            /* TODO finish and reactivate
            $this->triggerUpdateEvent($this->collectionId, $isNewItem);
            $this->updateItemToItemRelationShips($this->collectionId);
            $this->updateItemInventory($this->collectionId);
            $this->updateItemSort($this->collectionId);
            */

            $message = "Successfully processed MuseumPlusItem (ID: {$this->collectionId}).";
            Craft::info($message, 'museumplus');
            $logger->info($message);

        } catch (\Throwable $e) {
            $message = "Error processing MuseumPlusItem (ID: {$this->collectionId}): " . $e->getMessage();
            Craft::error($message, 'museumplus');
            $logger->error($message);
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating MuseumPlusItem');
    }

    private function updateItemFromMuseumPlus($collectionId)
    {
        $logger = MuseumPlusForCraftCms::getLogger();
        $message = "Running updateItemFromMuseumPlus('{$this->collectionId}').";
        $logger->info($message);

        //echo 'Update item '.$collectionId.PHP_EOL;
        try {


            $museumPlusService = Craft::$app->getModule('museum-plus-for-craft-cms')->get('museumPlusService');
            return;



            $o = $museumPlusService->getObjectDetail($collectionId);
            $item = $this->createOrUpdateItem($o);

            //add attachment
            //echo '- Main image'.PHP_EOL;
            /*if (!$this->ignoreAttachments) {*/
                $assetId = $this->createAttachmentFromObjectId($item->collectionId);
                if($assetId){
                    //echo "Attachment for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                    $item->assetId = $assetId;
                    Craft::$app->elements->saveElement($item);
                } else {
                    //echo "Attachment for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                }
           /*}*/

            $moduleRefs = $item->getDataAttribute('moduleReferences');

            //add multimedia
            //echo '- Multimedia files'.PHP_EOL;
            if(!$this->ignoreMultimedia && isset($moduleRefs['ObjMultimediaRef'])) {
                $assetIds = [];
                $refs = $moduleRefs['ObjMultimediaRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $mm){
                    $assetId = $this->createMultimediaFromId($mm['id'],$collectionId);
                    if ($assetId) {
                        $assetIds[] = $assetId;
                    }
                }
                if(count($assetIds)){
                    $item->syncMultimediaRelations($assetIds);
                    //echo "Multimedia assets for Item Id: " . $item->id . " Asset IDs: " . implode(",", $assetIds) . PHP_EOL;
                }
            }

            //add literature relations
            $literatureIds = [];
            if(isset($moduleRefs['ObjLiteratureRef'])) {
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $l){
                    try {
                        $data = $this->museumPlus->getLiterature($l['id']);
                        if ($data){
                            $literature = $this->createOrUpdateLiterature($data);
                            if ($literature) {
                                $literatureIds[] = $literature->id;
                            }
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        echo "WARNING: ".$e->getMessage().PHP_EOL;
                    }
                }
            }

            //sync
            if(count($literatureIds)){
                $item->syncLiteratureRelations($literatureIds);
                //echo 'l';
            }

            //add literature assets
            if(!$this->ignoreLiterature && isset($moduleRefs['ObjLiteratureRef'])) {
                $assetIds = [];
                $refs = $moduleRefs['ObjLiteratureRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $l){
                    $assetId = $this->createLiteratureFromId($l['id']);
                    $literature = $this->museumPlus->getLiterature($l['id']);
                    if($assetId && $literature){
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                        $literature->assetId = $assetId;
                        $literature->save();
                    }else{
                        //echo "Literature for id " . $literature->id . " for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                    }
                }
            }

            //add people refs
            $peopleTypes = ['ObjAdministrationRef', 'ObjPerOwnerRef', 'ObjPerAssociationRef'];
            foreach($peopleTypes as $peopleType) {
                if(isset($moduleRefs[$peopleType])) {
                    $peopleIds = [];
                    $refs = $moduleRefs[$peopleType]['items'];
                    $this->sortArray($refs, 'SortLnu');
                    foreach ($refs as $p){
                        try {
                            $data = $this->museumPlus->getPerson($p['id']);
                            $person = $this->createOrUpdatePerson($data);
                            if ($person) {
                                $peopleIds[] = $person->id;
                            }
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            echo "WARNING: ".$e->getMessage().PHP_EOL;
                        }
                    }
                    //sync
                    if(count($peopleIds)){
                        $item->syncPeopleRelations($peopleIds, $peopleType);
                        //echo 'p';
                    }
                }
            }

            //add owenrship refs
            $ownershipIds = [];
            if(isset($moduleRefs['ObjOwnershipRef'])) {
                $refs = $moduleRefs['ObjOwnershipRef']['items'];
                $this->sortArray($refs, 'SortLnu');
                foreach ($refs as $o){
                    try {
                        $data = $this->museumPlus->getOwnership($o['id']);
                        $ownership = $this->createOrUpdateOwnership($data);
                        if ($ownership) {
                            $ownershipIds[] = $ownership->id;
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        echo "WARNING: ".$e->getMessage().PHP_EOL;
                    }
                }
            }
            //sync
            if(count($ownershipIds)){
                $item->syncOwnershipRelations($ownershipIds);
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

    private function triggerUpdateEvent($collectionItemId, $isNewItem = false) {
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

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        $moduleRefs = $item->getDataAttribute('moduleReferences');

        $types = ['ObjObjectARef', 'ObjObjectBRef',];

        foreach($types as $type) {
            if(isset($moduleRefs[$type])) {
                $ids = [];
                foreach ($moduleRefs[$type]['items'] as $i){
                    $tmp = MuseumPlusItem::find()
                        ->where(['collectionId' => $i['id']])
                        ->one();
                    if ($tmp) {
                        $ids[] = $tmp->id;
                    }
                }
                //sync
                if(count($ids)){
                    $item->syncItemRelations($ids);
                    echo '.';
                }
            }
        }
    }

    private function updateItemInventory($collectionId)
    {

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        $inventoryNumber = $item->getDataAttribute('ObjObjectNumberVrt');
        if (empty($inventoryNumber))
            $inventoryNumber = $item->getDataAttribute('ObjObjectNumberTxt');

        if($inventoryNumber){
            $item->inventoryNumber = $inventoryNumber;
            if(Craft::$app->elements->saveElement($item, false)) {
                //if(Craft::$app->elements->saveElement($item, false, false)) {
                echo $item->id . " - " . $inventoryNumber;
            } else {
                echo 'Could not save item';
            }
        } else {
            echo $item->id;
        }
        echo "\n";
    }

    private function updateItemSort($collectionId)
    {

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
            echo $e->getMessage().PHP_EOL;
        }
    }

    private function createOrUpdateItem($object) {
        $collectionId = $object->id;

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();

        if (empty($item)) {
            //create new
            $item = new MuseumPlusItem();
            $item->collectionId = $collectionId;
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;
        } else {
            //update
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;
        }
        $success = Craft::$app->elements->saveElement($item, false);
        //$success = Craft::$app->elements->saveElement($item, false,false);

        //insert object relations if they do not exist
        $itemRecord = $item->getRecord();
        $itemRecord->unlinkAll('objectGroups', true);

        $moduleReferences = $item->getDataAttribute('moduleReferences');
        if (isset($moduleReferences['ObjObjectGroupsRef'])) {
            foreach($moduleReferences['ObjObjectGroupsRef']['items'] as $og) {
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
        $attachment = $this->museumPlus->getAttachmentByObjectId($id);
        $folderId = $this->settings['attachmentVolumeId'];
        $folder = $this->assets->findFolder(['id' => $folderId]);
        $parentFolder = $this->createFolder("Items");
        $itemFolder = $this->createFolder($id,$parentFolder->id,$parentFolder->path);
        if ($attachment) {
            $asset = $this->createAsset($id, $attachment, $itemFolder);
            if($asset){
                return $asset->id;
            }
        }
        return false;
    }


}