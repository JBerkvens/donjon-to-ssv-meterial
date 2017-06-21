<?php

/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 14-6-17
 * Time: 7:15
 */
abstract class WizardawnConverter
{
    const DOC_TYPE = '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
    private static $houses = array();

    public static function Convert($content)
    {
//        $pregSearch = '/<b><i><font size="3">[0-9]<\/font><\/i><\/b><font size="2">&nbsp;-&nbsp;<b>(.*)<\/b>/';
//        $content = preg_replace($pregSearch, "<a href=\"test\">$0</a>", $content);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($content);
        $body = $file->getElementsByTagName('body')->item(0);

        $baseElements = $body->childNodes;

        for ($i = 0; $i < $baseElements->length; $i++) {
            $html = $file->saveHTML($baseElements->item($i));
            if (strpos($html, 'wtown_01.jpg') !== false) {
                $badCode = trim($file->saveHTML($baseElements->item($i + 2)->childNodes->item(0)));
            }
        }
        if (isset($badCode)) {
            $html = $file->saveHTML();
            $html = str_replace($badCode, $badCode . '</font>', $html);
            $file->loadHTML($html);
        }
        $body = $file->getElementsByTagName('body')->item(0);

        $baseElements = $body->childNodes;

        $parts  = array();
        $filter = 'map';
        for ($i = 0; $i < $baseElements->length; $i++) {
            $baseElement = $baseElements->item($i);
            $html        = $file->saveHTML($baseElement);
            if ($filter == 'map' && strpos($html, '<hr>') !== false) {
                $filter = 'title';
                continue;
            }
            if (strpos($html, 'wtown_01.jpg') !== false) {
                $filter = 'citizens';
                continue;
            }
            if (strpos($html, 'wtown_02.jpg') !== false) {
                $filter = 'ruler';
                continue;
            }
            if (strpos($html, 'wtown_03.jpg') !== false) {
                $filter = 'guards';
                continue;
            }
            if (strpos($html, 'wtown_04.jpg') !== false) {
                $filter = 'churches';
                continue;
            }
            if (strpos($html, 'wtown_05.jpg') !== false) {
                $filter = 'banks';
                continue;
            }
            if (strpos($html, 'wtown_06.jpg') !== false) {
                $filter = 'merchants';
                continue;
            }
            if (strpos($html, 'wtown_07.jpg') !== false) {
                $filter = 'guilds';
                continue;
            }
            if (!isset($parts[$filter])) {
                $parts[$filter] = '';
            }
            $parts[$filter] .= trim($html);
        }
        $parts = array_filter($parts);

        if (isset($parts['citizens'])) {
            foreach ($parts as $key => &$part) {
                switch ($key) {
                    case 'citizens':
                        $part = self::parseHouses($part);
                        self::parseCitizens($part);
                        self::$houses[count(self::$houses)] .= '<hr/>';
                        break;
                    case 'guards':
                    case 'churches':
                    case 'banks':
                    case 'merchants':
                    case 'guilds':
                        $part = self::appendToHouses($part);
                        break;
                }
                $part = self::parsePart($part);
            }
        } else {
            foreach ($parts as $key => &$part) {
                switch ($key) {
                    case 'guards':
                    case 'churches':
                    case 'banks':
                    case 'merchants':
                    case 'guilds':
                        $part = self::parseHouses($part);
                        break;
                }
                $part = self::parsePart($part);
            }
        }

        foreach (self::$houses as &$house) {
            $house = self::parsePart($house . '</div></div>');
        }
//        foreach ($parts as $key => &$part) {
//            $found = preg_match_all("/###PLACEHOLDER_([0-9]+)###/", $part, $ids);
//            if ($found) {
//                foreach ($ids[1] as $id) {
//                    $part = preg_replace("/###PLACEHOLDER_$id###/", self::$houses[$id] . '</div></div>', $part);
//                }
//            }
//        }
        $parts['houses'] = implode('', self::$houses);

        return $parts;
    }

    private static function parsePart($part)
    {
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $images = $file->getElementsByTagName('img');
        foreach ($images as $image) {
            $imageStart = $file->saveHTML($image);
            if (strpos($imageStart, 'wizardawn.and-mag.com') === false && strpos($imageStart, 'convert') === false) {
                $imageNew = preg_replace('/.\/[\s\S]+?\//', 'http://wizardawn.and-mag.com/maps/', $imageStart);
                $part     = str_replace($imageStart, $imageNew, $part);
            }
        }
        $part = str_replace('<font', '<p', $part);
        $part = str_replace('</font', '</p', $part);
        return $part;
    }

    private static function parseHouses($merchantsPart)
    {
        $merchantsPart = preg_replace("/<font size=\"3\">([0-9]+)<\/font>/", "##START##$0", $merchantsPart);
        $merchantsPart = str_replace(array('<b><i>', '</i></b>'), '', $merchantsPart);
        $merchantsPart = str_replace('</i><b>', '</i>', $merchantsPart);
//        $pregSearch = '/<b><i><font size="3">[0-9]<\/font><\/i><\/b><font size="2">&nbsp;-&nbsp;<b>(.*)<\/b>/';
//        $parts = preg_split($pregSearch, $merchantsPart);
        $parts = preg_split("/##START##/", $merchantsPart);
        foreach ($parts as &$part) {
            if (preg_match_all("/<font size=\"3\">([0-9]+)<\/font>/", $part, $ids)) {
                $id                = $ids[1][0];
                $title             = "House $id";
                $house             = "<div id=\"modal_$id\" class=\"modal modal-fixed-footer\"><div class=\"modal-content\">$part";
                self::$houses[$id] = preg_replace("/<font size=\"3\">$id<\/font>/", "<h2>$title</h2>", $house);
                if (preg_match_all("/ - <b>(.*?)<\/b>/", $part, $titles)) {
                    $title = str_replace(':', '', $titles[1][0]) . " ($id)";
                }
                $part = "<a class=\"modal-trigger\" href=\"#modal_$id\">$title</a><br/>";
            }
        }
        return implode('', $parts);
    }

    private static function parseCitizens($merchantsPart)
    {
        $merchantsPart = preg_replace("/<font size=\"3\">([0-9]+)<\/font>/", "##START##$0", $merchantsPart);
        $merchantsPart = str_replace(array('<b><i>', '</i></b>'), '', $merchantsPart);
        $merchantsPart = str_replace('</i><b>', '</i>', $merchantsPart);
        $parts         = preg_split("/##START##/", $merchantsPart);
        foreach ($parts as &$part) {
            $found = preg_match_all("/<font size=\"3\">([0-9]+)<\/font>/", $part, $ids);
            if ($found) {
                $id                = $ids[1][0];
                $house             = "<div id=\"modal_$id\" class=\"modal modal-fixed-footer\"><div class=\"modal-content\">$part";
                self::$houses[$id] = preg_replace("/<font size=\"3\">$id<\/font>/", "<h2>House $id</h2>", $house);
                $part              = "<a class=\"modal-trigger\" href=\"#modal_$id\">House $id</a><br/>";
            }
        }
    }

    private static function appendToHouses($merchantsPart)
    {
        $merchantsPart = preg_replace("/<font size=\"3\">([0-9]+)<\/font>/", "##START##$0", $merchantsPart);
        $merchantsPart = str_replace(array('<b><i>', '</i></b>'), '', $merchantsPart);
        $merchantsPart = str_replace('</i><b>', '</i>', $merchantsPart);
        $parts         = preg_split("/##START##/", $merchantsPart);
        foreach ($parts as &$part) {
            $found = preg_match_all("/<font size=\"3\">([0-9]+)<\/font>/", $part, $ids);
            if ($found) {
                preg_match_all("/ - <b>(.*)<\/b>/", $part, $titles);
                preg_match_all("/\[(.*?)\] <b>(.*?):<\/b>/", $part, $info);
                $id    = $ids[1][0];
                $title = $titles[1][0];
                $owner = $info[2][0];
                $info  = $info[1][0];

                $file = new DOMDocument();
                libxml_use_internal_errors(true);
                $file->loadHTML(self::DOC_TYPE . $part);
                $firstHR           = trim($file->saveHTML($file->getElementsByTagName('hr')->item(0)));
                $htmlParts         = explode($firstHR, $part);
                $htmlParts[0]      = "<h3><b>$owner</b> [$info]</h3>";
                $part = trim(implode('', $htmlParts));
                $part = "<hr/>$part";
                self::$houses[$id] .= $part;
                self::$houses[$id] = str_replace("<h2>House $id</h2>", "<h2>$title ($id)</h2>", self::$houses[$id]);
                $part              = "<a class=\"modal-trigger\" href=\"#modal_$id\">$title ($id)</a><br/>";
            }
        }
        return implode('', $parts);
    }
}
