<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use Craft;
use craft\db\ActiveRecord;
use craft\helpers\Db;

use furbo\museumplusforcraftcms\records\DataRecord;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;


/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class MuseumPlusItemRecord extends DataRecord
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

    public function getLiterature() {
        return $this->hasMany(LiteratureRecord::className(), ['id' => 'literatureId'])
            ->viaTable('museumplus_items_literature', ['itemId' => 'id']);
    }

    public function getVocabularyEntries() {
        return $this->hasMany(VocabularyEntryRecord::className(), ['id' => 'vocabularyId'])
            ->viaTable('museumplus_items_vocabulary', ['itemId' => 'id']);
    }

    public function getVocabularyEntriesByType($type) {
        return $this->getVocabularyEntries()->where(['type' => $type]);
    }

    public function getAssociationPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjPerAssociationRef']);
            });

    }

    public function getOwnerPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjPerOwnerRef']);
            });
    }

    public function getAdministrationPeople() {
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjAdministrationRef']);
            });
    }

    public function getRelatedItems() {
        return $this->hasMany(MuseumPlusitemRecord::className(), ['id' => 'relatedItemId'])
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

    public function syncVocabularyRelations($syncData) {

        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_vocabulary}}', ['itemId' => $this->id])
            ->execute();

        foreach($syncData as $type => $ids) {
            foreach($ids as $id) {

                Craft::$app->db->createCommand()
                    ->insert('{{%museumplus_items_vocabulary}}', [
                        'itemId' => $this->id,
                        'vocabularyId' => $id
                    ])->execute();
            }
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

    public function getRepeatableGroupValues($groupName, $attribute, $filterTypes = []) {
        $data = $this->getDataAttributes();
        $ret = [];
        foreach($data['repeatableGroups'] as $group) {
            if ($group['name'] == $groupName) {
                foreach($group['items'] as $i) {
                    if (empty($filterTypes)) {
                        if (isset($i[$attribute])) {
                            $ret[] = $i[$attribute];
                        }
                    } else {
                        if(isset($i['TypeVoc'])){
                            if (in_array($i['TypeVoc'], $filterTypes)) {
                                $ret[] = $i[$attribute];
                            }
                        }
                    }
                    
                }
            }
        }
        return $ret;
    }


}
