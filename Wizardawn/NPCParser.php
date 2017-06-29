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
        $this->parseTypes();
        $this->parseNames();
        $this->parsePhysique();
        $this->parseDescription();
        $this->parseClothing();
        $this->parsePossessions();
        return $this->npcs;
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
                if ($npc[$key] != $value) {
                    $match = false;
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
                    continue;
                }
                $id              = count($this->npcs);
                $this->npcs[$id] = array(
                    'id'          => $id,
                    'building_id' => $buildingID,
                    'html'        => $fontElement,
                );
            }
        }
    }

    /**
     * This function updates the NPCs adding the type (either 'owner', 'spouse' or 'child).
     */
    private function parseTypes()
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['type'])) {
                continue;
            }
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

    /**
     * This function updates the NPCs adding the name of the NPC.
     */
    private function parseNames()
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['name'])) {
                continue;
            }
            /** @var DOMElement $html */
            $html = $npc['html'];
            $name = $html->childNodes->item(1)->firstChild->textContent;

            $name        = str_replace(':', '', $name);
            $npc['name'] = $name;
        }
    }

    /**
     * This function updates the NPCs adding the physical properties (height and weight).
     */
    private function parsePhysique()
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['height']) && isset($npc['weight'])) {
                continue;
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
    }

    /**
     * This function updates the NPCs adding the description.
     */
    private function parseDescription($childID = 6)
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['description'])) {
                continue;
            }
            /** @var DOMElement $html */
            $html               = $npc['html'];
            $description        = $html->childNodes->item($childID)->textContent;
            $description        = trim(explode(']', $description)[1]);
            $npc['description'] = $description;
        }
    }

    /**
     * This function updates the NPCs adding an array of clothing items.
     */
    private function parseClothing($childID = 8)
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['clothing'])) {
                continue;
            }
            /** @var DOMElement $html */
            $html     = $npc['html'];
            $clothing = trim($html->childNodes->item($childID)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $npc['clothing'] = $clothing;
        }
    }

    /**
     * This function updates the NPCs adding an array of possessions.
     */
    private function parsePossessions($childID = 10)
    {
        foreach ($this->npcs as &$npc) {
            if (isset($npc['possessions'])) {
                continue;
            }
            /** @var DOMElement $html */
            $html     = $npc['html'];
            $clothing = trim($html->childNodes->item($childID)->textContent);
            $clothing = explode(', ', $clothing);
            foreach ($clothing as &$item) {
                $item = ucfirst($item);
            }
            $npc['possessions'] = $clothing;
        }
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
        while (strpos($html->ownerDocument->saveHTML($html->childNodes->item(1)), ':') === false) {
            $html->removeChild($html->childNodes->item(1));
        }
        $html->removeChild($html->childNodes->item(1));
        $id              = count($this->npcs);
        $this->npcs[$id] = array(
            'id'          => $id,
            'building_id' => $buildingID,
            'html'        => $html,
        );
        return $this->parseNPCs()[$id];
    }

    /**
     * @param DOMElement $html
     * @param int        $buildingID
     *
     * @return mixed
     */
    public function parseGuard($html, $buildingID)
    {
        /** @var DOMElement $html */
        $html  = $html->cloneNode(true);
        $info  = explode(' ', $html->childNodes->item(1)->firstChild->textContent);
        $level = $info[1];
        $class = str_replace(']', '', $info[2]);
        // Remove Merchant Specific Fields
        $html->removeChild($html->childNodes->item(0));
        $id              = count($this->npcs);
        $this->npcs[$id] = array(
            'id'          => $id,
            'building_id' => $buildingID,
            'html'        => $html,
            'type'        => 'guard',
            'level'       => $level,
            'class'       => $class,
        );
        return $this->parseNPCs()[$id];
    }

    /**
     * @param DOMElement $html
     * @param int        $buildingID
     *
     * @return mixed
     */
    public function parseChurchNPC($html, $buildingID)
    {
        /** @var DOMElement $html */
        $html  = $html->cloneNode(true);
        $info  = explode(' ', $html->childNodes->item(1)->firstChild->textContent);
        $level = $info[1];
        $class = str_replace(']', '', $info[2]);
        // Remove Merchant Specific Fields
        $html->removeChild($html->childNodes->item(0));
        $id              = count($this->npcs);
        $this->npcs[$id] = array(
            'id'          => $id,
            'building_id' => $buildingID,
            'html'        => $html,
            'type'        => 'guard',
            'level'       => $level,
            'class'       => $class,
        );
        return $this->parseNPCs()[$id];
    }

    /**
     * @param array $npc
     */
    public static function toWordPress(&$npc)
    {
        $title   = $npc['name'];
        $content = $npc['description'];
        /** @var \wpdb $wpdb */
        global $wpdb;
        $sql         = "SELECT p.ID FROM $wpdb->posts AS p";
        $keysToCheck = array('height', 'weight', 'type', 'building_id');
        foreach ($keysToCheck as $key) {
            $sql .= " LEFT JOIN $wpdb->postmeta AS pm_$key ON pm_$key.post_id = p.ID";
        }
        $sql .= " WHERE p.post_type = 'npc' AND p.post_title = '$title' AND p.post_content = '$content';";
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
        $postID = wp_insert_post(
            array(
                'post_title'   => $npc['name'],
                'post_content' => $npc['description'],
                'post_type'    => 'npc',
                'post_status'  => 'publish',
            )
        );
        foreach ($npc as $key => $value) {
            if ($key == 'name' || $key == 'description' || $key == 'html') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            update_post_meta($postID, $key, $value);
        }
        $npc['wp_id'] = $postID;
    }
}
