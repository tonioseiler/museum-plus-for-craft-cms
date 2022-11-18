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
        }

        if (!$this->db->tableExists('{{%museumplus_objectgroups}}')) {
            $this->createTable('{{%museumplus_objectgroups}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->longText()->null(),
                'collectionId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);
        }

        if (!$this->db->tableExists('{{%museumplus_items_objectgroups}}')) {
            $this->createTable('{{%museumplus_items_objectgroups}}', [
                'id' => $this->integer()->notNull(),
                'itemId' => $this->integer()->notNull(),
                'objectGroupId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_objectgroups}}', 'objectGroupId'),
                '{{%museumplus_items_objectgroups}}',
                'objectGroupId',
                '{{%museumplus_objectgroups}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_items_objectgroups}}', 'itemId'),
                '{{%museumplus_items_objectgroups}}',
                'itemId',
                '{{%museumplus_items}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%museumplus_literature}}')) {
            $this->createTable('{{%museumplus_literature}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->longText()->null(),
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
        };

        if (!$this->db->tableExists('{{%museumplus_people}}')) {
            $this->createTable('{{%museumplus_people}}', [
                'id' => $this->integer()->notNull(),
                'data' => $this->longText()->null(),
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
        //$this->dropTable('{{%museumplus_items}}');
        //TODO: but later
        return true;
    }
}
