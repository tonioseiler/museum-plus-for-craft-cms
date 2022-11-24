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

use craft\db\Query;
use craft\helpers\Cp;

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusItemQuery;
use furbo\museumplusforcraftcms\records\ObjectGroupRecord;
use furbo\museumplusforcraftcms\records\MuseumPlusItemRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use craft\models\TagGroup;

/**
 *  Element MuseumPlusItem
 *
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
class MuseumPlusItem  extends Element
{

    // Public Properties
    // =========================================================================

    public $data = null;

    public $collectionId = null;

    public $assetId = null;

    public $multiMedia = [];

    private $record = null;

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'Item');
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

    public static function hasUris(): bool
    {
        return true;
    }


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
               'hasThumbs' => false
           ],
           [
               'heading' => 'Object Groups',
           ],
        ];

        $objectGroups = MuseumPlusForCraftCms::$plugin->collection->getAllObjectGroups();

        foreach ($objectGroups as $objectGroup) {
            $sources[] = [
                'key' => 'objectGroup:' . $objectGroup->id,
                'label' => $objectGroup->title,
                'criteria' => ['objectGroupId' => $objectGroup->id]
            ];
        }

        return $sources;
    }

    // Public Methods
    // =========================================================================
    /*public function rules(): array
    {
        return [];
    }*/

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
        return \Craft::$app->fields->getLayoutByType(MuseumPlusItem::class);
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
        return 'museum-plus-for-craft-cms/collection/'.$this->id;
    }


    public static function hasStatuses(): bool
    {
        return true;
    }

    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return '–';
        }

        return null;
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
            $itemRecord = new MuseumPlusItemRecord();
            $itemRecord->id = $this->id;
        }
        else {
            $itemRecord = MuseumPlusItemRecord::findOne($this->id);
        }

        $itemRecord->collectionId = $this->collectionId;
        $itemRecord->data = $this->data;
        $itemRecord->assetId = $this->assetId;

        $itemRecord->save(false);

        parent::afterSave($isNew);

    }

    public function getMultimedia()
    {
        $assets = [];
        $multiMedia = (new Query())
            ->select(['assetId'])
            ->from('{{%museumplus_items_assets}}')
            ->where(['itemId' => $this->id])
            ->all();

        foreach($multiMedia as $asset){
            $assets[] = Craft::$app->assets->getAssetById($asset['assetId']);
        }

        return $assets;
    }

    public function getAttachment()
    {
        if($this->assetId){
            return Craft::$app->assets->getAssetById($this->assetId);
        }
        return false;
    }

    public function addMultimedia($assetId){
        Craft::$app->db->createCommand()
            ->insert('{{%museumplus_items_assets}}', [
                'itemId' => $this->id,
                'assetId' => $assetId,
            ])->execute();
    }

    public function deleteMultimedia($assetId){
        Craft::$app->db->createCommand()
            ->delete('{{%museumplus_items_assets}}', ['itemId' => $this->id, 'assetId' => $assetId])->execute();
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
        return new MuseumPlusItemQuery(static::class);
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'assetId':
                if($this->assetId) {
                    $asset = Craft::$app->getAssets()->getAssetById($this->assetId);
                    if($asset){
                        return Cp::elementPreviewHtml([$asset], Cp::ELEMENT_SIZE_SMALL, false, true, true, false);
                    }
                    return $this->assetId;
                }
                return '-';
            case 'multimedia':
                $assets = $this->getMultimedia();
                if(count($assets)) {
                    return Cp::elementPreviewHtml($assets, Cp::ELEMENT_SIZE_SMALL, false, true, true, false);
                }
                return '-';
        }
        return parent::tableAttributeHtml($attribute);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'collectionId' => 'MuseumPlus Id',
            'assetId' => 'Main Image',
            'multimedia' => 'Media',
            'id' => ['label' => Craft::t('app', 'ID')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['collectionId', 'assetId', 'multimedia'];
    }


    protected static function defineSortOptions(): array
    {
        return [
            'title' => \Craft::t('app', 'Title'),
            'collectionId' => 'MuseumPlus Id'
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['data', 'collectionId'];
    }


    public function syncMultimediaRelations($assetIds) {
        $this->getRecord()->syncMultimediaRelations($assetIds);
    }

    public function syncPeopleRelations($peopleIds, $type) {
        $this->getRecord()->syncPeopleRelations($peopleIds, $type);
    }

    public function syncOwnershipRelations($ownershipIds) {
        $this->getRecord()->syncOwnershipRelations($ownershipIds);
    }

    public function syncLiteratureRelations($literureIds) {
        $this->getRecord()->syncLiteratureRelations($literureIds);
    }

    public function syncItemRelations($itemIds) {
        $this->getRecord()->syncItemRelations($itemIds);
    }

    public function getObjectGroups() {
        $rec = $this->getRecord();
        return $rec->getObjectGroups();
    }

    public function getOwnerships() {
        $rec = $this->getRecord();
        return $rec->getOwnerships();
    }

    public function getAssociationPeople() {
        $rec = $this->getRecord();
        return $rec->getAssociationPeople();
    }

    public function getOwnerPeople() {
        $rec = $this->getRecord();
        return $rec->getOwnerPeople();
    }

    public function getAdministrationPeople() {
        $rec = $this->getRecord();
        return $rec->getAdministrationPeople();
    }

    public function getRelatedItems() {
        $rec = $this->getRecord();
        return $rec->getRelatedItems();
    }

    public function getRecord() {
        if (empty($this->record)) {
            $this->record = MuseumPlusItemRecord::findOne($this->id);
        }
        return $this->record;
    }

    public function getDating() {
        $rec = $this->getRecord();
        return $rec->getRepeatableGroupValues('ObjDateGrp', 'DateTxt');
    }

    public function getGeographicReferences() {
        $rec = $this->getRecord();
        return $rec->getGeographicReferences();
    }

    public function getMaterial() {
        $rec = $this->getRecord();
        return $rec->getMaterial();
    }

    public function getDimensions() {
        $rec = $this->getRecord();
        return $rec->getDimensions();
    }

    public function getProvenance() {
        $rec = $this->getRecord();
        return $rec->getProvenance();
    }

    public function getCreditLine() {
        $rec = $this->getRecord();
        return $rec->getCreditLine();
    }

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
