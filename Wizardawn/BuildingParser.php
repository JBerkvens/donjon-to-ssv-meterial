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
     * @param string $type of the building (merchant, church, etc.).
     *
     * @return array of buildings
     */
    public function parseBuildings($basePart, $type)
    {
        $this->parseBase($basePart, $type);
        $this->parseOwner();
        $this->parseTable();
        $this->parseNPCs();
        return $this->buildings;
    }

    /**
     * This function converts the base HTML string into an array of buildings with the id, title, gold and a DOMElement with the Building HTML.
     *
     * @param string $basePart
     * @param string $type of the building (merchant, church, etc.).
     */
    protected function parseBase($basePart, $type)
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
            $building['type']                 = $type;
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
    protected function parseTable()
    {
        foreach ($this->buildings as &$building) {
            /** @var DOMElement $html */
            $html  = $building['html'];
            $table = $html->getElementsByTagName('tbody')->item(0);
            if ($table == null) {
                return;
            }
            for ($i = 1; $i < $table->childNodes->length; $i++) {
                $row                    = $table->childNodes->item($i);
                $product                = array();
                $product['item']        = $row->childNodes->item(1)->firstChild->firstChild->textContent;
                $product['cost']        = $row->childNodes->item(2)->firstChild->firstChild->textContent;
                $product['stock']       = $row->childNodes->item(3)->firstChild->firstChild->textContent;
                $building['products'][] = $product;
            }
        }
    }

    /**
     * This function parses the products table into an array and adding it to the building.
     */
    protected function parseNPCs()
    {
        foreach ($this->buildings as &$building) {
            if (isset($building['products'])) {
                continue; // Buildings with products are merchants and those don't have more NPCs than just the owner (and that one is already parsed).
            }
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
                $npc               = $parser->parseNPC($npcPart, $building['id'], $building['type']);
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

    /**
     * @param array   $building
     * @param array[] $npcs
     * @param string  $city
     */
    public static function toWordPress(&$building, $npcs, $city)
    {
        $building['owner'] = $npcs[$building['owner']]['wp_id'];
        if (isset($building['npcs'])) {
            foreach ($building['npcs'] as &$npcID) {
                $npcID = $npcs[$npcID]['wp_id'];
            }
        }
        $title = $building['title'];
        /** @var \wpdb $wpdb */
        global $wpdb;
        $sql         = "SELECT p.ID FROM $wpdb->posts AS p";
        $keysToCheck = array('info', 'owner');
        foreach ($keysToCheck as $key) {
            $sql .= " LEFT JOIN $wpdb->postmeta AS pm_$key ON pm_$key.post_id = p.ID";
        }
        $sql .= " WHERE p.post_type = 'building' AND p.post_title = '$title'";
        foreach ($keysToCheck as $key) {
            $value = $building[$key];
            $sql   .= " AND pm_$key.meta_key = '$key' AND pm_$key.meta_value = '$value'";
        }
        /** @var \WP_Post $foundBuilding */
        $foundBuilding = $wpdb->get_row($sql);
        if ($foundBuilding) {
            $building['wp_id'] = $foundBuilding->ID;
            return;
        }

        $citiesTerm = term_exists('Cities', 'building_category', 0);
        if (!$citiesTerm) {
            $citiesTerm = wp_insert_term('Cities', 'building_category', array('parent' => 0));
        }

        $cityTerm = term_exists($city, 'building_category', $citiesTerm['term_taxonomy_id']);
        if (!$cityTerm) {
            $cityTerm = wp_insert_term($city, 'building_category', array('parent' => $citiesTerm['term_taxonomy_id']));
        }

        $buildingType     = mp_to_title($building['type']);
        $buildingTypeTerm = term_exists($buildingType, 'building_category', 0);
        if (!$buildingTypeTerm) {
            $buildingTypeTerm = wp_insert_term($buildingType, 'building_category', array('parent' => 0));
        }

        $custom_tax = array(
            'building_category' => array(
                $cityTerm['term_taxonomy_id'],
                $buildingTypeTerm['term_taxonomy_id'],
            ),
        );

        $postID = wp_insert_post(
            array(
                'post_title'   => $building['title'],
                'post_content' => self::toHTML($building),
                'post_type'    => 'building',
                'post_status'  => 'publish',
                'tax_input'    => $custom_tax,
            )
        );
        foreach ($building as $key => $value) {
            if ($key == 'title' || $key == 'products' || $key == 'html') {
                continue;
            }
            update_post_meta($postID, $key, $value);
        }
        $building['wp_id'] = $postID;
    }

    public static function toHTML($building)
    {
        if (!isset($building['products'])) {
            return '';
        }
        ob_start();
        ?>
        <table class="striped responsive-table">
            <thead>
            <tr>
                <th>Item</th>
                <th>Cost</th>
                <th>Stock</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($building['products'] as $product): ?>
                <tr>
                    <td><?= $product['item'] ?></td>
                    <td><?= $product['cost'] ?></td>
                    <td><?= $product['stock'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return self::cleanCode(ob_get_clean());
    }
}
