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
    public $objectGroupId;
    public $geographic;
    public $classification;
    public $tag;
    public $objectGroup;


    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    public function objectGroupId($value)
    {
        $this->objectGroupId = $value;

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
            $geoGraphicQuery = VocabularyEntryRecord::find()
                ->select('{{%museumplus_vocabulary}}.id')
                ->innerJoin("{{%content}} c", "{{%museumplus_vocabulary}}.id = c.elementId")
                ->andWhere(Db::parseParam('{{%museumplus_vocabulary}}.type', "GenPlaceVgr"))
                ->andWhere("c.title LIKE '".addslashes($this->geographic)."'")
                ->groupBy('id');

            if(count($geoGraphicQuery->all()) > 0){
                $this->subQuery->innerJoin("{{%museumplus_items_vocabulary}} itemsVocabulary", "itemsVocabulary.itemId = museumplus_items.id AND itemsVocabulary.vocabularyId = ".$geoGraphicQuery->one()->id);
            }
        }

        if($this->classification){
            $classificationQuery = VocabularyEntryRecord::find()
                ->select('{{%museumplus_vocabulary}}.id')
                ->innerJoin("{{%content}} c", "{{%museumplus_vocabulary}}.id = c.elementId")
                ->andWhere(Db::parseParam('{{%museumplus_vocabulary}}.type', "ObjClassificationVgr"))
                ->andWhere("c.title LIKE '".addslashes($this->classification)."'")
                ->groupBy('id');
        //227880
        //    dd($classificationQuery->one()->id);
            if(count($classificationQuery->all()) > 0){
                $this->subQuery->innerJoin("{{%museumplus_items_vocabulary}} itemsVocabulary", "itemsVocabulary.itemId = museumplus_items.id AND itemsVocabulary.vocabularyId = ".$classificationQuery->one()->id);
            }
        }
/*
        if($this->classification){
            $this->subQuery->innerJoin("{{%museumplus_items_vocabulary}} itemsVocabulary", "itemsVocabulary.itemId = museumplus_items.id");
            $this->subQuery->innerJoin("{{%museumplus_vocabulary}} vocabulary", "itemsVocabulary.vocabularyId = vocabulary.id");
            $this->subQuery->innerJoin("{{%content}} c", "vocabulary.id = c.elementId");

            $this->subQuery->andWhere(Db::parseParam('vocabulary.type', "ObjClassificationVgr"));
            $this->subQuery->andWhere("c.title LIKE '".addslashes($this->classification)."'");
        }*/

        if($this->tag){
            $this->subQuery->innerJoin("{{%museumplus_items_vocabulary}} itemsVocabulary", "itemsVocabulary.itemId = museumplus_items.id");
            $this->subQuery->innerJoin("{{%museumplus_vocabulary}} vocabulary", "itemsVocabulary.vocabularyId = vocabulary.id");
            $this->subQuery->innerJoin("{{%content}} c", "vocabulary.id = c.elementId");

            $this->subQuery->andWhere(Db::parseParam('vocabulary.type', "ObjKeyWordVgr"));
            $this->subQuery->andWhere("c.title LIKE '".addslashes($this->tag)."'");
        }

        if($this->objectGroup){
            $this->subQuery->innerJoin("{{%museumplus_items_objectgroups}} itemsObjectgroups", "itemsObjectgroups.itemId = museumplus_items.id");
            $this->subQuery->innerJoin("{{%museumplus_objectgroups}} objectgroups", "itemsObjectgroups.objectGroupId = objectgroups.id");

            $this->subQuery->andWhere("objectgroups.title LIKE '".addslashes($this->objectGroup)."'");
        }

        if ($this->objectGroupId) {
            $this->subQuery->innerJoin('museumplus_items_objectgroups', '[[museumplus_items.id]] = [[museumplus_items_objectgroups.itemId]]');

            $this->subQuery->andWhere(Db::parseParam('museumplus_items_objectgroups.objectGroupId', $this->objectGroupId));
        }
        $this->subQuery->groupBy('museumplus_items.id');
/*
        echo $this->subQuery->getRawSql();
        exit();*/

        return parent::beforePrepare();
    }
}
