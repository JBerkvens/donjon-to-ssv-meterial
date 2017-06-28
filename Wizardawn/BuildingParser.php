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
use DOMText;

class BuildingParser extends Parser
{
    protected $buildings = array();

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array of buildings
     */
    public function parseBuildings($basePart)
    {
        $this->parseBase($basePart);
        $this->parseOwner();
        $this->parseNPCs();
        return $this->buildings;
    }

    /**
     * This function converts the base HTML string into an array of buildings with the id, title, gold and a DOMElement with the Building HTML.
     *
     * @param string $basePart
     */
    protected function parseBase($basePart)
    {
        $parts = explode('<hr>', $basePart);
        foreach ($parts as $part) {
            $building = array();
            if (mp_starts_with($part, '<br>')) {
                continue; // This is the description and not needed.
            }
            $part = $this->cleanCode($part);
            $file = new DOMDocument();
            libxml_use_internal_errors(true);
            $file->loadHTML($part);

            $fontElements                     = $file->getElementsByTagName('font');
            $html                             = $fontElements->item(1);
            $building['id']                   = $fontElements->item(0)->firstChild->textContent;
            $building['html']                 = $html;
            $building['title']                = $html->childNodes->item(1)->firstChild->textContent;
            $building['info']                 = trim(str_replace(array('[', ']'), '', $html->childNodes->item(2)->textContent));
            $this->buildings[$building['id']] = $building;
        }
    }

    /**
     * This function updates all buildings and adds the owner.
     */
    protected function parseOwner()
    {
        foreach ($this->buildings as &$building) {
            /** @var DOMElement $html */
            $html      = $building['html'];
            $parser    = NPCParser::getParser();
            $foundNPCs = $parser->getNPCs(array('building_id' => $building['id'], 'type' => 'owner'));
            if (empty($foundNPCs)) {
                $foundNPCs = array($parser->parseOwner($html, $building['id']));
            }
            $owner               = $foundNPCs[0];
            $owner['profession'] = str_replace(':', '', $html->childNodes->item(3)->textContent);
            $parser->updateNPC($owner);
            $building['owner'] = $owner['id'];
        }
    }

    /**
     * This function parses the products table into an array and adding it to the building.
     */
    protected function parseNPCs()
    {
        foreach ($this->buildings as &$building) {
            /** @var DOMElement $html */
            $html     = $building['html'];
            $npcParts = $html->getElementsByTagName('font');
            for ($i = 0; $i < $npcParts->length; $i++) {
                $npcPart = $npcParts->item($i);
                if ($npcPart->firstChild instanceof DOMText) {
                    $this->parseSpell($building, $npcPart);
                    continue; // This is not an NPC but a spell.
                }
                $parser            = NPCParser::getParser();
                $npc               = $parser->parseChurchNPC($npcPart, $building['id']);
                $npc['profession'] = ucfirst(strtolower(str_replace(':', '', $npcPart->childNodes->item(0)->textContent)));
                $parser->updateNPC($npc);
                $building['npcs'][] = $npc['id'];
            }
        }
    }

    /**
     * @param array      $building
     * @param DOMElement $html
     */
    protected function parseSpell(&$building, $html)
    {
        $building['spells_cast_on'] = explode('.', $html->childNodes->item(0)->textContent)[0] . '.';
        for ($i = 1; $i < $html->childNodes->length; $i++) {
            $name = $html->childNodes->item($i)->firstChild->textContent;
            $i++;
            $cost = str_replace(',', '', explode(' ', $html->childNodes->item($i)->textContent)[2]);

            $building['spells'][] = array(
                'name' => $name,
                'cost' => $cost,
            );
        }
    }
}
