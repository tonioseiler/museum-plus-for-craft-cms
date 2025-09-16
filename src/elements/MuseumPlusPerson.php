<?php

namespace furbo\museumplusforcraftcms\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use furbo\museumplusforcraftcms\elements\conditions\MuseumPlusPersonCondition;
use furbo\museumplusforcraftcms\elements\db\MuseumPlusPersonQuery;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use furbo\museumplusforcraftcms\records\OwnershipRecord;
use furbo\museumplusforcraftcms\records\PersonRecord;
use yii\web\Response;

/**
 * Museum Plus Person element type
 */
class MuseumPlusPerson extends Element
{

    public $data = null;

    public $collectionId = null;

    private $record = null;


    public static function displayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'Person');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'person');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'People');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('museum-plus-for-craft-cms', 'people');
    }

    public static function refHandle(): ?string
    {
        return 'person';
    }

    public static function hasContent(): bool
    {
        return true;
    }

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

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function getIsEditable(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new MuseumPlusPersonQuery(static::class);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('museum-plus-for-craft-cms', 'All'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
            'dateCreated',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        $settings = MuseumPlusForCraftCms::getInstance()->getSettings()->peoplesites;
        return $settings[$this->site->handle]['uriFormat'];
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/people/' . $this->id);
    }

    protected function cpEditUrl(): ?string
    {
        return $this->getCpEditUrl();
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('museum-plus-for-craft-cms/people');
    }

    protected function route(): array|string|null
    {
        // Define how museum plus people should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['museumPlusPerson' => $this],
            ]
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewMuseumPlusPeople');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveMuseumPlusPeople');
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    public function getFieldLayout(): FieldLayout
    {
        return \Craft::$app->fields->getLayoutByType(MuseumPlusPerson::class);
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('museum-plus-people'),
            ],
        ]);
    }

    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            $personRecord = new PersonRecord();
            $personRecord->id = $this->id;
        } else{
            $personRecord = PersonRecord::findOne($this->id);
        }

        $personRecord->collectionId = $this->collectionId;
        $personRecord->data = $this->data;
        $personRecord->save(false);

        parent::afterSave($isNew);
    }

    public function getItems(): array
    {
        return MuseumPlusItem::find()
            ->innerJoin(
                '{{%museumplus_items_people}} mip',
                '[[mip.itemId]] = [[elements.id]]'
            )
            ->where(['mip.personId' => $this->id])
            ->groupBy('mip.itemId')
            ->limit(50)
            ->all();
    }

    public function getOwnerships(): array
    {
        $ownerships = [];
        $ownershipsQuery = (new Query())
            ->from('{{%museumplus_ownerships_people}}')
            ->where(['personId' => $this->id])
            ->orderBy(['id' => SORT_ASC])->all();

        foreach ($ownershipsQuery as $ownership){
            $_ownership = OwnershipRecord::find()
                ->where(['id' => $ownership['ownershipId']])
                ->one();
            if($_ownership){
                $ownerships[] = $_ownership;
            }
        }

        return $ownerships;

    }

    public function getAssets(): array
    {
        $assets = [];
        $multiMedia = (new Query())
            ->select(['assetId'])
            ->from('{{%museumplus_people_assets}}')
            ->where(['peopleId' => $this->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach($multiMedia as $asset){
            $assets[] = Craft::$app->assets->getAssetById($asset['assetId']);
        }

        return $assets;
    }

    public function getDataAttributes() {
        $rec = $this->getRecord();
        return $rec->getDataAttributes();
    }

    public function getRecord() {
        if (empty($this->record)) {
            $this->record = PersonRecord::findOne($this->id);
        }
        return $this->record;
    }
    public function getSupportedSites(): array
    {
        $sites = MuseumPlusForCraftCms::getInstance()->getSettings()->peoplesites;
        $filteredSites = [];
        foreach ($sites as $siteHandle => $siteSettings) {
            if (!empty($siteSettings['uriFormat'])) {
                $site = \Craft::$app->sites->getSiteByHandle($siteHandle);
                $filteredSites[] = $site->id;
            }
        }
        return $filteredSites;
    }
}
