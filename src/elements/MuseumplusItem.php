<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\elements;

use furbo\museumplusforcraftcms\MuseumplusForCraftcms;
use furbo\museumplusforcraftcms\elements\db\MuseumplusItemQuery;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use craft\models\TagGroup;

/**
 *  Element MuseumplusItem
 *
 *
 * @author    Furbo GmbH
 * @package   MuseumplusForCraftcms
 * @since     1.0.0
 */
class MuseumplusItem  extends Element
{
    // Public Properties
    // =========================================================================

    public $data = null;

    public $collectionId = null;

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', '');
    }

    /**
     * Returns whether elements of this type will be storing any data in the `content`
     * table (tiles or custom fields).
     *
     * @return bool Whether elements of this type will be storing any data in the `content` table.
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * Returns whether elements of this type have traditional titles.
     *
     * @return bool Whether elements of this type have traditional titles.
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * Returns whether elements of this type have statuses.
     *
     * If this returns `true`, the element index template will show a Status menu
     * by default, and your elements will get status indicator icons next to them.
     *
     * Use [[statuses()]] to customize which statuses the elements might have.
     *
     * @return bool Whether elements of this type have statuses.
     * @see statuses()
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     *
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
           [
               'key' => '*',
               'label' => Craft::t('app', 'All'),
               'criteria' => [],
               'hasThumbs' => true
           ]
        ];
        return $sources;
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Returns whether the current user can edit the element.
     *
     * @return bool
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayout|null
     */
    public function getFieldLayout(): FieldLayout
    {
        return \Craft::$app->fields->getLayoutByType(MuseumplusItem::class);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * Returns the HTML for the element’s editor HUD.
     *
     * @return string The HTML for the editor HUD
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    public function getCpEditUrl(): ?string
    {
        return 'collection/'.$this->id;
    }


    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     *
     * @return bool Whether the element should be saved
     */
    public function beforeSave(bool $isNew): bool
    {
        return true;
    }

    /**
     * Performs actions after an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     *
     * @return void
     */
    public function afterSave(bool $isNew): void
    {

        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%museumplus_items}}', [
                    'id' => $this->id,
                    'collectionId' => $this->collectionId,
                    'data' => $this->data
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%museumplus_items}}', [
                    'data' => $this->data,
                    'collectionId' => $this->collectionId,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);

    }

    /**
     * Performs actions before an element is deleted.
     *
     * @return bool Whether the element should be deleted
     */
    public function beforeDelete(): bool
    {
        return true;
    }

    /**
     * Performs actions after an element is deleted.
     *
     * @return void
     */
    public function afterDelete(): void
    {
    }

    /**
     * Performs actions before an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     *
     * @return bool Whether the element should be moved within the structure
     */
    public function beforeMoveInStructure(int $structureId): bool
    {
        return true;
    }

    /**
     * Performs actions after an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     *
     * @return void
     */
    public function afterMoveInStructure(int $structureId): void
    {
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumplusItemQuery(static::class);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => \Craft::t('app', 'Title')
        ];
    }

    protected static function defineSortOptions(): array
{
    return [
        'title' => \Craft::t('app', 'Title')
    ];
}


}
