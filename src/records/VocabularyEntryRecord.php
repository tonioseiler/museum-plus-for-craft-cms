<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use craft\db\ActiveRecord;

use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
use furbo\museumplusforcraftcms\records\DataRecord;


/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class VocabularyEntryRecord extends DataRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_vocabulary}}';
    }

    public function getItems() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_vocabulary', ['vocabularyId' => 'id']);
    }

    public function getTitle()
    {
        $element = MuseumPlusVocabulary::find()->id($this->id)->one();
        return $element->title;
    }

    public function getParent():VocabularyEntryRecord|null
    {
        if (empty($this->parentId)) {
            return null;
        }
        return VocabularyEntryRecord::find()->where(['collectionId' => $this->parentId])->one();
    }

    //@paolo: I found a simple way to implement this method. I hope it helps.
    public function getParents()
    {
        $parent = $this->getParent();
        if ($parent) {
            return [$parent] + $parent->getParents();
        } else {
            return [];
        }
    }

    public function getPath()
    {
        return array_filter([$this] + $this->getParents());
    }


    //@tonio maybe here we should have getParents() ...


}
