<?php

namespace furbo\museumplusforcraftcms\jobs;

use Craft;
use craft\queue\BaseJob;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

/**
 * Job to update a MuseumPlusItem.
 */
class UpdateItemJob extends BaseJob
{
    public int $collectionId;

    public function execute($queue): void
    {
        $logger = MuseumPlusForCraftCms::getLogger();


        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $this->collectionId])
            ->one();

        $item = MuseumPlusItem::find()
            ->where(['collectionId' => $this->collectionId])
            ->one();

        $isNewItem = !$item;
        $message = $isNewItem
            ? "Creating new MuseumPlusItem (ID: {$this->collectionId})."
            : "Updating MuseumPlusItem (ID: {$this->collectionId}).";

        Craft::info($message, 'museumplus');
        $logger->info($message);

        try {
            $item->title = $item->getDataAttribute('ObjObjectTitleVrt');
            if (Craft::$app->elements->saveElement($item)) {
                //Craft::info("Updated MuseumPlusItem {$item->id} successfully.", __METHOD__);
                $message = "Successfully updated MuseumPlusItem ID {$item->id}.";
                Craft::info($message, 'museumplus');
                $logger->info($message);
            } else {
                //Craft::error("Failed to update MuseumPlusItem {$item->id}.", __METHOD__);
                $message = "Failed to update MuseumPlusItem ID {$item->id}.";
                Craft::error($message, 'museumplus');
                $logger->error($message);
            }
        } catch (\Throwable $e) {
            //Craft::error("Error updating MuseumPlusItem: " . $e->getMessage(), __METHOD__);
            $message = "Error updating MuseumPlusItem: " . $e->getMessage();
            Craft::error($message, 'museumplus');
            $logger->error($message);
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating MuseumPlusItem');
    }
}