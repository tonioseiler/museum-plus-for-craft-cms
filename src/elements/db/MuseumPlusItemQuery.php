<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

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


    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    public function geographic($value)
    {
        $this->geographic = $value;
        return $this;
    }

    public function classification($value)
    {
        $this->classification = $value;
        return $this;
    }

    public function tag($value)
    {
        $this->tag = $value;
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

        if($this->geographic){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_vocabulary}}'])
                ->where(['vocabularyId' => $this->geographic]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->classification){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_vocabulary}}'])
                ->where(['vocabularyId' => $this->classification]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->tag){
            $tagId = $this->tag;
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_vocabulary}}']);

            $subQuery = $subQuery->where(['vocabularyId' => $tagId]);


            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
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


        $this->subQuery->groupBy('museumplus_items.id');


        return parent::beforePrepare();
    }
}

