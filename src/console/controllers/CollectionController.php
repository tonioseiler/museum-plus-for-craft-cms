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

         foreach ($settings['objectGroups'] as $objectGroupId) {
             $objects = $collection->getObjectsByObjectGroup($objectGroupId);
             foreach ($objects as $o) {
                 $this->createOrUpdateItem($o);
             }
         }
         return null;
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
            $item->title = $object['ObjObjectTitleVrt'];
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
