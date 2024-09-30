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
        $prompt = "Write a title (approximately 160 characters) and a text (approximately 1000 characters) for the museum object in the image. The name of the image is " . $item->title .'. Send me the response in json.';
        // TODO prepare a complete prompt based on the available item's data
        $mainImage = $item->getAttachment();

        $transform = [
            'mode' => 'fit',
            'width' => 300,
            'height' => 300,
            'quality' => 80,
        ];

        $imagePath = $mainImage->getUrl($transform, true);
        $imageEncoded = base64_encode(file_get_contents($imagePath));
        $pluginsettings = MuseumPlusForCraftCms::$plugin->getSettings();
        $client = new Client($pluginsettings['googleGeminiApiKey']);
        //$result = $client->geminiProVision()->generateContent(
        $result = $client->geminiProFlash1_5()->generateContent(
            new TextPart($prompt),
            new ImagePart(
                MimeType::IMAGE_JPEG,
                $imageEncoded,
            ),
        );
        $prefix = "```json\n";
        $txt = $result->text();
        $txt = substr($txt, strlen($prefix));
        $txt = substr($txt, 0, -3);

        //dd($txt);
        $data = json_decode($txt, true);

        if (isset($data['title']))
            $aiData['extraTitle'] = $data['title'];

        if (isset($data['text']))
            $aiData['extraDescription'] = $data['text'];
        return $aiData;
    }

}
