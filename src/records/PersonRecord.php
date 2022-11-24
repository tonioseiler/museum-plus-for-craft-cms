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

class PersonRecord extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_people}}';
    }

    public function getItems() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_objectgroups', ['objectGroupId' => 'id']);
    }

    public function __get($name)
    {
        $data = json_decode(parent::__get('data'), true);
        if ($name == 'attributes') {
            return $data;
        } else if (array_key_exists($name, $data)) {
            return $data[$name];
        } else {
            return parent::__get($name);
        }
    }


}
