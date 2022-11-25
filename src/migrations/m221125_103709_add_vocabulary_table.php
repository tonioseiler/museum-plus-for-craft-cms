<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221125_103709_add_vocabulary_table migration.
 */
class m221125_103709_add_vocabulary_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_vocabulary}}')) {
            $this->createTable('{{%museumplus_vocabulary}}', [
                'id' => $this->primaryKey(),
                'parentId' => $this->integer()->notNull()->defaultValue(0),
                'title' => $this->string(),
                'language' => $this->string()->null(),
                'type' => $this->string(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_vocabulary}')) {
            $this->createTable('{{%museumplus_items_vocabulary}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'vocabularyId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_vocabulary}}', 'vocabularyId'),
                '{{%museumplus_items_vocabulary}}',
                'vocabularyId',
                '{{%museumplus_vocabulary}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_vocabulary}}', 'itemId'),
                '{{%museumplus_items_vocabulary}}',
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
        echo "m221125_103709_add_vocabulary_table cannot be reverted.\n";
        return false;
    }
}
