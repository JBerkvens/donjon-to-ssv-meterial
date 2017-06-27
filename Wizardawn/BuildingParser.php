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
    protected static $buildings = array();

    /**
     * This function converts the base HTML string into an array of buildings with the id, title, gold and a DOMElement with the Building HTML.
     *
     * @param string $basePart
     */
    protected static function parseBase($basePart)
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

            $fontElements                     = $file->getElementsByTagName('font');
            $html                             = $fontElements->item(1);
            $building['id']                   = $fontElements->item(0)->firstChild->textContent;
            $building['html']                 = $html;
            $building['title']                = $html->childNodes->item(1)->firstChild->textContent;
            $building['gold']                 = trim(str_replace(array('[', ']'), '', $html->childNodes->item(2)->textContent));
            self::$buildings[$building['id']] = $building;
        }
    }

    /**
     * This function updates all buildings and adds the owner.
     */
    protected static function parseOwner()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html      = $building['html'];
            $foundNPCs = NPCParser::getNPCs(array('building_id' => $building['id'], 'type' => 'owner'));
            if (empty($foundNPCs)) {
                //TODO Parse Owner.
            }
            $owner               = $foundNPCs[0];
            $owner['profession'] = str_replace(':', '', $html->childNodes->item(3)->textContent);
            NPCParser::updateNPC($owner);
            $building['owner'] = $owner['id'];
        }
    }
}
