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
    private $npcs = array();
    /** @var  NPCParser $parser */
    private static $parser;

    private function __construct()
    {
    }

    public static function getParser()
    {
        if (self::$parser == null) {
            self::$parser = new NPCParser();
        }
        return self::$parser;
    }

    /**
     * This function parses the NPCs, adds them to the array (if they don't exist yet) and updates all NPCs with the type, name, physique, description, clothing and possessions.
     *
     * @param string|null $basePart is the HTML as a string or null if the base doesn't have to be parsed.
     *
     * @return array of all the NPCs as arrays of data.
     */
    public function parseNPCs($basePart = null)
    {
        if (empty($this->npcs) && is_string($basePart)) {
            $this->parseBase($basePart);
        }
        foreach ($this->npcs as &$npc) {
            $this->parseNPC($npc);
        }
        return $this->npcs;
    }

    private function parseNPC(&$npc)
    {
        $this->parseType($npc);
        $this->parseName($npc);
        $this->parsePhysique($npc);
        $this->parseDescription($npc);
        $this->parseClothing($npc);
        $this->parsePossessions($npc);
    }

    /**
     * This function returns an array of NPCs that fit the filters.
     *
     * @param array $args to match the keys and values to the NPC.
     *
     * @return array of NPCs matching the $args.
     */
    public function getNPCs($args = array())
    {
        $matches = array();
        foreach ($this->npcs as $npc) {
            $match = true;
            foreach ($args as $key => $value) {
                if (!key_exists($key, $npc)) {
                    $match = false;
                } else {
                    if (is_array($value)) {
                        if (!in_array($npc[$key], $value)) {
                            $match = false;
                        }
                    } else {
                        if ($npc[$key] != $value) {
                            $match = false;
                        }
                    }
                }
            }
            if ($match) {
                $matches[] = $npc;
            }
        }
        return $matches;
    }

    /**
     * This function replaces one of the NPCs with the given NPC based on the id field.
     *
     * @param array $npc the new NPC.
     */
    public function updateNPC($npc)
    {
        $this->npcs[$npc['id']] = $npc;
    }

    /**
     * This functions parses the $basePart into an array of NPCs containing an id, building_id and the DomElement HTML object.
     *
     * @param string $basePart
     */
    private function parseBase($basePart)
    {
        $part = $this->cleanCode($basePart);
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
                    // This building is empty
                    continue;
                }
                $id              = count($this->npcs);
                $this->npcs[$id] = array(
                    'id'          => $id,
                    'building_id' => $buildingID,
                    'html'        => $fontElement,
                    'children'    => array(),
                );
            }
        }
    }

    /**
     * This function updates the NPCs adding the type (either 'owner', 'spouse' or 'child).
     *
     * @param array $npc
     */
    private function parseType(&$npc)
    {
        if (isset($npc['type'])) {
            return;
        }
        /** @var DOMElement $html */
        $html = $npc['html'];
        $type = $html->firstChild->textContent;
        switch ($type) {
            case '-':
                $type = 'owner';
                break;
            case '--':
                $type    = 'spouse';
                $mainNPC = $this->getNPCs(array('building_id' => $npc['building_id'], 'type' => 'owner'))[0];
                if (!empty($mainNPC)) {
                    $mainNPC['spouse'] = $npc['id'];
                    $this->updateNPC($mainNPC);
                }
                break;
            case '---':
                $type    = 'child';
                $mainNPC = $this->getNPCs(array('building_id' => $npc['building_id'], 'type' => 'owner'))[0];
                if (!empty($mainNPC)) {
                    $mainNPC['children'][] = $npc['id'];
                    $this->updateNPC($mainNPC);
                }
                break;
        }
        $npc['type'] = $type;
    }

    /**
     * This function updates the NPCs adding the name of the NPC.
     *
     * @param array $npc
     */
    private function parseName(&$npc)
    {
        if (isset($npc['name'])) {
            return;
        }
        /** @var DOMElement $html */
        $html = $npc['html'];
        $name = $html->childNodes->item(1)->firstChild->textContent;

        $name        = str_replace(':', '', $name);
        $npc['name'] = $name;
    }

    /**
     * This function updates the NPCs adding the physical properties (height and weight).
     *
     * @param array $npc
     */
    private function parsePhysique(&$npc)
    {
        if (isset($npc['height']) && isset($npc['weight'])) {
            return;
        }
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

    /**
     * This function updates the NPCs adding the description.
     *
     * @param array $npc
     */
    private function parseDescription(&$npc)
    {
        if (isset($npc['description'])) {
            return;
        }
        /** @var DOMElement $html */
        $html               = $npc['html'];
        $description        = $html->childNodes->item(6)->textContent;
        $description        = trim(explode(']', $description)[1]);
        $npc['description'] = $description;
    }

    /**
     * This function updates the NPCs adding an array of clothing items.
     *
     * @param array $npc
     */
    private function parseClothing(&$npc)
    {
        if (isset($npc['clothing'])) {
            return;
        }
        /** @var DOMElement $html */
        $html            = $npc['html'];
        $clothing        = ucfirst(trim($html->childNodes->item(8)->textContent));
        $npc['clothing'] = $clothing;
    }

    /**
     * This function updates the NPCs adding an array of possessions.
     *
     * @param array $npc
     */
    private function parsePossessions(&$npc)
    {
        if (isset($npc['possessions'])) {
            return;
        }
        /** @var DOMElement $html */
        $html               = $npc['html'];
        $possessions        = ucfirst(trim($html->childNodes->item(10)->textContent));
        $npc['possessions'] = $possessions;
    }

    /**
     * This function updates the NPCs adding an array of possessions.
     *
     * @param array $npc
     */
    private function parseArmsAndArmor(&$npc)
    {
        if (isset($npc['arms_armor'])) {
            return;
        }
        /** @var DOMElement $html */
        $html              = $npc['html'];
        $armsAndArmor      = ucfirst(trim($html->childNodes->item(12)->textContent));
        $npc['arms_armor'] = $armsAndArmor;
    }

    /**
     * @param DOMElement $html
     * @param int        $buildingID
     *
     * @return mixed
     */
    public function parseOwner($html, $buildingID)
    {
        /** @var DOMElement $html */
        $html = $html->cloneNode(true);
        // Remove Merchant Specific Fields
        if (strpos($html->ownerDocument->saveHTML($html->childNodes->item(1)), ':') === false) {
            while (strpos($html->ownerDocument->saveHTML($html->childNodes->item(1)), ':') === false) {
                $html->removeChild($html->childNodes->item(1));
            }
            $html->removeChild($html->childNodes->item(1));
        }
        $id  = count($this->npcs);
        $npc = array(
            'id'          => $id,
            'building_id' => $buildingID,
            'html'        => $html,
            'children'    => array(),
        );
        $this->parseNPC($npc);
        $this->npcs[$id] = $npc;
        return $npc;
    }

    /**
     * @param DOMElement $html
     * @param int        $buildingID
     * @param string     $type
     *
     * @return mixed
     */
    public function parseBuildingNPC($html, $buildingID, $type)
    {
        /** @var DOMElement $html */
        $html       = $html->cloneNode(true);
        $info       = explode(' ', $html->childNodes->item(1)->firstChild->textContent);
        $level      = $info[1];
        $class      = str_replace(']', '', $info[2]);
        $profession = mp_to_title(strtolower(str_replace(':', '', $html->childNodes->item(0)->textContent)));
        $profession = $profession == 'HGT' ? '' : $profession;
        $html->removeChild($html->childNodes->item(0));
//        $foundNPC = self::getNPCs(array('building_id' => $buildingID, 'type' => $type, 'html' => $html));
        $id  = count($this->npcs);
        $npc = array(
            'id'          => $id,
            'building_id' => $buildingID,
            'html'        => $html,
            'type'        => $type,
            'profession'  => $profession,
            'level'       => $level,
            'class'       => $class,
        );
        $this->parseName($npc);
        $this->parsePhysique($npc);
        $this->parseDescription($npc);
        $this->parseClothing($npc);
        $this->parsePossessions($npc);
        $this->parseArmsAndArmor($npc);
        $this->npcs[$id] = $npc;
        return $npc;
    }

    /**
     * @param array $npc
     */
    public static function toWordPress(&$npc, $npcs = array())
    {
        $title   = $npc['name'];
        $content = $npc['description'];
        if (isset($npc['spouse'])) {
            $npc['spouse'] = $npcs[$npc['spouse']]['wp_id'];
        }
        if (isset($npc['children'])) {
            foreach ($npc['children'] as &$npcID) {
                $npcID = $npcs[$npcID]['wp_id'];
            }
        } else {
            $npc['children'] = array();
        }
        /** @var \wpdb $wpdb */
        global $wpdb;
        $sql         = "SELECT p.ID FROM $wpdb->posts AS p";
        $keysToCheck = array('height', 'weight', 'type', 'building_id');
        foreach ($keysToCheck as $key) {
            $sql .= " LEFT JOIN $wpdb->postmeta AS pm_$key ON pm_$key.post_id = p.ID";
        }
        $sql .= " WHERE p.post_type = 'npc' AND p.post_title = '$title' AND p.post_content = '$content'";
        foreach ($keysToCheck as $key) {
            $value = $npc[$key];
            $sql   .= " AND pm_$key.meta_key = '$key' AND pm_$key.meta_value = '$value'";
        }
        /** @var \WP_Post $foundNPC */
        $foundNPC = $wpdb->get_row($sql);
        if ($foundNPC) {
            $npc['wp_id'] = $foundNPC->ID;
            return;
        }

        switch ($npc['type']) {
            case 'churches':
                $npcType = 'Clergy';
                break;
            case 'guards':
                $npcType = 'Guard';
                break;
            case 'guilds':
                $npcType = 'Guild Member';
                break;
            default:
                $npcType = 'Citizen';
                break;
        }
        $npcTypeTerm = term_exists($npcType, 'npc_type', 0);
        if (!$npcTypeTerm) {
            $npcTypeTerm = wp_insert_term($npcType, 'npc_type', array('parent' => 0));
        }

        $custom_tax = array(
            'npc_type' => array(
                $npcTypeTerm['term_taxonomy_id'],
            ),
        );

        $postID = wp_insert_post(
            array(
                'post_title'   => $npc['name'],
                'post_content' => $npc['description'],
                'post_type'    => 'npc',
                'post_status'  => 'publish',
                'tax_input'    => $custom_tax,
            )
        );
        foreach ($npc as $key => $value) {
            if ($key == 'name' || $key == 'description' || $key == 'html') {
                continue;
            }
            update_post_meta($postID, $key, $value);
        }
        $npc['wp_id'] = $postID;
    }
}
