<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230327_122310_add_sort_items migration.
 */
class m230327_122310_add_sort_items extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'sort')) {
            $this->addColumn('{{%museumplus_items}}', 'sort', $this->string());
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230327_122310_add_sort_items cannot be reverted.\n";
        return false;
    }
}
