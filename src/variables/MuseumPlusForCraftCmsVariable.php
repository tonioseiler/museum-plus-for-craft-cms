<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\variables;

use furbo\museumplusforcraftcms\elements\MuseumPlusVocabulary;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;

use Craft;

/**
 * MuseumPlus for CraftCMS Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.museumPlus }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
class MuseumPlusForCraftCmsVariable
{
    // Public Methods
    // =========================================================================

    /**
     *
     *     {{ craft.museumPlus.cpTitle }} or
     *     {{ craft.museumPlus.cpTitle(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function cpTitle($optional = null)
    {
        $settings = MuseumPlusForCraftCms::$plugin->getSettings();
        return $settings['cpTitle'];
    }

    public function getObjectGroups() {
        try {
            $objectGroups = MuseumPlusForCraftCms::$plugin->museumPlus->getObjectGroups();
            $ret = [];
            foreach ($objectGroups as $og) {
                $title = $og->OgrNameTxt;
                if (strlen($title) > 60) {
                    $title = substr($title, 0, 60). '...';
                }
                $ret[$og->id] = $title;
            }
            return $ret;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all exhibitions
     * @return array
     * 
     * untested, probably does not work
     */
    public function getExhibitions() {
        try {
            $exhibitions = MuseumPlusForCraftCms::$plugin->museumPlus->getExhibitions();
            $ret = [];
            foreach ($exhibitions as $ex) {
                $title = $ex->ExhExhibitionTitleVrt;
                if (strlen($title) > 60) {
                    $title = substr($title, 0, 60). '...';
                }

                $ret[$ex->id] = $title;
            }
            return $ret;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getVolumes() {
        $volumes = Craft::$app->volumes->allVolumes;
        $ret = [];
        foreach ($volumes as $v) {
            $ret[$v->id] = $v->name;
        }
        return $ret;
    }

    public function getItemById($id) {
        return MuseumPlusForCraftCms::$plugin->collection->getItemById($id);
    }

    public function getItemsByIds($ids,$limit = 10, $offset = 0) {
        return MuseumPlusForCraftCms::$plugin->collection->getItemsByIds($ids,$limit, $offset);
    }

    public function getVocabularies($type = null) { 
        return MuseumPlusForCraftCms::$plugin->vocabulary->getVocabularies($type);
    }

    public function getVocabularyById($id) {
        return MuseumPlusForCraftCms::$plugin->vocabulary->getElementById($id);
    }

    public function getAllCountries($type = null, $depth = null) {
        return MuseumPlusForCraftCms::$plugin->vocabulary->getAllCountries($type,$depth);
    }

    public function getAllPeople() {
        return MuseumPlusForCraftCms::$plugin->collection->getAllPeople();
    }

    public function getPeopleById($id) {
        return MuseumPlusForCraftCms::$plugin->collection->getPeopleById($id);
    }

    public function getObjectGroupById($id) {
        return MuseumPlusForCraftCms::$plugin->collection->getObjectGroupById($id);
    }

    public function getAllObjectGroups() {
        return MuseumPlusForCraftCms::$plugin->collection->getAllObjectGroups();
    }

    public function searchItems($params, $limit = 10, $offset = 0) {
        return MuseumPlusForCraftCms::$plugin->collection->searchItems($params, $limit, $offset);
    }

    public function getAllClassifications() {
        return $this->getVocabularies('ObjClassificationVgr');
    }

    public function getAllPlaces() {
        return $this->getVocabularies('GenPlaceVgr');
    }

    public function getAllKeywords() {
        return $this->getVocabularies('ObjKeyWordVgr');
    }

    public function getBookmarks($limit = 10, $offset = 0) {
        return MuseumPlusForCraftCms::$plugin->collection->getBookmarks($limit, $offset);
    }

    public function isBookmarked($id) {
        return MuseumPlusForCraftCms::$plugin->collection->isBookmarked($id);
    }

}
