<?php
/**
 * MuseumPlus for CraftCMS plugin for Craft CMS 3.x
 *
 * Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
 *
 * @link      https://furbo.ch
 * @copyright Copyright (c) 2022 Furbo GmbH
 */

namespace furbo\museumplusforcraftcms\models;

use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;

use Craft;
use craft\base\Model;

/**
 * MuseumPlusForCraftCms Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Furbo GmbH
 * @package   MuseumPlusForCraftCms
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $cpTitle = 'Collection';

    public bool $showImagesInIndex = true;

    public $classifier = '';

    public $hostname = '';

    public $username = '';

    public $password = '';

    public $languageHeader = 'en';

    // vocabulary term languages to import, comma separated ISO codes (e.g. "de, en")
    public $vocabularyLanguages = 'de';

    // language of the main vocabulary title, also used for sites without a term in their language
    public $vocabularyFallbackLanguage = 'de';

    public $objectGroups = [];

    public $exhibitions = [];

    public $attachmentVolumeId = 0;

    public $attachmentFileTypes = '';

    public $googleGeminiApiKey = '';

    public $sites = [];

    public $peoplesites = [];

    public $sitemapSections = [];

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
        return [
            ['cpTitle', 'string'],
            [['languageHeader', 'vocabularyFallbackLanguage'], 'trim'],
            // sent as Accept-Language header: one language, not a list
            ['languageHeader', 'required', 'message' => 'Enter one language code, e.g. "de" or "en".'],
            ['languageHeader', 'match', 'pattern' => '/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/i',
                'message' => 'Enter one language code, e.g. "de" or "en" (not a list).'],
            ['vocabularyFallbackLanguage', 'required', 'message' => 'Enter one language code, e.g. "de".'],
            ['vocabularyFallbackLanguage', 'match', 'pattern' => '/^[a-z]{2,3}$/i',
                'message' => 'Enter one language code, e.g. "de".'],
            // inline validators skip empty values by default; an empty list must be an error
            ['vocabularyLanguages', 'validateVocabularyLanguages', 'skipOnEmpty' => false],
        ];
    }

    public function validateVocabularyLanguages($attribute): void
    {
        $languages = is_array($this->$attribute) ? $this->$attribute : explode(',', (string)$this->$attribute);
        $languages = array_filter(array_map(fn($language) => trim((string)$language), $languages), fn($language) => $language !== '');
        if (empty($languages)) {
            $this->addError($attribute, 'Enter at least one language code, e.g. "de" or "de, en".');
            return;
        }
        foreach ($languages as $language) {
            if (!preg_match('/^[a-z]{2,3}$/i', $language)) {
                $this->addError($attribute, 'Enter language codes separated by commas, e.g. "de, en" ("' . $language . '" is not valid).');
                return;
            }
        }
    }

    /**
     * Vocabulary term languages to import, always including the fallback language.
     * Accepts a comma separated string (CP field) or an array (config file).
     *
     * @return string[] e.g. ['de', 'en']
     */
    public function getVocabularyLanguageCodes(): array
    {
        $languages = is_array($this->vocabularyLanguages) ? $this->vocabularyLanguages : explode(',', (string)$this->vocabularyLanguages);
        $languages[] = $this->getVocabularyFallbackLanguageCode();
        $languages = array_map(fn($language) => strtolower(trim($language)), $languages);
        return array_values(array_unique(array_filter($languages)));
    }

    public function getVocabularyFallbackLanguageCode(): string
    {
        $language = strtolower(trim((string)$this->vocabularyFallbackLanguage));
        return $language !== '' ? $language : 'de';
    }
}
