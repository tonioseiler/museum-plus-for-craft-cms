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
    /**
    * @var bool|null if true - script will download all data.
    */
    public $forceAll;

    /**
    * @var int|null if set, script will downoad only this item.
    */
    public $collectionItemId;

    /**
    * @var bool|null if true - script will not download attachments.
    */
    public $ignoreAttachments;

    /**
    * @var bool|null if true - script will not download multimedia.
    */
    public $ignoreMultimedia;

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

    public function actionUpdateItem() {

        if (empty($this->collectionItemId)) {
            echo 'Missing param: collectionItemId'.PHP_EOL;
            return false;
        }


        $item = MuseumPlusItem::find()
                ->where(['collectionId' => $this->collectionItemId])
                ->one();

        if (!$item) {
            echo 'creating item (id: '.$this->collectionItemId.')'.PHP_EOL;
            $this->updateItemFromMuseumPlus($this->collectionItemId);
        } else {
            echo 'updating item (id: '.$this->collectionItemId.')'.PHP_EOL;
            $this->updateItemFromMuseumPlus($this->collectionItemId);
        }
    }


    public function actionUpdateItems()
    {

        $this->downloadObjectGroups();

        $objectIds = [];
        foreach ($this->settings['objectGroups'] as $objectGroupId) {
            $objects = $this->museumPlus->getObjectsByObjectGroup($objectGroupId, ['__id', '__lastModifiedUser', '__lastModified']);
            foreach ($objects as $o) {
                $objectIds[$o->id] = $o->id;

                //check if item exists and if last mod is before last mod in mplus
                $objectLastModified = new \DateTime($o->__lastModified);
                $item = MuseumPlusItem::find()
                        ->where(['collectionId' => $o->id])
                        ->one();

                if (!$item) {
                    echo 'Creating item (id: '.$o->id.')'.PHP_EOL;
                    $this->updateItemFromMuseumPlus($o->id);
                } else if ($this->forceAll || $item->dateUpdated < $objectLastModified) {
                    echo 'Updating item (id: '.$o->id.')'.PHP_EOL;
                    $this->updateItemFromMuseumPlus($o->id);
                } else {
                    echo '.';
                }
            }
        }

        $existingItems = MuseumPlusItem::find()->all();
        foreach ($existingItems as $item) {
            if (!isset($objectIds[$item->collectionId])) {
                $success = Craft::$app->elements->deleteElement($item);
                echo 'Item deleted: '.$item->title.' ('.$item->id.')'.PHP_EOL;
            }
        }

        //create object to object relations
        echo 'Echo updating item to item relationships'.PHP_EOL;
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

    private function updateItemFromMuseumPlus($collectionId)
    {
        echo 'Update item '.$collectionId.PHP_EOL;

        $o = $this->museumPlus->getObjectDetail($collectionId);
        $item = $this->createOrUpdateItem($o);

        $moduleRefs = $item->getDataAttribute('moduleReferences');

        //add literature relations
        $moduleRefs = $item->getDataAttribute('moduleReferences');
        $literatureIds = [];
        if(isset($moduleRefs['ObjLiteratureRef'])) {
            foreach ($moduleRefs['ObjLiteratureRef']['items'] as $l){
                $data = $this->museumPlus->getLiterature($l['id']);
                if ($data){
                    $literature = $this->createOrUpdateLiterature($data);
                    if ($literature) {
                        $literatureIds[] = $literature->id;
                    }
                }
            }
        }
        //sync
        if(count($literatureIds)){
            $item->syncLiteratureRelations($literatureIds);
            echo 'l';
        }

        //add people refs
        $peopleTypes = ['ObjAdministrationRef', 'ObjPerOwnerRef', 'ObjPerAssociationRef'];
        foreach($peopleTypes as $peopleType) {
            if(isset($moduleRefs[$peopleType])) {
                $peopleIds = [];
                foreach ($moduleRefs[$peopleType]['items'] as $p){
                    $data = $this->museumPlus->getPerson($p['id']);
                    $person = $this->createOrUpdatePerson($data);
                    if ($person) {
                        $peopleIds[] = $person->id;
                    }
                }
                //sync
                if(count($peopleIds)){
                    $item->syncPeopleRelations($peopleIds, $peopleType);
                    echo 'p';
                }
            }
        }

        //add owenrship refs
        $ownershipIds = [];
        if(isset($moduleRefs['ObjOwnershipRef'])) {
            foreach ($moduleRefs['ObjOwnershipRef']['items'] as $o){
                $data = $this->museumPlus->getOwnership($o['id']);
                $ownership = $this->createOrUpdateOwnership($data);
                if ($ownership) {
                    $ownershipIds[] = $ownership->id;
                }
            }
        }
        //sync
        if(count($ownershipIds)){
            $item->syncOwnershipRelations($ownershipIds);
            echo 'o';
        }

        //add vocabulary refs
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
            echo 'v';
        }
        echo PHP_EOL;

        //add attachment
        if (!$this->ignoreAttachments) {
            $assetId = $this->createAttachmentFromObjectId($item->collectionId);
            if($assetId){
                echo "Attachment for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                $item->assetId = $assetId;
                Craft::$app->elements->saveElement($item);
            }else{
                echo "Attachment for item " . $item->id . " AssetID: NULL" . PHP_EOL;
            }
        }


        //add multimedia
        if(!$this->ignoreMultimedia && isset($moduleRefs['ObjMultimediaRef'])) {

            $assetIds = [];
            foreach ($moduleRefs['ObjMultimediaRef']['items'] as $mm){
                $asset = $this->createMultimediaFromId($mm['id']);
                if ($asset) {
                    $assetIds[] = $asset->id;
                }
            }

            if(count($assetIds)){
                $item->syncMultimediaRelations($assetIds);
                echo "Multimedia assets for Item Id: " . $item->id . " Asset IDs: " . implode(",", $assetIds) . PHP_EOL;
            }
        }

    }

    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'forceAll';
        $options[] = 'collectionItemId';
        $options[] = 'ignoreAttachments';
        $options[] = 'ignoreMultimedia';
        return $options;
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

    private function downloadObjectGroups() {
        echo 'Downloading object groups.'.PHP_EOL;
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
                    return $asset;
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
        return $item;
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
