<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

class MuseumPlusItemQuery extends ElementQuery
{
    public $collectionId;
    public $assetId;
    public $objectGroupId;

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

    protected function beforePrepare(): bool
    {
        // join in the items table
        $this->joinElementTable('museumplus_items');
        //$this->query->innerJoin(['multiMedia' => '{{%museumplus_items_assets}}'], '[[multiMedia.itemId]] = [[museumplus_items.id]]');

        // select the collection id column
        $this->query->select([
            'museumplus_items.collectionId',
            'museumplus_items.data',
            'museumplus_items.assetId',
            //'multiMedia.assetId as multiMedia',
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.collectionId', $this->collectionId));
        }

        if ($this->assetId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.assetId', $this->assetId));
        }

        if ($this->objectGroupId) {
            $this->subQuery->innerJoin('museumplus_items_objectgroups', '[[museumplus_items.id]] = [[museumplus_items_objectgroups.itemId]]');
            $this->subQuery->andWhere(Db::parseParam('museumplus_items_objectgroups.objectGroupId', $this->objectGroupId));
        }

        return parent::beforePrepare();
    }
}
