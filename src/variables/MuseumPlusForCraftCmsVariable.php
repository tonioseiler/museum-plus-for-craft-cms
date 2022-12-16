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

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;

use Craft;

/**
 * MuseumPlus for CraftCMS Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.museumPlusForCraftCms }}).
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
     *     {{ craft.museumPlusForCraftCms.cpTitle }} or
     *     {{ craft.museumPlusForCraftCms.cpTitle(twigValue) }}
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

    public function getItemsByTag($tagId) {
        //TODO: implement
        return MuseumPlusForCraftCms::$plugin->collection->getItemsByTag($tagId);
    }

    public function getItemById($id) {
        //TODO: implement
        return MuseumPlusForCraftCms::$plugin->collection->getItemById($id);
    }

    public function getItemsByIds($ids) {
        //TODO: implement
        return MuseumPlusForCraftCms::$plugin->collection->getItemsById($ids);
    }

    public function searchItems($params, $limit = 10, $offset = 0) {
        return MuseumPlusForCraftCms::$plugin->collection->searchItems($params, $limit, $offset);
    }
}
