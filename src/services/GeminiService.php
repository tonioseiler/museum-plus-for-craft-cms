<?php
/**
* MuseumPlus for CraftCMS plugin for Craft CMS 3.x
*
* Allows to import MuseumsPlus Collection data to Craft CMS and publish data. Additioanl Web Specific Data can be added to the imported data.
*
* @link      https://furbo.ch
* @copyright Copyright (c) 2022 Furbo GmbH
*/

namespace furbo\museumplusforcraftcms\services;

use furbo\museumplusforcraftcms\elements\MuseumPlusItem;

//use Gemini;
use GeminiAPI\Client;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Resources\Parts\TextPart;

use Craft;
use craft\base\Component;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;


/**
* Gemini Service
*
* generate Text from images
*
*     MuseumPlusForCraftCms::$plugin->gemini->generateContent($itemId)
*
*
* @author    Furbo GmbH
* @package   MuseumPlusForCraftCms
* @since     1.0.0
*/
class GeminiService extends Component
{
    public function generateContent($itemId) {
        $aiData = [];
        $item = MuseumPlusItem::find()->id($itemId)->one();
        $prompt = "Generate a title for the museum object in the image. The name of the image is " . $item->title;
        // TODO prepare a complete prompt based on the available item's data
        $mainImage = $item->getAttachment();
        $imagePath = $mainImage->getUrl();
        $imageEncoded = base64_encode(file_get_contents($imagePath));
        $pluginsettings = MuseumPlusForCraftCms::$plugin->getSettings();
        $client = new Client($pluginsettings['googleGeminiApiKey']);
        $result = $client->geminiProVision()->generateContent(
            new TextPart($prompt),
            new ImagePart(
                MimeType::IMAGE_JPEG,
                $imageEncoded,
            ),
        );
        $aiData['extraTitle'] = $result->text();
        $aiData['extraDescription'] = 'not implemented yet';
        return $aiData;
    }

}
