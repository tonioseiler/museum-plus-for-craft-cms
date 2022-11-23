<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\traits;

/**
 *  Trait HasAccessibleData
 *
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
trait HasAccessibleData
{

    public function __get($name)
    {
        $data = json_decode($this->data, true);
        if ($name == 'attributes') {
            return $data;
        } else if (array_key_exists($name, $data)) {
            return $data[$name];
        } else {
            return parent::__get($name);
        }
    }

}
