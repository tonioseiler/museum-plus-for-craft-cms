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
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;
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
        $this->logger->info('---- Updating item parent/child relations START ---------');


        //reset parent ids
        $itemRecords = MuseumPlusItemRecord::find()
            ->where(['>', 'parentId', '0'])
            ->all();
        $this->logger->info('Resetting all parent ids');
        foreach ($itemRecords as $item) {
            $item->parentId = 0;
            $item->save();

        }
        $this->logger->info('Resetting all parent ids DONE');
        $this->logger->info('Set the relations again');
        //set the relations again
        $itemRecords = MuseumPlusItemRecord::find()->all();
        $progressIndex = 0;

        try {
            foreach ($itemRecords as $item) {
                $this->logger->info('Element ' . $item->id. ' START');
                $progressIndex++;
                $progressPercent = floatval($progressIndex) / floatval(count($itemRecords));
                $this->setProgress($this->queue, $progressPercent, 'Settings relations '.$item->id);
                $moduleRefs = $item->getDataAttribute('moduleReferences');
                if (isset($moduleRefs['ObjObjectPartRef'])) {
                    $parts = $moduleRefs['ObjObjectPartRef']['items'];
                    foreach ($parts as $part) {
                        //$this->logger->info('Evaluating part');

                        $child = MuseumPlusItemRecord::find()
                            ->where(['collectionId' => $part['id']])
                            ->one();
                        if ($child) {
                            //$this->logger->info('Relation set');
                            $child->parentId = $item->collectionId;
                            //$child->save();
                        } else {
                            //$this->logger->info('Skipping');
                        }
                    }
                } else {
                    //$this->logger->info('Skipping');
                }
                $this->logger->info('Element ' . $item->id. ' END');

            }
        } catch (\Exception $e) {
            throw new  \Exception('Something went wrong: ' . $e->getMessage());
        }

        $this->logger->info('---- Updating item parent/child relations END ---------');
    }
}