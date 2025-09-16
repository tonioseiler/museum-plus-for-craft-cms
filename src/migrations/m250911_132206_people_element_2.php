<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\DateTimeHelper;
use furbo\museumplusforcraftcms\elements\MuseumPlusPerson;

/**
 * m250911_132206_people_element_2 migration.
 */
class m250911_132206_people_element_2 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        //1. Delete the title column
        //2.Delete the old People Records
        //3. Create the Foreign Key

        $todayMidnight = DateTimeHelper::toDateTime(new \DateTime('today midnight'));

        $firstPerson = MuseumPlusPerson::find()->orderBy('id')->dateCreated(">= {$todayMidnight->format('Y-m-d H:i:s')}")->one();
        Craft::$app->db->createCommand()
           -> delete('{{%museumplus_people}}', 'id < :id', [':id' => $firstPerson->id])
            ->execute();

        $this->dropColumn('{{%museumplus_people}}', 'title');

        $this->addForeignKey(
               $this->db->getForeignKeyName(),
               '{{%museumplus_people}}',
               'id',
               '{{%elements}}',
               'id',
               'CASCADE',
               null
           );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250911_132206_people_element_2 cannot be reverted.\n";
        return false;
    }
}
