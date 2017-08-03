<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 26-6-17
 * Time: 20:58
 */

namespace Wizardawn\Parser;

use Exception;
use simple_html_dom;
use simple_html_dom_node;
use ssv_material_parser\Parser;
use Wizardawn\Models\Building;
use Wizardawn\Models\City;
use Wizardawn\Models\Product;
use Wizardawn\Models\Spell;

class BuildingParser extends Parser
{

    /**
     * This function parses the Building with the NPC's, Products and Spells.
     *
     * @param City            $city
     * @param simple_html_dom $html
     *
     * @return City
     */
    public static function parseBuildings(City &$city, simple_html_dom $html): City
    {
        $children     = $html->childNodes();
        $building     = new simple_html_dom_node($html);
        $buildingType = 'House';
        foreach ($children as $child) {
            if (self::isBuildingID($child) || $child->tag == 'br') {
                if (self::isBuildingID($building->firstChild())) {
                    $city->addBuilding(self::parseBuilding($building, $buildingType));
                } else {
                    if ($building->lastChild()->tag == 'img') {
                        if (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_01.jpg')) {
                            $buildingType = 'House';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_02.jpg')) {
                            $buildingType = 'Ruler';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_03.jpg')) {
                            $buildingType = 'Guardhouse';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_04.jpg')) {
                            $buildingType = 'Church';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_05.jpg')) {
                            $buildingType = 'Bank';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_06.jpg')) {
                            $buildingType = 'Merchant';
                        } elseif (mp_ends_with($building->lastChild()->getAttribute('src'), 'wtown_07.jpg')) {
                            $buildingType = 'Guild';
                        }
                    }
                }
                $building = new simple_html_dom_node($html);
            }
            $building->appendChild($child);
        }
        return $city;
    }

    private static function isBuildingID(simple_html_dom_node $node)
    {
        return $node->tag == 'b'
               && $node->firstChild()->tag == 'i'
               && $node->firstChild()->firstChild()->tag == 'font'
               && $node->firstChild()->firstChild()->getAttribute('size') == 3;
    }

    private static function parseBuilding(simple_html_dom_node $node, $buildingType = 'House'): Building
    {
        $building = new Building(intval($node->firstChild()->firstChild()->firstChild()->innertext()), $buildingType);
        switch ($building->getType()) {
            case 'House':
                foreach ($node->childNodes() as $childNode) {
                    if ($childNode->tag == 'font' && $childNode->innertext() != '-This building is empty.') {
                        $building->addNPC(NPCParser::parseNPC($childNode));
                    }
                }
                break;
            case 'Merchant':
                $label = $node->childNodes(1)->childNodes(1)->text();
                $building->setTitle($node->childNodes(1)->childNodes(0)->text());
                if (mp_starts_with($label, '(') && mp_ends_with($label, ')')) {
                    $building->setTitle($building->getTitle() . ' ' . $label);
                    $building->setType('Inn');
                }
                foreach ($node->childNodes() as $childNode) {
                    if ($childNode->tag == 'font') {
                        $building->setProducts(self::parseProductTable($childNode));
                        $cleanChildNode = $childNode->removeChild(0, $building->getType() == 'Merchant' ? 1 : 3);
                        $cleanChildNode = $cleanChildNode->removeChild($cleanChildNode->lastChild());
                        $cleanChildNode = $cleanChildNode->removeChild($cleanChildNode->lastChild());
                        $building->addNPC(NPCParser::parseNPC($cleanChildNode));
                    }
                }
                break;
            case 'Guild':
                $building->setTitle($node->childNodes(1)->childNodes(0)->text());
                foreach ($node->childNodes(1)->childNodes() as $nodeChild) {
                    if ($nodeChild->tag == 'font') {
                        $building->addNPC(NPCParser::parseNPC($nodeChild, 'guild_member'));
                    }
                }
                break;
            case 'Guardhouse':
                $building->setTitle($node->childNodes(1)->childNodes(0)->text());
                foreach ($node->childNodes(1)->childNodes() as $nodeChild) {
                    if ($nodeChild->tag == 'font') {
                        $building->addNPC(NPCParser::parseNPC($nodeChild, 'guard'));
                    }
                }
                break;
            case 'Church':
                $building->setTitle($node->childNodes(1)->childNodes(0)->text());
                foreach ($node->childNodes(1)->childNodes() as $nodeChild) {
                    if ($nodeChild->tag == 'font' && $nodeChild->firstChild()->tag == 'b') {
                        $building->addNPC(NPCParser::parseNPC($nodeChild, 'church_member'));
                    } elseif ($nodeChild->tag == 'font' && $nodeChild->firstChild()->tag == 'i') {
                        preg_match_all('/<i>(.*?)<\/i> .*? (.*?)([cseg]p)/', $nodeChild->innertext(), $spellParts);
                        for ($i = 0; $i < count($spellParts[0]); ++$i) {
                            $building->addSpell(new Spell($spellParts[1][$i], $spellParts[2][$i] . $spellParts[3][$i]));
                        }
                    }
                }
                break;
            default:
                throw new Exception('\'' . $building->getType() . '\' is an unknown building type.');
        }
        return $building;
    }

    private static function parseProductTable(simple_html_dom_node $node): array
    {
        $table       = $node->lastChild();
        $productList = [];
        foreach ($table->firstChild()->childNodes() as $row) {
            if ($row === $table->firstChild()->firstChild()) {
                continue;
            }
            $name                      = $row->childNodes(1)->firstChild()->innertext();
            $cost                      = $row->childNodes(2)->firstChild()->innertext();
            $inStock                   = intval($row->childNodes(3)->firstChild()->innertext());
            $product                   = new Product($name, $cost, $inStock);
            $productList[$product->id] = $product;
        }
        return $productList;
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
