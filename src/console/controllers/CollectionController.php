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

use furbo\museumplusforcraftcms\MuseumplusForCraftcms;
use furbo\museumplusforcraftcms\elements\MuseumplusItem;

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
 * @package   MuseumplusForCraftcms
 * @since     1.0.0
 */
class CollectionController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Handle museum-plus-for-craft-cms/collection console commands
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
     public function actionImport()
     {
         $settings = MuseumplusForCraftcms::$plugin->getSettings();
         $collection = MuseumplusForCraftcms::$plugin->collection;

         $objectIds = [];

         //create or update items
         foreach ($settings['objectGroups'] as $objectGroupId) {
             $objects = $collection->getObjectsByObjectGroup($objectGroupId);
             foreach ($objects as $o) {
                 $objectIds[$o->id] = $o->id;
                 $this->createOrUpdateItem($o);
             }
         }

         //delete items
         $existingItems = MuseumplusItem::find()
             ->all();
         foreach ($existingItems as $item) {
             if (!isset($objectIds[$item->collectionId])) {
                 $success = Craft::$app->elements->deleteElement($item);
                 echo 'x';
             }
         }

         //download attachments
         foreach ($objectIds as $id) {
             //get attachment from collection service by id
             //create asset
             //$assets = Craft::$app->getAssets();
             /*$folder = $assets->findFolder(['path' => $path]);
                if (empty($folder)) {
                    //create folder
                    $folder = new \craft\models\VolumeFolder();
                    $folder->name = $folderPathArr[$i - 1];
                    $folder->parentId = !empty($parentFolder) ? $parentFolder->id : 1;
                    $folder->volumeId = 1;
                    $folder->path = $path;

                    $assets->createFolder($folder);
                }*/
            /*
            // Check the permissions to upload in the resolved folder.
                $filename = \craft\helpers\Assets::prepareAssetName($uploadedFile->name);

                $asset = new \craft\elements\Asset();
                $asset->tempFilePath = $tempPath;
                $asset->filename = $filename;
                $asset->newFolderId = $folder->id;
                $asset->volumeId = $folder->volumeId;
                $asset->avoidFilenameConflicts = true;
                $asset->setScenario(\craft\elements\Asset::SCENARIO_CREATE);

                Craft::$app->getElements()->saveElement($asset);
            */

            //assign to
            //Craft::$app->getRelations()->saveRelations($field, $element, $targetIds);

         }


         return true;
     }



    private function createOrUpdateItem($object) {
        $collectionId = $object->id;

        $item = MuseumplusItem::find()
            ->where(['collectionId' => $collectionId])
            ->one();
        if (empty($item)) {
            //create new
            $item = new MuseumplusItem();
            $item->collectionId = $collectionId;
            $item->data = json_encode($object);
            $item->title = $object->ObjObjectTitleVrt;
            $success = Craft::$app->elements->saveElement($item);
        } else {
            //update
            $item->data = json_encode($object);
            if (empty($item->title)) {
                $item->title = $object->ObjObjectTitleVrt;
            }
            $success = Craft::$app->elements->saveElement($item);
        }
        echo '.';
        return true;
    }

}
