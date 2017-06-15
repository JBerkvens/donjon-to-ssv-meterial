<?php

/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 14-6-17
 * Time: 7:15
 */
abstract class WizardawnConverter
{
    public static function Convert($content)
    {
//        $pregSearch = '/<b><i><font size="3">[0-9]<\/font><\/i><\/b><font size="2">&nbsp;-&nbsp;<b>(.*)<\/b>/';
//        $content = preg_replace($pregSearch, "<a href=\"test\">$0</a>", $content);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($content);

        $baseElements = $file->getElementsByTagName('body')->item(0)->childNodes;

        $mapPart       = '';
        $titlePart     = '';
        $rulerPart     = '';
        $merchantsPart = '';
        $guildsPart    = '';
        $guardsPart    = '';
        $churchesPart  = '';
        $filter        = 'map';
        foreach ($baseElements as $baseElement) {
            $html = $file->saveHTML($baseElement);
            if ($filter == 'map' && strpos($html, '<hr>') !== false) {
                $filter = 'title';
                continue;
            }
            if (strpos($html, 'wtown_02.jpg') !== false) {
                $filter = 'ruler';
                $html   = preg_replace('/.\/[\s\S]+?\//', '/convert/', $html);
            }
            if (strpos($html, 'wtown_06.jpg') !== false) {
                $filter = 'merchants';
                $html   = preg_replace('/.\/[\s\S]+?\//', '/convert/', $html);
            }
            if (strpos($html, 'wtown_07.jpg') !== false) {
                $filter = 'guilds';
                $html   = preg_replace('/.\/[\s\S]+?\//', '/convert/', $html);
            }
            if (strpos($html, 'wtown_03.jpg') !== false) {
                $filter = 'guards';
                $html   = preg_replace('/.\/[\s\S]+?\//', '/convert/', $html);
            }
            if (strpos($html, 'wtown_04.jpg') !== false) {
                $filter = 'churches';
                $html   = preg_replace('/.\/[\s\S]+?\//', '/convert/', $html);
            }
            if ($filter == 'map') {
                $mapPart .= $html;
            }
            if ($filter == 'title') {
                $titlePart .= $html;
            }
            if ($filter == 'ruler') {
                $rulerPart .= $html;
            }
            if ($filter == 'merchants') {
                $merchantsPart .= $html;
            }
            if ($filter == 'guilds') {
                $guildsPart .= $html;
            }
            if ($filter == 'guards') {
                $guardsPart .= $html;
            }
            if ($filter == 'churches') {
                $churchesPart .= $html;
            }
        }

        $merchantsPart = self::parseMerchants($merchantsPart);

        $map       = self::parsePart($mapPart, 'maps');
        $title     = self::parsePart($titlePart, 'maps');
        $ruler     = self::parsePart($rulerPart, 'pics_tools');
        $merchants = self::parsePart($merchantsPart, 'pics_tools');
        $guilds    = self::parsePart($guildsPart, 'pics_tools');
        $guards    = self::parsePart($guardsPart, 'pics_tools');
        $churches  = self::parsePart($churchesPart, 'pics_tools');

        return array(
            'map'       => $map,
            'title'     => $title,
            'ruler'     => $ruler,
            'merchants' => $merchants,
            'guilds'    => $guilds,
            'guards'    => $guards,
            'churches'  => $churches,
        );
    }

    private static function parsePart($part, $imageFolder)
    {
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $images = $file->getElementsByTagName('img');
        foreach ($images as $image) {
            $imageStart = $file->saveHTML($image);
            if (strpos($imageStart, 'wizardawn.and-mag.com') === false && strpos($imageStart, 'convert') === false) {
                $imageNew = preg_replace('/.\/[\s\S]+?\//', 'http://wizardawn.and-mag.com/' . $imageFolder . '/', $imageStart);
                $part     = str_replace($imageStart, $imageNew, $part);
            }
        }
//        $hrTags = $file->getElementsByTagName('hr');
//        foreach ($hrTags as $hr) {
//            $startHTML = $file->saveHTML($hr);
//            $part = str_replace($startHTML, '', $part);
//        }
        $part = str_replace('<font', '<p', $part);
        $part = str_replace('</font', '</p', $part);
        return $part;
    }

    private static function parseMerchants($merchantsPart)
    {
        $merchantsPart = preg_replace("/<font size=\"3\">([0-9]+)<\/font>/", "##START##$0", $merchantsPart);
        $merchantsPart = str_replace(array('<b><i>', '</i></b>'), '', $merchantsPart);
        $merchantsPart = str_replace('</i><b>', '</i>', $merchantsPart);
//        $pregSearch = '/<b><i><font size="3">[0-9]<\/font><\/i><\/b><font size="2">&nbsp;-&nbsp;<b>(.*)<\/b>/';
//        $parts = preg_split($pregSearch, $merchantsPart);
        $parts = preg_split("/##START##/", $merchantsPart);
        foreach ($parts as &$part) {
            $found = preg_match_all("/<font size=\"3\">([0-9]+)<\/font>/", $part, $ids);
            if ($found) {
                preg_match_all("/ - <b>(.*)<\/b>/", $part, $titles);
                $id    = $ids[1][0];
                $title = $titles[1][0];
                $part  = "<a class=\"modal-trigger\" href=\"#modal_$id\">$title</a><div id=\"modal_$id\" class=\"modal modal-fixed-footer\"><div class=\"modal-content\">$part</div></div>";
                $part  = preg_replace("/<font size=\"3\">$id<\/font>(.*) - <b>(.*)<\/b>/", "<h2>$title</h2>", $part);
            }
        }
        return implode('', $parts);
    }
}
