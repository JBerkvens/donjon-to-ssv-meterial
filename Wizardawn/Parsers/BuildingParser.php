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
use Exception;
use simple_html_dom;
use simple_html_dom_node;
use Wizardawn\Models\Building;
use Wizardawn\Models\City;

class BuildingParser extends Parser
{
    private static $buildings = array();
    private $buildingsFromType = array();

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param City            $city
     * @param simple_html_dom $html
     *
     * @return array of buildings
     * @internal param string $basePart
     * @internal param string $type of the building (merchant, church, etc.).
     */
    public static function parseBuildings(City &$city, simple_html_dom $html)
    {
        $buildings = [];
        $children = $html->childNodes();
        $building = new simple_html_dom_node($html);
        $buildingType = 'test';
        foreach ($children as $child) {
            if (self::isBuildingID($child) || $child->tag == 'br') {
                if (self::isBuildingID($building->firstChild())) {
                    $buildings[] = self::parseBuilding($building, $buildingType);
                } else {
                    if ($building->lastChild()->tag == 'img') {
                        if (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_01.jpg')) {
                            $buildingType = 'house';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_02.jpg')) {
                            $buildingType = 'ruler';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_03.jpg')) {
                            $buildingType = 'guardhouse';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_04.jpg')) {
                            $buildingType = 'church';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_05.jpg')) {
                            $buildingType = 'bank';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_06.jpg')) {
                            $buildingType = 'merchant';
                        }
                        elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_07.jpg')) {
                            $buildingType = 'guild';
                        }
                    }
                }
                $building = new simple_html_dom_node($html);
            }
            $building->appendChild($child);
        }
        // The buildings currently also contain the NPC's.
        /** @var Building $building */
        foreach ($buildings as $building) {
            mp_var_export($building, false, true, $building->getType());
        }
        exit;
        $parser = new BuildingParser();
        $parser->parseBase($basePart, $type);
        $parser->parseOwner();
        $parser->parseFamily();
        $parser->parseTable();
        $parser->parseNPCs();
        if ($type == 'houses') {
            self::$buildings = $parser->buildingsFromType;
        } else {
            foreach ($parser->buildingsFromType as $building) {
                if (isset(self::$buildings[$building['id']])) {
                    self::$buildings[$building['id']] = array_merge(self::$buildings[$building['id']], $building);
                    unset(self::$buildings[$building['id']]['html']);
                }
            }
        }
        return $parser->buildingsFromType;
    }

    private static function isBuildingID(simple_html_dom_node $node) {
        return $node->tag == 'b'
            && $node->firstChild()->tag == 'i'
            && $node->firstChild()->firstChild()->tag == 'font'
            && $node->firstChild()->firstChild()->getAttribute('size') == 3;
    }

    private static function parseBuilding(simple_html_dom_node $node, $buildingType = 'house'): Building {
        $building = new Building(intval($node->firstChild()->firstChild()->firstChild()->innertext()), $buildingType);
        switch ($building->getType()) {
            case 'house':
                foreach ($node->childNodes() as $childNode) {
                    if ($childNode->tag == 'font' && $childNode->innertext() != '-This building is empty.') {
                        $building->addNPC(NPCParser::parseNPC($childNode));
                    }
                }
                break;
            case 'merchant':
                break;
            case 'guild':
                break;
            case 'guardhouse':
                break;
            case 'church':
                break;
            default:
                throw new Exception('\''.$building->getType().'\' is an unknown building type.');
        }
//        self::printExample($building, $node);
        return $building;
    }

    /**
     * @param array   $building
     * @param array[] $npcs
     * @param string  $city
     */
    public static function toWordPress(&$building, $npcs, $city)
    {
        $building['city']  = $city;
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
            $terms = wp_get_post_terms($foundBuilding->ID, 'building_category');
            if (in_array($city, array_column($terms, 'name'))) {
                //Only if the building is in the same city it is the same building.
                $building['wp_id'] = $foundBuilding->ID;
                return;
            }
        }

        $buildingType     = mp_to_title($building['type']);
        $buildingTypeTerm = term_exists($buildingType, 'building_category', 0);
        if (!$buildingTypeTerm) {
            $buildingTypeTerm = wp_insert_term($buildingType, 'building_category', array('parent' => 0));
        }

        $custom_tax = array(
            'building_category' => array(
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
        ob_start();
        if ($building['type'] == 'houses') {
            echo '[npc-owner-with-family]';
        } else {
            echo '[npc-li-owner-with-family]';
        }
        if (isset($building['npcs'])) {
            foreach ($building['npcs'] as $npcID) {
                echo "[npc-$npcID]";
            }
        }
        if (isset($building['products'])) {
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
        }
        return self::cleanCode(ob_get_clean());
    }
}
