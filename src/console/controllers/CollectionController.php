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
use craft\helpers\FileHelper;
use craft\models\VolumeFolder;
use craft\helpers\App;

use craft\queue\jobs\ResaveElements;
use craft\queue\jobs\UpdateSearchIndex;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\events\ItemUpdatedFromMuseumPlusEvent;

use Craft;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
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

    /**
     * @var bool|null if true - script will not download literature.
     */
    public $ignoreLiterature;

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

        $isNewItem = false;
        if (!$item) {
            echo 'Creating item (id: '.$this->collectionItemId.')'.PHP_EOL;
            $isNewItem = true;
        } else {
            echo 'Updating item (id: '.$this->collectionItemId.')'.PHP_EOL;
        }
        $this->updateItemFromMuseumPlus($this->collectionItemId);
        $this->triggerUpdateEvent($this->collectionItemId, $isNewItem);
        $this->updateItemToItemRelationShips($this->collectionItemId);
        $this->updateItemInventory($this->collectionItemId);
        $this->updateItemSort($this->collectionItemId);

    }


    public function actionUpdateItems()
    {
        //$this->forceAll = true;

        echo 'Downloading list of object groups'.PHP_EOL;
        $this->downloadObjectGroups();
        echo 'Updating Items'.PHP_EOL;
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
                    $this->triggerUpdateEvent($o->id, true);
                } else if ($this->forceAll || $item->dateUpdated < $objectLastModified) {
                    echo 'Updating item (id: '.$o->id.')'.PHP_EOL;
                    $this->updateItemFromMuseumPlus($o->id);
                    $this->triggerUpdateEvent($o->id, false);
                } else {
                    //echo '.';
                }
            }
        }

        //delete items which do not exist anymore
        $itemIds = MuseumPlusItem::find()->ids();
        foreach($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            if (!isset($objectIds[$item->collectionId])) {
                $success = Craft::$app->elements->deleteElement($item);
                echo 'Item deleted: '.$item->title.' ('.$item->id.')'.PHP_EOL;
            }
        }

        $this->actionUpdateItemsInventory();
        $this->actionUpdateItemToItemRelationShips();
        $this->actionUpdateItemParentChildRelations();
        $this->optimizeSearchIndex();

        return true;
    }

    public function actionUpdateItemToItemRelationShips()
    {
        App::maxPowerCaptain();
        //create object to object relations
        echo 'Updating item to item relationships'.PHP_EOL;
        $itemIds = MuseumPlusItem::find()->ids();
        foreach($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            $this->updateItemToItemRelationShips($item->collectionId);
        }
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

    private function updateItemFromMuseumPlus($collectionId)
    {
        //echo 'Update item '.$collectionId.PHP_EOL;
        try {
            $o = $this->museumPlus->getObjectDetail($collectionId);
            $item = $this->createOrUpdateItem($o);

            //add attachment
            //echo '- Main image'.PHP_EOL;
            if (!$this->ignoreAttachments) {
                $assetId = $this->createAttachmentFromObjectId($item->collectionId);
                if($assetId){
                    //echo "Attachment for item " . $item->id . " AssetID: " . $assetId . PHP_EOL;
                    $item->assetId = $assetId;
                    Craft::$app->elements->saveElement($item);
                } else {
                    //echo "Attachment for item " . $item->id . " AssetID: NULL" . PHP_EOL;
                }
            }

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
        }

    }

    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'forceAll';
        $options[] = 'collectionItemId';
        $options[] = 'ignoreAttachments';
        $options[] = 'ignoreMultimedia';
        $options[] = 'ignoreLiterature';
        return $options;
    }


    public function actionRemoveAttachments()
    {
        $itemIds = MuseumPlusItem::find()->ids();
        foreach($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
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

    public function actionResaveItems()
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => MuseumPlusItem::class,
            'criteria' => [
                'siteId' => '*',
                'unique' => true,
                'status' => null,
            ],
        ]));
    }

    public function actionUpdateSearchIndex()
    {
        $itemIds = MuseumPlusItem::find()->ids();
        foreach($itemIds as $itemId) {
            Craft::$app->getQueue()->push(new UpdateSearchIndex([
                'elementType' => MuseumPlusItem::class,
                'elementId' => $itemId,
            ]));
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
                //echo 'x';
            }
        }

        return true;
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

    private function createMultimediaFromId($id, $itemId = null)
    {
        $attachment = $this->museumPlus->getMultimediaById($id);
        $folderId = $this->settings['attachmentVolumeId'];
        $folder = $this->assets->findFolder(['id' => $folderId]);
        $parentFolder = $this->createFolder("Multimedia");
        $itemFolder = $this->createFolder($itemId,$parentFolder->id,$parentFolder->path);
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
            if($asset){
                return $asset->id;
            }
        }
        return false;
    }

    private function createAsset($id, $attachment, $parentFolder)
    {
        $basename = pathinfo($attachment, PATHINFO_FILENAME);
        $basename = FileHelper::sanitizeFilename($basename,[true,'_']);
        $extension = pathinfo($attachment, PATHINFO_EXTENSION);
        $filename = $basename . '_' . $id . '.' . $extension;
        $title = Assets::filename2Title($basename . '_' . $id);
        try {
            $asset = Asset::find()->title($title)->folderId($parentFolder->id)->one();
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
                //echo '- File '.$id.PHP_EOL;
                return $asset;
            }else{
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    private function createFolder($folderName, $parentFolderId = null,$parentFolderPath = null)
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
            }        }



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
            if (empty($vocabularyEntry->title)) {
                $vocabularyEntry->title = $data->LitLiteratureVrt;
            }
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
        foreach($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            echo $itemId . " => ";
            try {
                $this->updateItemInventory($item->collectionId);
            } catch (\Exception $e) {
                echo $e->getMessage().PHP_EOL;
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
                echo $item->id . " - " . $inventoryNumber;
            } else {
                echo 'Could not save item';
            }
        } else {
            echo $item->id;
        }
        echo "\n";
    }

    public function actionUpdateItemsSort()
    {
        App::maxPowerCaptain();
        $itemIds = MuseumPlusItem::find()->ids();
        foreach($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            $this->updateItemSort($item->collectionId);
        }
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
    public function actionUpdateVocabularyRefs()
    {
        App::maxPowerCaptain();

        $itemIds = MuseumPlusItem::find()
            ->ids();

        foreach($itemIds as $itemId) {
            try {
                $item = MuseumPlusItem::find()
                    ->id($itemId)
                    ->one();
                $this->updateVocabularyRefs($item);
            } catch(\Exception $e) {
                echo $e->getMessage().PHP_EOL;
            }
        }
    }

    public function actionUpdateItemTitles()
    {
        App::maxPowerCaptain();

        $itemIds = MuseumPlusItem::find()
            ->ids();

        foreach($itemIds as $itemId) {
            try {
                $item = MuseumPlusItem::find()
                    ->id($itemId)
                    ->one();
                $item->title = $item->getDataAttribute('ObjObjectTitleVrt');
                if(Craft::$app->elements->saveElement($item)) {
                    echo $item->id . " - " . $item->title;
                    echo "\n";
                }
            } catch(\Exception $e) {
                echo $e->getMessage().PHP_EOL;
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
                            if(($type=='GenGeoCultureVgr')||($type=='GenGeoPoliticalVgr')||($type=='GenGeoGeographyVgr')||($type=='GenGeoHistoryVgr') ){
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
                            } else {
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
                            }
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

    public function actionOptimizeSearchIndex()
    {
        $this->optimizeSearchIndex();
    }

    private function optimizeSearchIndex() {
        Craft::$app->db->createCommand("OPTIMIZE TABLE searchindex")->execute();
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


    private function sortArray(&$array, $key) {
        usort($array, function($a, $b) use ($key) {

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

    public function actionUpdateItemParentChildRelations()
    {

        App::maxPowerCaptain();

        //reset parent ids
        $itemRecords = MuseumPlusItemRecord::find()
            ->where(['>', 'parentId', '0'])
            ->all();
        foreach ($itemRecords as $item) {
            $item->parentId = 0;
            $item->save();
            echo '.';
        }

        //set the realtions again
        $itemRecords = MuseumPlusItemRecord::find()->all();
        foreach ($itemRecords as $item) {
            $moduleRefs = $item->getDataAttribute('moduleReferences');
            if (isset($moduleRefs['ObjObjectPartRef'])) {
                $parts = $moduleRefs['ObjObjectPartRef']['items'];
                foreach ($parts as $part) {
                    $child = MuseumPlusItemRecord::find()
                        ->where(['collectionId' => $part['id']])
                        ->one();
                    if ($child) {
                        $child->parentId = $item->collectionId;
                        $child->save();
                        echo 'x';
                    }
                }
                echo '-';
            } else {
                echo '_';
            }
        }
    }


}
