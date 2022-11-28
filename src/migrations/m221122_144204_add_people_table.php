<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221122_144204_add_people_table migration.
 */
class m221122_144204_add_people_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {

        if (!$this->db->tableExists('{{%museumplus_people}}')) {
            $this->createTable('{{%museumplus_people}}', [
                'id' => $this->primaryKey(),
                'title' => $this->text(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }


        if (!$this->db->tableExists('{{%museumplus_items_people}}')) {
            $this->createTable('{{%museumplus_items_people}}', [
                'id' => $this->primaryKey(),
                'itemId' => $this->integer()->notNull(),
                'personId' => $this->integer()->notNull(),
                'type' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_people}}', 'personId'),
                '{{%museumplus_items_people}}',
                'personId',
                '{{%museumplus_people}}',
                'id'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_people}}', 'itemId'),
                '{{%museumplus_items_people}}',
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
        echo "m221122_144204_add_people_table cannot be reverted.\n";
        return false;
    }
}
