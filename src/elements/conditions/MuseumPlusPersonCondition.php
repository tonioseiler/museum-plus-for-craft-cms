<?php

namespace furbo\museumplusforcraftcms\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Museum Plus Person condition
 */
class MuseumPlusPersonCondition extends ElementCondition
{
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            // ...
        ]);
    }
}
