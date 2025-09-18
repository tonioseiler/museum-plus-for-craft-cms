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

class OwnershipRecord extends DataRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_ownerships}}';
    }

    public function getItems() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_items_ownerships', ['ownershipId' => 'id']);
    }

    public function getPeople()
    {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['id' => 'itemId'])
            ->viaTable('museumplus_ownerships_people', ['ownershipId' => 'id']);
    }

    public function syncPeopleRelations($peopleIds)
    {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_ownerships_people}}', ['ownershipId' => $this->id])
            ->execute();

        $sort = 1;
        foreach ($peopleIds as $personId) {
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_ownerships_people}}', [
                    'ownershipId' => $this->id,
                    'personId' => $personId,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }



}
