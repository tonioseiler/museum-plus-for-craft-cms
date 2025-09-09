<?php

namespace furbo\museumplusforcraftcms\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;

/**
 * m250909_075714_content_to_craft5 migration.
 */
class m250909_075714_content_to_craft5 extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...
        $this->updateElements(
            (new Query())
                ->from('{{%museumplus_items}}'),
            Craft::$app->getFields()->getLayoutByType(MuseumPlusItem::class),
        );

        $this->updateElements(
            (new Query())->from('{{%museumplus_items_vocabulary}}'),
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
        echo "m250909_075714_content_to_craft5 cannot be reverted.\n";
        return false;
    }
}
