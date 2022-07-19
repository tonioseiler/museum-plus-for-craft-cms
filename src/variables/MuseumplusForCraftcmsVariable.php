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

use furbo\museumplusforcraftcms\MuseumplusForCraftCms;

use Craft;

/**
 * MuseumPlus for CraftCMS Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.museumplusForCraftcms }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Furbo GmbH
 * @package   MuseumplusForCraftcms
 * @since     1.0.0
 */
class MuseumplusForCraftcmsVariable
{
    // Public Methods
    // =========================================================================

    /**
     *
     *     {{ craft.museumplusForCraftcms.cpTitle }} or
     *     {{ craft.museumplusForCraftcms.cpTitle(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function cpTitle($optional = null)
    {
        $settings = MuseumplusForCraftCms::$plugin->getSettings();
        return $settings['cpTitle'];
    }

    public function getObjectGroups() {
        try {
            $objectGroups = MuseumplusForCraftcms::$plugin->collection->getObjectGroups();
            $ret = [];
            foreach ($objectGroups as $og) {
                $title = $og->OgrNameTxt;
                if (strlen($title)) {
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
            $exhibitions = MuseumplusForCraftcms::$plugin->collection->getExhibitions();
            $ret = [];
            foreach ($exhibitions as $ex) {
                $title = $ex->ExhExhibitionTitleVrt;
                if (strlen($title)) {
                    $title = substr($title, 0, 60). '...';
                }

                $ret[$ex->id] = $title;
            }
            return $ret;
        } catch (\Exception $e) {
            return [];
        }
    }
}
