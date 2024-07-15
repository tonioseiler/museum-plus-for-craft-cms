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

    // @override
    public function getChildren() {
        return null;
    }

    // @override
    public function getParent():DataRecord|null
    {
        return null;
    }

    /*
    public function getDescendants() {
        $descendants = $this->getChildren();
        if (!empty($this->getChildren())) {
            foreach ($this->getChildren()->all() as $child) {
                $descendants = array_merge($descendants, $child->getDescendants());
            }
        }
        return $descendants;
    }
    */

    public function getDescendants() {
        $descendants = $this->getChildren()->all();  // Convert the ActiveQuery result to an array immediately.
        if (!empty($descendants)) {  // Check if descendants is not empty.
            foreach ($descendants as $child) {
                $descendants = array_merge($descendants, $child->getDescendants());  // Merge with returned descendants.
            }
        }
        return $descendants;
    }
    
    

    public function getParents()
    {
        $parent = $this->getParent();
        if ($parent) {
            //return [$parent] + $parent->getParents();
            return array_merge([$parent], $parent->getParents());
        } else {
            return [];
        }
    }

    public function getPath()
    {
        return array_filter([$this] + $this->getParents());
    }

}
