<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221101_131444_add_item_referenced_data migration.
 */
class m221101_131444_add_item_referenced_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221101_131444_add_item_referenced_data cannot be reverted.\n";
        return false;
    }
}
