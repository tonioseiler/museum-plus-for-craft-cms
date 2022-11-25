<?php
/**
* MuseumPlus for CraftCMS plugin for Craft CMS 3.x
*
* Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
*
* @link      https://furbo.ch
* @copyright Copyright (c) 2022 Furbo GmbH
*/

namespace furbo\museumplusforcraftcms\console\controllers;

use craft\elements\Asset;
use craft\helpers\Assets;
use craft\models\VolumeFolder;
use craft\helpers\App;

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

/**
* Collection Command
*
* The first line of this class docblock is displayed as the description
* of the Console Command in ./craft help
*
* Craft can be invoked via commandline console by using the `./craft` command
* from the project root.
*
* Console Commands are just controllers that are invoked to handle console
* actions. The segment routing is plugin-name/controller-name/action-name
*
* The actionIndex() method is what is executed if no sub-commands are supplied, e.g.:
*
* ./craft museum-plus-for-craft-cms/collection
*
* Actions must be in 'kebab-case' so actionDoSomething() maps to 'do-something',
* and would be invoked via:
*
* ./craft museum-plus-for-craft-cms/collection/do-something
*
* @author    Furbo GmbH
* @package   MuseumPlusForCraftCms
* @since     1.0.0
*/
class CollectionController extends Controller
{
    // Public Methods
    /**
    * @var bool|null if true - script finish will download all images.
    */
    public $forceAll;

    // Private Properties
    private $start;
    private $settings;
    private $museumPlus;
    public $assets;


    // Public Methods
    // =========================================================================

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->settings = MuseumPlusForCraftCms::$plugin->getSettings();
        $this->museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        $this->assets = Craft::$app->getAssets();
    }

    public function beforeAction($action)
    {
        App::maxPowerCaptain();
        ini_set('memory_limit', '512M');
        $this->start = microtime(true);
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    public function afterAction($action, $result)
    {
        $end = microtime(true);
        $time = $end - $this->start;
        $time = round($time/60, 2);
        echo PHP_EOL;
        echo "Time: " . $time . " min" . PHP_EOL;
        return parent::afterAction($action, $result); // TODO: Change the autogenerated stub
    }

    /**
    * Handle museum-plus-for-craft-cms/collection console commands
    *
    * The first line of this method docblock is displayed as the description
    * of the Console Command in ./craft help
    *
    * @return mixed
    */
    public function actionImportAll()
    {
        $this->actionImportData();
        $this->actionImportAttachments();
        $this->actionImportMultimediaObjects();
    }

    public function actionImportData()
    {
        echo 'Data import started.'.PHP_EOL;
        $this->downloadObjectGroups();
        $this->downloadItems();
        $this->downloadLiterature();
        $this->downloadPeople();
        $this->downloadOwnerships();
        $this->downloadVocabulary();
    }

    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'forceAll';
        return $options;
    }

    public function downloadPeople()
    {
        echo 'Downloading referenced people records.'.PHP_EOL;

        $existingItems = MuseumPlusItem::find()->all();
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('PT24H'));

        foreach ($existingItems as $item) {
            $newDate = null;

            if(!$this->forceAll) {
                $date = $this->museumPlus->getObjectLastModified($item->collectionId);
                try {
                    $newDate = new \DateTime($date);
                } catch (\Exception $e) {

                }
            }

            if($newDate > $yesterday || $this->forceAll){
                $moduleRefs = $item->getDataAttribute('moduleReferences');

                $types = ['ObjAdministrationRef', 'ObjPerOwnerRef', 'ObjPerAssociationRef'];

                foreach($types as $type) {
                    if(isset($moduleRefs[$type])) {
                        $ids = [];
                        foreach ($moduleRefs[$type]['items'] as $p){
                            $data = $this->museumPlus->getPerson($p['id']);
                            $person = $this->createOrUpdatePerson($data);
                            if ($person) {
                                $ids[] = $person->id;
                            }
                        }
                        //sync
                        if(count($ids)){
                            $item->syncPeopleRelations($ids, $type);
                            echo '.';
                        }
                    }
                }


            }
        }

        return true;
    }

    public function downloadOwnerships()
    {
        echo 'Downloading refernced ownership records.'.PHP_EOL;

        $existingItems = MuseumPlusItem::find()->all();
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('PT24H'));

        foreach ($existingItems as $item) {
            $newDate = null;

            echo '.';

            if(!$this->forceAll) {
                $date = $this->museumPlus->getObjectLastModified($item->collectionId);
                try {
                    $newDate = new \DateTime($date);
                } catch (\Exception $e) {

                }
            }

            if($newDate > $yesterday || $this->forceAll){
                $moduleRefs = $item->getDataAttribute('moduleReferences');

                if(isset($moduleRefs['ObjOwnershipRef'])) {
                    $ids = [];
                    foreach ($moduleRefs['ObjOwnershipRef']['items'] as $o){
                        $data = $this->museumPlus->getOwnership($o['id']);
                        $ownership = $this->createOrUpdateOwnership($data);
                        if ($ownership) {
                            $ids[] = $ownership->id;
                        }
                    }
                }
                //sync
                if(count($ids)){
                    $item->syncOwnershipRelations($ids);
                    echo '.';
                }


            }
        }

        return true;
    }

    public function downloadLiterature()
    {
        echo 'Downloading refernced literture records.'.PHP_EOL;

        $existingItems = MuseumPlusItem::find()->all();
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('PT24H'));

        foreach ($existingItems as $item) {
            $newDate = null;

            echo '.';

            if(!$this->forceAll) {
                $date = $this->museumPlus->getObjectLastModified($item->collectionId);
                try {
                    $newDate = new \DateTime($date);
                } catch (\Exception $e) {

                }
            }

            if($newDate > $yesterday || $this->forceAll){
                $moduleRefs = $item->getDataAttribute('moduleReferences');

                $ids = [];
                if(isset($moduleRefs['ObjLiteratureRef'])) {
                    foreach ($moduleRefs['ObjLiteratureRef']['items'] as $o){
                        $data = $this->museumPlus->getLiterature($o['id']);
                        if ($data){
                            $literature = $this->createOrUpdateLiterature($data);
                            if ($literature) {
                                $ids[] = $literature->id;
                            }
                        }
                    }
                }
                //sync
                if(count($ids)){
                    $item->syncLiteratureRelations($ids);
                    echo '.';
                }


            }
        }

        return true;
    }

    public function actionImportAttachments()
    {
        $existingItems = MuseumPlusItem::find()->all();
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('PT24H'));

        foreach ($existingItems as $item) {
            $newDate = null;

            if(!$this->forceAll) {
                $date = $this->museumPlus->getObjectLastModified($item->collectionId);
                try {
                    $newDate = new \DateTime($date);
                } catch (\Exception $e) {}
                }

                if($newDate > $yesterday || $this->forceAll){
                    $assetId = $this->createAttachmentFromObjectId($item->collectionId);
                    if($assetId){
                        echo "[OK] Id: " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                        $item->assetId = $assetId;
                        Craft::$app->elements->saveElement($item);
                    }else{
                        echo "[OK] Id: " . $item->id . " AssetID: NULL" . PHP_EOL;
                    }
                }
                echo ".";
            }

            return true;
        }

        public function actionRemoveAttachments()
        {
            $existingItems = MuseumPlusItem::find()->all();
            foreach ($existingItems as $item) {
                if($item->assetId) {
                    $asset = Asset::find()->id($item->assetId)->one();
                    if ($asset) {
                        $success = Craft::$app->elements->deleteElement($asset);
                        if ($success) {
                            echo "[OK] Id:" . $item->id . " AssetID " . $item->assetId . PHP_EOL;
                        } else {
                            echo "[ERROR] Id:" . $item->id . " AssetID " . $item->assetId . PHP_EOL;
                        }
                    }
                }
            }
            return true;
        }

        public function actionImportMultimediaObjects()
        {
            $existingItems = MuseumPlusItem::find()->all();
            $yesterday = new \DateTime();
            $yesterday->sub(new \DateInterval('PT24H'));

            foreach ($existingItems as $item) {
                $assetIds = [];
                $moduleRefs = $item->getDataAttribute('moduleReferences');

                if(isset($moduleRefs['ObjMultimediaRef'])) {
                    foreach ($moduleRefs['ObjMultimediaRef']['items'] as $mm){
                        $newDate = null;

                        if(!$this->forceAll) {
                            $date = $this->museumPlus->getMultimediaLastModified($mm['id']);
                            try {
                                $newDate = new \DateTime($date);
                            } catch (\Exception $e) {}
                            }

                            if($newDate > $yesterday || $this->forceAll) {
                                $assetId = $this->createMultimediaFromId($mm['id']);
                                if ($assetId) {
                                    $assetIds[] = $assetId;
                                }
                            }
                        }
                    }

                    if(count($assetIds)){
                        $item->syncMultimediaRelations($assetIds);
                        echo "Download Multimedia Assets[OK]. Item Id: " . $item->id . " Asset IDs: " . implode(",", $assetIds) . PHP_EOL;
                    }
                }
            }

            private function downloadObjectGroups() {
                $objectGroupIds = $this->settings['objectGroups'];
                $objectGroupsData = $this->museumPlus->getObjectGroups();
                foreach ($objectGroupsData as $ogd) {
                    if (in_array($ogd->id, $objectGroupIds)) {
                        $this->createOrUpdateObjectGroup($ogd);
                    }
                }

                $existingObjectGroups = ObjectGroupRecord::find()->all();
                foreach ($existingObjectGroups as $objectGroup) {
                    if (!in_array($objectGroup->collectionId, $objectGroupIds)) {
                        $success = $objectGroup->delete();
                        echo 'x';
                    }
                }

                return true;
            }

            private function downloadVocabulary() {
                echo 'Downloading referenced vocabulary records.'.PHP_EOL;

                $existingItems = MuseumPlusItem::find()->all();
                $yesterday = new \DateTime();
                $yesterday->sub(new \DateInterval('PT24H'));

                foreach ($existingItems as $item) {
                    $newDate = null;

                    if(!$this->forceAll) {
                        $date = $this->museumPlus->getObjectLastModified($item->collectionId);
                        try {
                            $newDate = new \DateTime($date);
                        } catch (\Exception $e) {

                        }
                    }

                    if($newDate > $yesterday || $this->forceAll){
                        $vocabularyRefs = $item->getDataAttribute('vocabularyReferences');

                        $syncData = [];
                        foreach($vocabularyRefs as $vocabularyRef) {
                            $ids = [];
                            $type = $vocabularyRef['instanceName'];
                            foreach ($vocabularyRef['items'] as $vc){
                                $data = $this->museumPlus->getVocabularyNode($type,$vc['id']);
                                foreach ($data as $d) {
                                    $vocabularyEntry = $this->createOrUpdateVocabularyEntry($type, $d);
                                    if ($vocabularyEntry) {
                                        $ids[] = $vocabularyEntry->id;
                                    }
                                }
                            }
                            if (isset($syncData[$type])) {
                                foreach($ids as $id) {
                                    $syncData[$type][] = $id;
                                }
                            } else {
                                $syncData[$type] = $ids;
                            }
                        }

                        if(count($syncData)){
                            $item->syncVocabularyRelations($syncData);
                            echo '.';
                        }

                    }
                }

                return true;
            }

            private function downloadItems() {
                $objectIds = [];
                foreach ($this->settings['objectGroups'] as $objectGroupId) {
                    $objects = $this->museumPlus->getObjectsByObjectGroup($objectGroupId);
                    foreach ($objects as $o) {
                        $objectIds[$o->id] = $o->id;
                        $this->createOrUpdateItem($o);
                    }
                }

                $existingItems = MuseumPlusItem::find()->all();
                foreach ($existingItems as $item) {
                    if (!isset($objectIds[$item->collectionId])) {
                        $success = Craft::$app->elements->deleteElement($item);
                        echo 'x';
                    }
                }

                //create object to object relations
                $existingItems = MuseumPlusItem::find()->all();
                foreach ($existingItems as $item) {
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
                return true;
            }

            private function createAttachmentFromObjectId($id)
            {
                $folderId = $this->settings['attachmentVolumeId'];
                $folder = $this->assets->findFolder(['id' => $folderId]);
                $parentFolder = $this->createFolder("Items", $folderId);
                $attachment = $this->museumPlus->getAttachmentByObjectId($id);

                if ($attachment) {
                    $basename = pathinfo($attachment, PATHINFO_FILENAME);
                    $extension = pathinfo($attachment, PATHINFO_EXTENSION);
                    $filename = $basename . '_' . $id . '.' . $extension;
                    try {
                        $asset = Asset::find()->filename($filename)->folderId($parentFolder->id)->one();
                        if(is_null($asset)){
                            $asset = new Asset();
                        }
                        $asset->tempFilePath = $attachment;
                        $asset->filename = $filename;
                        $asset->newFolderId = $parentFolder->id;
                        $asset->setVolumeId($parentFolder->volumeId);
                        $asset->setScenario(Asset::SCENARIO_CREATE);
                        $asset->avoidFilenameConflicts = true;

                        $result = Craft::$app->getElements()->saveElement($asset);
                        if ($result){
                            return $asset->id;
                        }else{
                            return false;
                        }
                    } catch (\Throwable $e) {
                        echo $e->getMessage();
                        return false;
                    }
                }
                return false;
            }

            private function createMultimediaFromId($id)
            {
                $folderId = $this->settings['attachmentVolumeId'];
                $folder = $this->assets->findFolder(['id' => $folderId]);
                $parentFolder = $this->createFolder("Multimedia", $folderId);
                $attachment = $this->museumPlus->getMultimediaById($id);

                if ($attachment) {
                    $basename = pathinfo($attachment, PATHINFO_FILENAME);
                    $extension = pathinfo($attachment, PATHINFO_EXTENSION);
                    $filename = $basename . '_' . $id . '.' . $extension;
                    try {
                        $asset = Asset::find()->filename($filename)->folderId($parentFolder->id)->one();
                        if(is_null($asset)){
                            $asset = new Asset();
                        }
                        $asset->tempFilePath = $attachment;
                        $asset->filename = $filename;
                        $asset->newFolderId = $parentFolder->id;
                        $asset->setVolumeId($parentFolder->volumeId);
                        $asset->setScenario(Asset::SCENARIO_CREATE);
                        $asset->avoidFilenameConflicts = true;

                        $result = Craft::$app->getElements()->saveElement($asset);
                        if ($result){
                            return $asset->id;
                        }else{
                            return false;
                        }
                    } catch (\Throwable $e) {
                        return false;
                    }
                }
                return false;
            }

            private function createFolder($folderName, $parentFolderId)
            {
                $folder = $this->assets->findFolder(['name' => $folderName, 'parentId' => $parentFolderId]);
                if (is_null($folder)) {
                    $folder = new VolumeFolder();
                    $folder->parentId = $parentFolderId;
                    $folder->name = $folderName;
                    $folder->volumeId = $this->settings['attachmentVolumeId'];
                    $folder->path = $folderName . '/';
                    $this->assets->createFolder($folder);
                    return $folder;
                } else {
                    return $folder;
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
                    if (empty($item->title)) {
                        $item->title = $object->ObjObjectTitleVrt;
                    }
                }
                $success = Craft::$app->elements->saveElement($item);

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


                echo '.';
                return true;
            }

            private function createOrUpdateObjectGroup($data) {
                $collectionId = $data->id;

                $objectGroup = ObjectGroupRecord::find()
                ->where(['collectionId' => $collectionId])
                ->one();

                if (empty($objectGroup)) {
                    //create new
                    $objectGroup = new ObjectGroupRecord();
                    $objectGroup->id = 0;
                    $objectGroup->collectionId = $collectionId;
                    $objectGroup->title = $data->OgrNameTxt;
                } else {
                    //update
                    $objectGroup->data = json_encode($data);
                    if (empty($objectGroup->title)) {
                        $objectGroup->title = $data->OgrNameTxt;
                    }
                }
                $objectGroup->data = json_encode($data);
                $success = $objectGroup->save();
                return $objectGroup;
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
                    if (empty($ownerhsip->title)) {
                        $ownerhsip->title = $data->OwsOwnershipVrt;
                    }
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
                    if (empty($literature->title)) {
                        $literature->title = $data->LitLiteratureVrt;
                    }
                }
                $literature->data = json_encode($data);
                $success = $literature->save();
                return $literature;
            }

            private function createOrUpdateVocabularyEntry($type, $data)
            {

                $collectionId = $data->id;
                $vocabularyEntry = VocabularyEntryRecord::find()
                    ->where(['collectionId' => $collectionId])
                    ->one();

                if (empty($vocabularyEntry)) {
                    //create new
                    $vocabularyEntry = new VocabularyEntryRecord();
                    $vocabularyEntry->id = 0;
                    $vocabularyEntry->collectionId = $data->id;
                    $vocabularyEntry->title = $data->content;
                } else {
                    //update
                    if (empty($vocabularyEntry->title)) {
                        $vocabularyEntry->title = $data->LitLiteratureVrt;
                    }
                }
                $vocabularyEntry->type = $type;
                $vocabularyEntry->parentId = $data->parentId;
                $vocabularyEntry->language = $data->isoLanguageCode;
                $vocabularyEntry->data = json_encode($data);
                $success = $vocabularyEntry->save();
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

        }
