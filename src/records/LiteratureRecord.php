<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use craft\db\ActiveRecord;

use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;


/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class LiteratureRecord extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_literature}}';
    }

    public function getItems() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_literure', ['literatureId' => 'id']);
    }


}
