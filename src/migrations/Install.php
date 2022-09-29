<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_items}}')) {
            // create the items table
            $this->createTable('{{%museumplus_items}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            // give it a foreign key to the elements table
            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                '{{%museumplus_items}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );


            $this->createTable('{{%museumplus_literature}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->text()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                '{{%museumplus_literature}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );

            $this->createTable('{{%museumplus_people}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->text()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName(),
                '{{%museumplus_people}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );



        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTable('{{%museumplus_items}}');
        return true;
    }
}
