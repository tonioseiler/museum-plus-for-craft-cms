<?php

namespace furbo\museumplusforcraftcms\jobs;

use Craft;
use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\models\VolumeFolder;
use craft\queue\BaseJob;
use craft\queue\jobs\UpdateSearchIndex;
use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\events\ItemUpdatedFromMuseumPlusEvent;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\MuseumPlusItem;
use furbo\museumplusforcraftcms\records\LiteratureRecord;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;

/**
 * Job to update a MuseumPlusItem.
 */
class UpdateItemParentChildRelationsJob extends BaseJob
{
    private $settings;
    private $museumPlus;
    private $queue = null;
    private $logger = null;

    public function execute($queue): void
    {
        $this->logger = MuseumPlusForCraftCms::getLogger();
        $this->queue = $queue;
        $this->settings = MuseumPlusForCraftCms::$plugin->getSettings();
        $this->museumPlus = MuseumPlusForCraftCms::$plugin->museumPlus;
        $this->logger->info('---- Deleting removed items START ---------');

        $objectIds = [];
        foreach ($this->settings['objectGroups'] as $objectGroupId) {
            $objects = $this->museumPlus->getObjectsByObjectGroup($objectGroupId, ['__id', '__lastModifiedUser', '__lastModified']);
            foreach ($objects as $o) {
                $objectIds[$o->id] = $o->id;
            }
        }
        $itemIds = MuseumPlusItem::find()->ids();
        $this->logger->info('Number of items from MuseumPlus: ' . count($objectIds));
        $this->logger->info('Number of items from db: ' . count($itemIds));
        if (floatval(count($objectIds)) / floatval(count($itemIds)) < 0.9) {
            $this->logger->info('Less than 90% came from the MuseumPlus server Skipping delete.');
            throw new  \Exception('Less than 90% came from the MuseumPlus server Skipping delete.');
        }
        $progressIndex = 0;
        foreach ($itemIds as $itemId) {
            $item = MuseumPlusItem::find()
                ->id($itemId)
                ->one();
            $progressIndex++;
            $progressPercent = $progressIndex / count($itemIds);
            $this->setProgress($this->queue, $progressPercent, 'Checking item: ' . $item->id);
            if (!isset($objectIds[$item->collectionId])) {
                $success = Craft::$app->elements->deleteElement($item);
                $this->logger->info('Item deleted: ' . $item->title . ' (' . $item->id . ')');
            }
        }
        $this->logger->info('---- Deleting removed items END ---------');
    }
}