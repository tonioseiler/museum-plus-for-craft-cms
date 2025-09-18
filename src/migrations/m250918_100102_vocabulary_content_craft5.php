<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;

/**
 * m250918_100102_vocabulary_content_craft5 migration.
 */
class m250918_100102_vocabulary_content_craft5 extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->updateElements(
            (new Query())
                ->from('{{%museumplus_vocabulary}}'),
            Craft::$app->getFields()->getLayoutByType(MuseumPlusVocabulary::class),
        );

        Craft::$app->getCache()->flush();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250918_100102_vocabulary_content_craft5 cannot be reverted.\n";
        return false;
    }
}
