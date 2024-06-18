<?php

namespace furbo\museumplusforcraftcms\events;

use yii\base\Event;

class ItemUpdatedFromMuseumPlusEvent extends Event
{
    public $item;

    public $isNewItem;

}