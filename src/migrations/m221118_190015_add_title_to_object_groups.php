<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221118_190015_add_title_to_object_groups migration.
 */
class m221118_190015_add_title_to_object_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_objectgroups}}', 'title')) {
            $this->addColumn('{{%museumplus_objectgroups}}', 'title', $this->string());
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221118_190015_add_title_to_object_groups cannot be reverted.\n";
        return false;
    }
}
