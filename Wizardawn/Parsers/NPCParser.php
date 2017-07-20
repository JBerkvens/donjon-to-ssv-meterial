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
use simple_html_dom_node;

class NPCParser extends Parser
{

    private static $typeMap = [
        '-' => 'main',
        '--' => 'spouse',
        '---' => 'child',
    ];

    public static function parseNPC(simple_html_dom_node $node): NPC
    {
        $npc = new NPC();
        $type = explode('<b>', $node->innertext())[0];
        $type = self::$typeMap[$type];
        $npc->type = $type;
        mp_var_export($type);
        return $npc;
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
