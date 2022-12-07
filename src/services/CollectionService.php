<?php
/**
* MuseumPlus for CraftCMS plugin for Craft CMS 3.x
*
* Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
*
* @link      https://furbo.ch
* @copyright Copyright (c) 2022 Furbo GmbH
*/

namespace furbo\museumplusforcraftcms\services;

use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;

use Craft;
use craft\base\Component;
use craft\helpers\App;


/**
* MuseumPlus Service
*
* From any other plugin file, call it like this:
*
*     MuseumPlusForCraftCms::$plugin->collection->someMethod()
*
*
* @author    Furbo GmbH
* @package   MuseumPlusForCraftCms
* @since     1.0.0
*/
class CollectionService extends Component
{
    public function getAllObjectGroups() {
        $objectGroupRecords = ObjectGroupRecord::find()
                ->orderBy(['title' => SORT_ASC])
                ->all();
        return $objectGroupRecords;
    }

    public function getItemsByTag($tagId) {
        return 'Todo: implement';
    }

    public function getItemById($id) {
        $item = MuseumPlusItem::find()
            ->id($id)
            ->one();
        return $item;
    }

    public function getItemsByIds($ids) {
        return 'Todo: implement';
    }

    public function searchItems($params) {
        return 'Todo: implement';
    }
}
