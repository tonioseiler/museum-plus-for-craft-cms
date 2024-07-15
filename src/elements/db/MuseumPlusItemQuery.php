<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

class MuseumPlusItemQuery extends ElementQuery
{
    public $collectionId;
    public $assetId;
    public $geographic;
    public $classification;
    public $tag;
    public $objectGroup;
    public $objectGroupId;
    public $person;
    public $inventoryNumber;
    public $extraTitle;
    public $extraDescription;

    public $vocabularyIds = [];


    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    public function objectGroup($value)
    {
        $this->objectGroup = $value;
        return $this;
    }

    public function objectGroupId($value)
    {
        $this->objectGroupId = $value;
        return $this;
    }

    public function person($value)
    {
        $this->person = $value;
        return $this;
    }

    public function inventoryNumber($value)
    {
        $this->inventoryNumber = $value;
        return $this;
    }

    public function extraTitle($value)
    {
        $this->extraTitle = $value;
        return $this;
    }

    public function extraDescription($value)
    {
        $this->extraDescription = $value;
        return $this;
    }

    public function vocabularyIds($value)
    {
        $this->vocabularyIds[] = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the items table
        $this->joinElementTable('museumplus_items');

        // select the collection id column
        $this->query->select([
            'museumplus_items.collectionId',
            'museumplus_items.data',
            'museumplus_items.assetId',
            'museumplus_items.inventoryNumber',
            'museumplus_items.extraTitle',
            'museumplus_items.extraDescription',
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.collectionId', $this->collectionId));
        }

        if ($this->assetId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.assetId', $this->assetId));
        }

        if($this->inventoryNumber){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.inventoryNumber', $this->inventoryNumber));
        }

        if(!is_null($this->extraTitle)){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.extraTitle', false));
        }

        if(!is_null($this->extraDescription)){
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.extraDescription', false));
        }

        if($this->objectGroup){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_objectgroups}}'])
                ->where(['objectGroupId' => $this->objectGroup]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->objectGroupId){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_objectgroups}}'])
                ->where(['objectGroupId' => $this->objectGroupId]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->person){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_people}}'])
                ->where(['personId' => $this->person]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }


        if(!empty($this->vocabularyIds)){
            $allDescendantVocabularyIds = $this->vocabularyIds;
            foreach ($this->vocabularyIds as $vocabularyId) {
                $descendantsIds = [];
                $record = VocabularyEntryRecord::findOne($vocabularyId);

                //$descendants = $record->getChildren();
                $descendants = $record->getDescendants();

                /*
                foreach ($descendants->all() as $descendant) {
                    $descendantsIds[] = $descendant->id;
                }
                */

                foreach ($descendants as $descendant) {
                    $descendantsIds[] = $descendant->id;
                }
                if (!empty($descendantsIds)) {
                    $allDescendantVocabularyIds = array_merge($allDescendantVocabularyIds,$descendantsIds);
                }
                $allDescendantVocabularyIds = array_map('intval', $allDescendantVocabularyIds);
            }
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_vocabulary}}'])
                ->where(['in', 'vocabularyId', $allDescendantVocabularyIds]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }
        $this->subQuery->groupBy('museumplus_items.id');
        return parent::beforePrepare();
    }
}

