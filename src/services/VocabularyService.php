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

use craft\db\Query;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
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
class VocabularyService extends Component
{
    public function getElementById($id) {
        return MuseumPlusVocabulary::find()->id($id)->one();
    }

    public function getTypes() {
        $types = [];
        $vocabularies = (new Query())
            ->select('type')
            ->from(['{{%museumplus_vocabulary}}'])
            ->groupBy(['type'])
            ->orderBy(['type' => SORT_ASC])
            ->all();
        foreach ($vocabularies as $vocabulary) {
            $types[$vocabulary["type"]] = $vocabulary["type"];
        }
        return $types;
    }

    public function search($searchString) {
        return MuseumPlusVocabulary::find()
            ->type(['ObjClassificationVgr', 'GenPlaceVgr', 'ObjKeyWordVgr'])
            ->search($searchString)
            ->orderBy('score')
            ->limit(10)
            ->all();
    }
}
