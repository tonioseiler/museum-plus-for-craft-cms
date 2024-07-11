<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use Craft;
use craft\db\ActiveRecord;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
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
            ->viaTable('museumplus_items_objectgroups', ['itemId' => 'id'], function ($query) {
                $query->orderBy(['sort' => SORT_ASC]);
            });
    }

    public function getOwnerships() {
        $tmp = $this->hasMany(OwnershipRecord::className(), ['id' => 'ownershipId'])
            ->viaTable('museumplus_items_ownerships', ['itemId' => 'id'])
            ->innerJoin('museumplus_items_ownerships', 'museumplus_items_ownerships.ownershipId = museumplus_ownerships.id')
            ->where(['museumplus_items_ownerships.itemId' => $this->id])
            ->orderBy(['museumplus_items_ownerships.sort' => SORT_ASC]);
        return $tmp;
    }

    public function getLiterature() {
        return $this->hasMany(LiteratureRecord::className(), ['id' => 'literatureId'])
            ->viaTable('museumplus_items_literature', ['itemId' => 'id'])
            ->innerJoin('museumplus_items_literature', 'museumplus_items_literature.literatureId = museumplus_literature.id')
            ->where(['museumplus_items_literature.itemId' => $this->id])
            ->orderBy(['museumplus_items_literature.sort' => SORT_ASC]);
    }

    public function getVocabularyEntries() {
        return $this->hasMany(VocabularyEntryRecord::className(), ['id' => 'vocabularyId'])
            ->viaTable('museumplus_items_vocabulary', ['itemId' => 'id'])
            ->innerJoin('museumplus_items_vocabulary', 'museumplus_items_vocabulary.vocabularyId = museumplus_vocabulary.id')
            ->where(['museumplus_items_vocabulary.itemId' => $this->id])
            ->orderBy(['museumplus_items_vocabulary.sort' => SORT_ASC]);
    }

    public function getVocabularyEntriesByType($type) {
        return $this->getVocabularyEntries()->where(['type' => $type]);
    }

    public function getAssociationPeople() {
        //TODO: FIX order/sort
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjPerAssociationRef']);
                $query->orderBy(['sort' => SORT_ASC]);
            });

    }

    public function getOwnerPeople() {
        //TODO: FIX order/sort
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjPerOwnerRef']);
                $query->orderBy(['sort' => SORT_ASC]);
            });
    }

    public function getAdministrationPeople() {
        //TODO: FIX order/sort
        return $this->hasMany(PersonRecord::className(), ['id' => 'personId'])
            ->viaTable('museumplus_items_people', ['itemId' => 'id'], function ($query) {
                $query->andWhere(['type' => 'ObjAdministrationRef']);
                $query->orderBy(['sort' => SORT_ASC]);
            });
    }

    public function getRelatedItems() {
        return $this->hasMany(MuseumPlusitemRecord::className(), ['id' => 'relatedItemId'])
            ->viaTable('museumplus_items_items', ['itemId' => 'id'])
            ->innerJoin('museumplus_items_items', 'museumplus_items_items.relatedItemId = museumplus_items.id')
            ->where(['museumplus_items_items.itemId' => $this->id])
            ->orderBy(['museumplus_items_items.sort' => SORT_ASC]);
    }

    public function syncMultimediaRelations($assetIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_assets}}', ['itemId' => $this->id])
            ->execute();

        $sort = 1;
        foreach($assetIds as $assetId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_assets}}', [
                    'itemId' => $this->id,
                    'assetId' => $assetId,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }

    public function syncPeopleRelations($peopleIds, $type) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_people}}', ['itemId' => $this->id, 'type' => $type])
            ->execute();

        $sort = 1;
        foreach($peopleIds as $personId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_people}}', [
                    'itemId' => $this->id,
                    'personId' => $personId,
                    'type' => $type,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }

    public function syncVocabularyRelations($syncData) {

        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_vocabulary}}', ['itemId' => $this->id])
            ->execute();

        foreach($syncData as $type => $ids) {
            $sort = 1;
            foreach($ids as $id) {
                Craft::$app->db->createCommand()
                    ->insert('{{%museumplus_items_vocabulary}}', [
                        'itemId' => $this->id,
                        'vocabularyId' => $id,
                        'sort' => $sort
                    ])->execute();
                $sort++;
            }
        }
    }

    public function syncOwnershipRelations($ownershipIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_ownerships}}', ['itemId' => $this->id])
            ->execute();

        $sort = 1;
        foreach($ownershipIds as $ownershipId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_ownerships}}', [
                    'itemId' => $this->id,
                    'ownershipId' => $ownershipId,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }

    public function syncLiteratureRelations($literureIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_literature}}', ['itemId' => $this->id])
            ->execute();

        $sort = 1;
        foreach($literureIds as $literureId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_literature}}', [
                    'itemId' => $this->id,
                    'literatureId' => $literureId,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }

    public function syncItemRelations($itemIds) {
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_items}}', ['itemId' => $this->id])
            ->execute();

        $sort = 1;
        foreach($itemIds as $itemId){
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items_items}}', [
                    'itemId' => $this->id,
                    'relatedItemId' => $itemId,
                    'sort' => $sort
                ])->execute();
            $sort++;
        }
    }

    public function getRepeatableGroupValues($groupName, $attribute = null, $filterTypes = []) {
        $data = $this->getDataAttributes();
        $ret = [];
        if (isset($data['repeatableGroups'])) {
            foreach($data['repeatableGroups'] as $group) {
                if ($group['name'] == $groupName) {
                    foreach($group['items'] as $i) {
                        if (empty($filterTypes)) {
                            if (empty($attribute)) {
                                $ret[] = $i;
                            } else {
                                if (isset($i[$attribute])) {
                                    $ret[] = $i[$attribute];
                                }
                            }
                            
                        } else {
                            if(isset($i['TypeVoc'])){
                                if (in_array($i['TypeVoc'], $filterTypes)) {
                                    if (empty($attribute)) {
                                        $ret[] = $i;
                                    } else {
                                        $ret[] = $i[$attribute];
                                    }
                                }
                            }
                        }
                        
                    }
                }
            }
        }
        return $ret;
    }

    public function hasChildren() {
        return !empty($this->getChildren()) && $this->getChildren()->count() > 0;
    }

    public function getChildren() {
        return $this->hasMany(MuseumPlusItemRecord::className(), ['parentId' => 'collectionId']);
    }

    public function getParent():MuseumPlusItemRecord|null
    {
        if (empty($this->parentId)) {
            return null;
        }
        return MuseumPlusItemRecord::find()->where(['collectionId' => $this->parentId])->one();
    }

    public function getTitle()
    {
        $element = MuseumPlusItem::find()->id($this->id)->one();
        return $element->title;
    }

    public function getUrl()
    {
        $element = MuseumPlusItem::find()->id($this->id)->one();
        return $element->getUrl();
    }


}
