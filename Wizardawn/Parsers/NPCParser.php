<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 26-6-17
 * Time: 20:58
 */

namespace Wizardawn\Parser;

use simple_html_dom_node;
use ssv_material_parser\Parser;
use Wizardawn\Models\NPC;

class NPCParser extends Parser
{

    private static $typeMap
        = [
            '-'   => 'main',
            '--'  => 'spouse',
            '---' => 'child',
        ];

    public static function parseNPC(simple_html_dom_node $node, $type = null): NPC
    {
        $npc  = new NPC();
        if ($type !== null) {
            $npc->profession = explode(':', ucfirst(strtolower($node->childNodes(0)->text())))[0];
            $info            = explode(' ', $node->childNodes(1)->text());
            if (!isset($info[1])) {
                mp_var_export($node);
                mp_var_export($info, 1);
            }
            $npc->level      = $info[1];
            $npc->class      = explode(']', $info[2])[0];
            $node            = $node->removeChild(0, 1);
            $npc->type       = $type;
        } else {
            $type = explode('<b>', $node->innertext())[0];
            $npc->type = self::$typeMap[$type];
        }
        $npc->name = str_replace(':', '', $node->firstChild()->text());
        list($npc->height, $npc->weight) = self::parsePhysique($node);
        $npc->description = self::parseDescription($node);
        $npc->clothing    = self::parseClothing($node);
        $npc->possessions = self::parsePossessions($node);
        $npc->arms_armor  = self::parseArmsAndArmor($node);
        return $npc;
    }

    private static function parsePhysique(simple_html_dom_node $node): array
    {
        if (preg_match("/\[<b>HGT:<\/b>(.*?)<b>WGT:<\/b>(.*?)\]/", $node->innertext(), $physique)) {
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
            return [intval(round($height, 0)), intval(round($weight, 0))];
        }
        return [];
    }

    private static function parseDescription(simple_html_dom_node $node): string
    {
        $description = explode($node->childNodes(2)->outertext(), $node->innertext())[1];
        $description = explode(']', $description)[1];
        $description = explode($node->childNodes(3)->outertext(), $description)[0];
        return trim($description);
    }

    private static function parseClothing(simple_html_dom_node $node): string
    {
        $clothing = explode($node->childNodes(3)->outertext(), $node->innertext())[1];
        return trim(explode($node->childNodes(4)->outertext(), $clothing)[0]);
    }

    private static function parsePossessions(simple_html_dom_node $node): string
    {
        $possessions = explode($node->childNodes(4)->outertext(), $node->innertext())[1];
        if ($node->childNodes(5) !== null) {
            $possessions = trim(explode($node->childNodes(5)->outertext(), $possessions)[0]);
        }
        return $possessions;
    }

    private static function parseArmsAndArmor(simple_html_dom_node $node): string
    {
        if ($node->childNodes(5) !== null) {
            return trim(explode($node->childNodes(5)->outertext(), $node->innertext())[1]);
        }
        return '';
    }

    /**
     * @param NPC $npc
     *
     * @return int|\WP_Error
     */
    public static function toWordPress(&$npc, $npcs = array())
    {
//        $title   = $npc->name;
//        $content = $npc->description;
//        if (isset($npc->spouse)) {
//            $npc->spouse = $npcs[$npc->spouse]['wp_id'];
//        }
//        if (isset($npc->children)) {
//            foreach ($npc->children as &$npcID) {
//                $npcID = $npcs[$npcID]['wp_id'];
//            }
//        } else {
//            $npc->children = array();
//        }
//        /** @var \wpdb $wpdb */
//        global $wpdb;
//        $sql         = "SELECT p.ID FROM $wpdb->posts AS p";
//        $keysToCheck = array('height', 'weight', 'type', 'building_id');
//        foreach ($keysToCheck as $key) {
//            $sql .= " LEFT JOIN $wpdb->postmeta AS pm_$key ON pm_$key.post_id = p.ID";
//        }
//        $sql .= " WHERE p.post_type = 'npc' AND p.post_title = '$title' AND p.post_content = '$content'";
//        foreach ($keysToCheck as $key) {
//            $value = $npc[$key];
//            $sql   .= " AND pm_$key.meta_key = '$key' AND pm_$key.meta_value = '$value'";
//        }
//        /** @var \WP_Post $foundNPC */
//        $foundNPC = $wpdb->get_row($sql);
//        if ($foundNPC) {
//            $npc->wp_id = $foundNPC->ID;
//            return $foundNPC->ID;
//        }
//
//        switch ($npc->type) {
//            case 'rulers':
//                $npcType = 'Ruler';
//                break;
//            case 'churches':
//                $npcType = 'Clergy';
//                break;
//            case 'guards':
//                $npcType = 'Guard';
//                break;
//            case 'guilds':
//                $npcType = 'Guild Member';
//                break;
//            default:
//                $npcType = 'Citizen';
//                break;
//        }
//        $npcTypeTerm = term_exists($npcType, 'npc_type', 0);
//        if (!$npcTypeTerm) {
//            $npcTypeTerm = wp_insert_term($npcType, 'npc_type', array('parent' => 0));
//        }
//
//        $custom_tax = array(
//            'npc_type' => array(
//                $npcTypeTerm['term_taxonomy_id'],
//            ),
//        );
//
//        $postID = wp_insert_post(
//            array(
//                'post_title'   => $npc->name,
//                'post_content' => $npc->description,
//                'post_type'    => 'npc',
//                'post_status'  => 'publish',
//                'tax_input'    => $custom_tax,
//            )
//        );
//        foreach ($npc as $key => $value) {
//            if ($key == 'name' || $key == 'description' || $key == 'html') {
//                continue;
//            }
//            update_post_meta($postID, $key, $value);
//        }
//        $npc->wp_id = $postID;
//        return $postID;
    }
}
