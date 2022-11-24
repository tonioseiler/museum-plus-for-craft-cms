<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use Craft;
use craft\db\ActiveRecord;

use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;

/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class MuseumPlusItemRecord extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_items}}';
    }

    public function getObjectGroups() {
        return $this->hasMany(ObjectGroupRecord::className(), ['id' => 'objectGroupId'])
            ->viaTable('museumplus_items_objectgroups', ['itemId' => 'id']);
    }

    public function getOwnerships() {
        return $this->hasMany(OwnershipRecord::className(), ['id' => 'ownershipId'])
            ->viaTable('museumplus_items_ownerships', ['itemId' => 'id']);
    }

    public function getAssociationPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->where(['type' => 'ObjPerAssociationRef'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id']);
    }

    public function getOwnerPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->where(['type' => 'ObjPerOwnerRef'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id']);
    }

    public function getAdministrationPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->where(['type' => 'ObjAdministrationRef'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id']);
    }

    public function getRelatedItems() {
        return $this->hasMany(MuseumPlusitemRecord::className(), ['id' => 'reltedItemId'])
            ->viaTable('museumplus_items_items', ['itemId' => 'id']);
    }

    public function syncMultimediaRelations($assetIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_assets}}', ['itemId' => $this->id])
            ->execute();

        foreach($assetIds as $assetId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_assets}}', [
                    'itemId' => $this->id,
                    'assetId' => $assetId,
                ])->execute();
        }
    }

    public function syncPeopleRelations($peopleIds, $type) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_people}}', ['itemId' => $this->id, 'type' => $type])
            ->execute();

        foreach($peopleIds as $personId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_people}}', [
                    'itemId' => $this->id,
                    'personId' => $personId,
                    'type' => $type,
                ])->execute();
        }
    }

    public function syncOwnershipRelations($ownershipIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_ownerships}}', ['itemId' => $this->id])
            ->execute();

        foreach($ownershipIds as $ownershipId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_ownerships}}', [
                    'itemId' => $this->id,
                    'ownershipId' => $ownershipId
                ])->execute();
        }
    }

    public function syncLiteratureRelations($literureIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_literature}}', ['itemId' => $this->id])
            ->execute();

        foreach($literureIds as $literureId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_literature}}', [
                    'itemId' => $this->id,
                    'literatureId' => $literureId
                ])->execute();
        }
    }

    public function syncItemRelations($itemIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_items}}', ['itemId' => $this->id])
            ->execute();

        foreach($itemIds as $itemId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_items}}', [
                    'itemId' => $this->id,
                    'relatedItemId' => $itemId
                ])->execute();
        }
    }



}
