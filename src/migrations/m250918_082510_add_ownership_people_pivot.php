<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250918_082510_add_ownership_people_pivot migration.
 */
class m250918_082510_add_ownership_people_pivot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%museumplus_ownerships_people}}')) {
            $this->createTable('{{%museumplus_ownerships_people}}', [
                'id' => $this->primaryKey(),
                'ownershipId' => $this->integer()->notNull(),
                'personId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'sort' => $this->integer()->defaultValue(0)
            ]);

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_ownerships_people}}', 'personId'),
                '{{%museumplus_ownerships_people}}',
                'personId',
                '{{%museumplus_people}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%museumplus_ownerships_people}}', 'ownershipId'),
                '{{%museumplus_ownerships_people}}',
                'ownershipId',
                '{{%museumplus_ownerships}}',
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
        echo "m250918_082510_add_ownership_people_pivot cannot be reverted.\n";
        return false;
    }
}
