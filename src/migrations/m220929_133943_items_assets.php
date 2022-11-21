<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220929_133943_items_assets migration.
 */
class m220929_133943_items_assets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_items_assets}}')) {
            $this->createTable('{{%museumplus_items_assets}}', [
                'id' => $this->integer()->notNull(),
                'itemId' => $this->integer()->notNull(),
                'assetId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_assets}}', 'assetId'),
                '{{%museumplus_items_assets}}',
                'assetId',
                '{{%assets}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_assets}}', 'fileId'),
                '{{%museumplus_items_assets}}',
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
        echo "m220929_133943_items_assets cannot be reverted.\n";
        return false;
    }
}
