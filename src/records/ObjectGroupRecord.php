<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace furbo\museumplusforcraftcms\records;

use craft\db\ActiveRecord;

use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

/*
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */

class ObjectGroupRecord extends ActiveRecord
{

    public static function tableName(): string
    {
        return '{{%museumplus_objectgroups}}';
    }

}
