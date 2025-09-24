<?php

namespace furbo\museumplusforcraftcms\controllers;

use Craft;
use craft\cache\ElementQueryTagDependency;
use craft\helpers\App;
use craft\web\Controller;
use furbo\museumplusforcraftcms\MuseumPlusForCraftCms;
use yii\web\Response;

/**
 * Sitemap controller
 */
class SitemapController extends Controller
{

    const  LIMIT = 1000;

    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * museum-plus-for-craft-cms/sitemap action
     */
    public function actionIndex()
    {
        App::maxPowerCaptain();

        $settings = MuseumPlusForCraftCms::$plugin->settings;
        if(!empty($settings->sitemapSections)){
            $sitemapSection = array_filter($settings->sitemapSections, function($section){
                return $section['filename'] == $this->request->fullPath;
            });
            $class = array_keys($sitemapSection)[0];
            $item = array_values($sitemapSection)[0];

            $query = $class::find()->site('*');
            $cacheKey = ['sitemap', $this->request->fullPath];

            Craft::$app->response->format = Response::FORMAT_RAW;


            $dom = new \DOMDocument('1.0', 'utf-8');
            $dom->formatOutput = true;


            $params = Craft::$app->getRequest()->getQueryParams();
            if (isset($params['page'])) {
                $urlset = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
                $urlset->setAttributeNS(
                    'http://www.w3.org/2000/xmlns/',
                    'xmlns:xhtml',
                    'http://www.w3.org/1999/xhtml'
                );
                $dom->appendChild($urlset);

                $offset = self::LIMIT * ($params['page'] - 1);
                foreach ($query->limit(self::LIMIT)->offset($offset)->all() as $element) {
                    $url = $dom->createElement('url');
                    $urlset->appendChild($url);
                    $url->appendChild($dom->createElement('loc', $element->url));
                    $url->appendChild($dom->createElement('priority', $item['priority']));
                    $url->appendChild($dom->createElement('changefreq', $item['changefreq']));
                    $url->appendChild($dom->createElement('lastmod', $element->dateUpdated->format(\DateTime::ATOM)));
                }
            }else{
                $sitemapindex = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'sitemapindex');
                $sitemapindex->setAttributeNS(
                    'http://www.w3.org/2000/xmlns/',
                    'xmlns:xhtml',
                    'http://www.w3.org/1999/xhtml'
                );
                $dom->appendChild($sitemapindex);

                $pages = intval(ceil($query->count() / self::LIMIT));
                $firstElement  = $query->orderBy('dateUpdated DESC')->one();
                for ($i = 1; $i <= $pages; $i++) {
                    $sitemap = $dom->createElement('sitemap');
                    $sitemapindex->appendChild($sitemap);
                    $sitemap->appendChild($dom->createElement('loc', Craft::$app->request->absoluteUrl . "?page=" . $i));
                    $sitemap->appendChild($dom->createElement('lastmod', $firstElement->dateUpdated->format(\DateTime::ATOM)));
                }
            }



            $headers = Craft::$app->response->headers;
            $headers->add('Content-Type', 'text/xml');

            return $dom->saveXML();

        }
    }
}
