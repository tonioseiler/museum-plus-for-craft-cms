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
class DeleteRemovedItemsJob extends BaseJob
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

        $items = MuseumPlusItem::find()->collectionId(['not', $objectIds]);

        $progressIndex = 0;
        foreach ($items as $item){
            $progressIndex++;
            $progressPercent = floatval($progressIndex) / floatval(count($items));
            $this->setProgress($this->queue, $progressPercent, 'Deleting item: ' . $item->id);
            if(Craft::$app->elements->deleteElement($item)){
                $this->logger->info('Item deleted: ' . $item->title . ' (' . $item->id . ')');
            }

        }

        $this->setProgress($this->queue, 1);

        $this->logger->info('---- Deleting removed items END ---------');
    }
}