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

class PersonRecord extends DataRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_people}}';
    }

    public function getItems() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_objectgroups', ['objectGroupId' => 'id']);
    }

    public function getOwnerships()
    {
        $tmp = $this->hasMany(OwnershipRecord::className(), ['id' => 'ownershipId'])
            ->viaTable('museumplus_ownerships_people', ['personId' => 'id'])
            ->innerJoin('museumplus_ownerships_people', 'museumplus_ownerships_people.ownershipId = museumplus_ownerships.id')
            ->where(['museumplus_ownerships_people.personId' => $this->id])
            ->orderBy(['museumplus_ownerships_people.sort' => SORT_ASC]);
        return $tmp;
    }


}
