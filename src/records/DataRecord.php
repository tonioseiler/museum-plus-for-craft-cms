<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use Craft;
use craft\db\ActiveRecord;

/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

abstract class DataRecord extends ActiveRecord
{

    public function getDataAttributes() {
        $data = json_decode($this->data, true);
        return $data;
    }

    public function getDataAttribute($name) {
        $attributes = $this->getDataAttributes();
        if (!isset($attributes[$name]))
            return null;
        return $attributes[$name];
    }

}
