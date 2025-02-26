<?php

namespace furbo\museumplusforcraftcms\jobs;

use Craft;
use craft\queue\BaseJob;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

/**
 * Job to update a MuseumPlusItem.
 */
class UpdateItemJob extends BaseJob
{
    public int $collectionId;

    public function execute($queue): void
    {
        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $this->collectionId])
            ->one();

        if (!$item) {
            Craft::error("MuseumPlusItem with collectionId {$this->collectionId} not found.", __METHOD__);
            return;
        }

        try {
            $item->title = $item->getDataAttribute('ObjObjectTitleVrt');
            if (Craft::$app->elements->saveElement($item)) {
                Craft::info("Updated MuseumPlusItem {$item->id} successfully.", __METHOD__);
            } else {
                Craft::error("Failed to update MuseumPlusItem {$item->id}.", __METHOD__);
            }
        } catch (\Throwable $e) {
            Craft::error("Error updating MuseumPlusItem: " . $e->getMessage(), __METHOD__);
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating MuseumPlusItem');
    }
}