<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use craft\db\ActiveRecord;

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


}
