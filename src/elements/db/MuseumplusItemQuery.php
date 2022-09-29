<?php
namespace furbo\museumplusforcraftcms\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use furbo\museumplusforcraftcms\elements\MuseumplusItem;

class MuseumplusItemQuery extends ElementQuery
{
    public $collectionId;

    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the items table
        $this->joinElementTable('museumplus_items');

        // select the collection id column
        $this->query->select([
            'museumplus_items.collectionId',
            'museumplus_items.data'
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_items.collectionId', $this->collectionId));
        }

        return parent::beforePrepare();
    }
}
