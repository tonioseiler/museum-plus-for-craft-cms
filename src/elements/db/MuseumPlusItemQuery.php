<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\VocabularyEntryRecord;

class MuseumPlusItemQuery extends ElementQuery
{
    public $collectionId;
    public $assetId;
    public $geographic;
    public $classification;
    public $tag;
    public $objectGroup;
    public $person;


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

    public function person($value)
    {
        $this->person = $value;
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
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.collectionId', $this->collectionId));
        }

        if ($this->assetId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.assetId', $this->assetId));
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
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_vocabulary}}'])
                ->where(['vocabularyId' => $this->tag]);
            $this->subQuery->andWhere(['in', 'museumplus_items.id', $subQuery]);
        }

        if($this->objectGroup){
            $subQuery = (new Query())
                ->select(['itemId'])
                ->from(['{{%museumplus_items_objectgroups}}'])
                ->where(['objectGroupId' => $this->objectGroup]);
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

