<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 26-6-17
 * Time: 20:58
 */

namespace ssv_material_parser;

use DOMDocument;
use DOMElement;

class BuildingParser extends Parser
{
    private static $buildings = array();

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array
     */
    public static function parseBuildings($basePart)
    {
        self::parseBase($basePart);
        return self::$buildings;
    }

    /**
     * @param string $basePart
     */
    private static function parseBase($basePart)
    {
        $parts = explode('<hr>', $basePart);
        foreach ($parts as $part) {
            $building = array();
            if (mp_starts_with($part, '<br>')) {
                continue; // This is the description and not needed.
            }
            $part = self::cleanCode($part);
            $file = new DOMDocument();
            libxml_use_internal_errors(true);
            $file->loadHTML($part);

            $fontElements = $file->getElementsByTagName('font');
            $building['id'] = $fontElements->item(0)->firstChild->textContent;
            $buildingHTML = $fontElements->item(1);
            $building['title'] = $buildingHTML->childNodes->item(1)->firstChild->textContent;
            $building['gold'] = trim(str_replace(array('[', ']'), '', $buildingHTML->childNodes->item(2)->textContent));
            $building['owner_profession'] = str_replace(':', '', $buildingHTML->childNodes->item(3)->textContent);
            NPCParser::getNPC($) //TODO
            mp_var_export($building['owner_profession'], 0);
            mp_var_export($building, 0);
            mp_var_export($part, 1);
        }
    }

    private static function parseTypes()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html = $building['html'];
            $type = $html->firstChild->textContent;
            switch ($type) {
                case '-':
                    $type = 'owner';
                    break;
                case '--':
                    $type = 'spouse';
                    break;
                case '---':
                    $type = 'child';
                    break;
            }
            $building['type'] = $type;
        }
    }

    private static function parseNames()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html             = $building['html'];
            $name             = $html->childNodes->item(1)->firstChild->textContent;
            $name             = str_replace(':', '', $name);
            $building['name'] = $name;
        }
    }

    private static function parsePhysique()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html = $building['html'];
            $html = $html->ownerDocument->saveHTML($html);
            if (preg_match("/\[<b>HGT:<\/b>(.*?)<b>WGT:<\/b>(.*?)\]/", $html, $physique)) {
                $height = 0;
                $weight = 0;
                if (preg_match("/(.*?)ft/", $physique[1], $feet)) {
                    $height += intval($feet[1]) * 30.48;
                }
                if (preg_match("/, (.*?)in/", $physique[1], $inches)) {
                    $height += intval($inches[1]) * 2.54;
                }
                if (preg_match("/(.*?)lbs/", $physique[2], $pounds)) {
                    $weight = intval($pounds[1]) * 0.453592;
                }
                $building['height'] = intval(round($height, 0));
                $building['weight'] = intval(round($weight, 0));
            }
        }
    }

    private static function parseDescription()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html                    = $building['html'];
            $description             = $html->childNodes->item(6)->textContent;
            $description             = trim(explode(']', $description)[1]);
            $building['description'] = $description;
        }
    }

    private static function parseClothing()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html     = $building['html'];
            $clothing = trim($html->childNodes->item(8)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $building['clothing'] = $clothing;
        }
    }

    private static function parsePossessions()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html     = $building['html'];
            $clothing = trim($html->childNodes->item(10)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $building['possessions'] = $clothing;
            mp_var_export($clothing, 0);
        }
    }
}
