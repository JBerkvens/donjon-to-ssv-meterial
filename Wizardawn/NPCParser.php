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
use DOMNodeList;
use DOMText;

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
        mp_var_export($basePart, 1);
        $part = self::cleanCode($basePart);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $map = $file->getElementById('myMap');
        for ($i = 0; $i < $map->childNodes->length; $i++) {
            $panelElement = $map->childNodes->item($i);
            if ($panelElement instanceof DOMElement) {
                self::$npcs[] = self::parsePanel($panelElement);
            }
        }

        return self::$npcs;
    }
}
