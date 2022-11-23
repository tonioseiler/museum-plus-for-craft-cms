<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221123_002240_add_literture_table migration.
 */
class m221123_002240_add_literture_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_literature}}')) {
            $this->createTable('{{%museumplus_literature}}', [
                'id' => $this->primaryKey(),
                'title' => $this->string(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_literature}}')) {
            $this->createTable('{{%museumplus_items_literature}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'literatureId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_literature}}', 'literatureId'),
                '{{%museumplus_items_literature}}',
                'literatureId',
                '{{%museumplus_literature}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_literature}}', 'itemId'),
                '{{%museumplus_items_literature}}',
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
        echo "m221123_002240_add_literture_table cannot be reverted.\n";
        return false;
    }
}
