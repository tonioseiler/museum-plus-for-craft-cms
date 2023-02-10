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

    protected $parsedData = null;

    public function getDataAttributes() {
        if (empty($this->parsedData)) {
            $this->parsedData = json_decode($this->data, true);
        }
        return $this->parsedData;
    }

    public function getDataAttribute($name) {
        $attributes = $this->getDataAttributes();
        if (!isset($attributes[$name]))
            return null;
        return $attributes[$name];
    }

}
