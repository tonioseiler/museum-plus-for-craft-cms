<?php

namespace Furbo\MuseumPlus\Events;

use yii\base\Event;

class ItemUpdatedFromMuseumPlusEvent extends Event
{
    public $item;

    public $isNewItem;

}