<?php

namespace furbo\museumplusforcraftcms\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

/**
 * Museum Plus Person query
 */
class MuseumPlusPersonQuery extends ElementQuery
{

    public $collectionId = null;

    public function collectionId($value)
    {
        $this->collectionId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('museumplus_people');

        $this->query->select([
            'museumplus_people.collectionId',
            'museumplus_people.data'
        ]);

        if ($this->collectionId) {
            $this->subQuery->andWhere(Db::parseParam('museumplus_people.collectionId', $this->collectionId));
        }

        return parent::beforePrepare();
    }
}
