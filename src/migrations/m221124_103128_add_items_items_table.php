<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221124_103128_add_items_items_table migration.
 */
class m221124_103128_add_items_items_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_items_items}}')) {
            $this->createTable('{{%museumplus_items_items}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'relatedItemId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_items}}', 'relatedItemId'),
                '{{%museumplus_items_items}}',
                'relatedItemId',
                '{{%museumplus_items}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_items}}', 'itemId'),
                '{{%museumplus_items_items}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221124_103128_add_items_items_table cannot be reverted.\n";
        return false;
    }
}
