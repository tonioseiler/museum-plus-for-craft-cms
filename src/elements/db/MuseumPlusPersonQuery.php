<?php

namespace furbo\museumplusforcraftcms\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Museum Plus Person query
 */
class MuseumPlusPersonQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // todo: join the `museumpluspeople` table
        // $this->joinElementTable('museumpluspeople');

        // todo: apply any custom query params
        // ...

        return parent::beforePrepare();
    }
}
