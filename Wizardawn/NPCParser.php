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

class NPCParser extends Parser
{
    private static $npcs = array();

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array
     */
    public static function parseNPCs($basePart)
    {
        self::parseBuildings($basePart);
        self::parseTypes();
        self::parseNames();
        self::parsePhysique();
        self::parseDescription();
        self::parseClothing();
        self::parsePossessions();
        foreach (self::$npcs as $npc) {
            unset($npc['html']);
        }
        return self::$npcs;
    }

    public static function getNPC($args)
    {
        $matches = array();
        foreach (self::$npcs as $npc) {
            $match = true;
            foreach ($args as $key => $value) {
                if ($npc[$key] != $value) {
                    $match = false;
                }
            }
            if ($match) {
                $matches[] = $npc;
            }
        }
        switch (count($matches)) {
            case 0:
                return null;
            case 1:
                return $matches[0];
            default:
                return $matches;
        }
    }

    /**
     * @param string $basePart
     */
    private static function parseBuildings($basePart)
    {
        $part = self::cleanCode($basePart);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $buildingID   = null;
        $fontElements = $file->getElementsByTagName('font');
        for ($i = 1; $i < $fontElements->length; $i++) { //$fontElements->item(0) is the description and not needed so skipped
            $fontElement = $fontElements->item($i);
            if ($fontElement->getAttribute('size') == 3) {
                $buildingID = $file->saveHTML($fontElement->childNodes->item(0));
            } elseif ($fontElement->getAttribute('size') == 2) {
                if ($fontElement->childNodes->item(0)->textContent == '-This building is empty.') {
                    continue;
                }
                self::$npcs[] = array(
                    'building_id' => $buildingID,
                    'html'        => $fontElement,
                );
            }
        }
    }

    private static function parseTypes()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html = $npc['html'];
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
            $npc['type'] = $type;
        }
    }

    private static function parseNames()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html        = $npc['html'];
            $name        = $html->childNodes->item(1)->firstChild->textContent;
            $name        = str_replace(':', '', $name);
            $npc['name'] = $name;
        }
    }

    private static function parsePhysique()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html = $npc['html'];
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
                $npc['height'] = intval(round($height, 0));
                $npc['weight'] = intval(round($weight, 0));
            }
        }
    }

    private static function parseDescription()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html               = $npc['html'];
            $description        = $html->childNodes->item(6)->textContent;
            $description        = trim(explode(']', $description)[1]);
            $npc['description'] = $description;
        }
    }

    private static function parseClothing()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html     = $npc['html'];
            $clothing = trim($html->childNodes->item(8)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $npc['clothing'] = $clothing;
        }
    }

    private static function parsePossessions()
    {
        foreach (self::$npcs as &$npc) {
            /** @var DOMElement $html */
            $html     = $npc['html'];
            $clothing = trim($html->childNodes->item(10)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $npc['possessions'] = $clothing;
        }
    }
}
