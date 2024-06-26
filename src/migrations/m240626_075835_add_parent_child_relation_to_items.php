<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240626_075835_add_parent_child_relation_to_items migration.
 */
class m240626_075835_add_parent_child_relation_to_items extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%museumplus_items}}', 'parentId')) {y
            $this->addColumn('{{%museumplus_items}}', 'parentId', $this->integer()->notNull()->defaultValue(0));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240626_075835_add_parent_child_relation_to_items cannot be reverted.\n";
        return false;
    }
}
