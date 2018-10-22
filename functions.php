<?php

use dd_parser\Parser;

if (!defined('ABSPATH')) {
    exit;
}

function dd_parser_register_plugin()
{
    wp_insert_term('City', 'area_category', array('parent' => 0));
    wp_insert_term('Building', 'area_category', array('parent' => 0));
    wp_insert_term('Room', 'area_category', array('parent' => 0));
    wp_insert_term('Forest', 'area_category', array('parent' => 0));
    wp_insert_term('Mountain', 'area_category', array('parent' => 0));
    wp_insert_term('Ocean', 'area_category', array('parent' => 0));
    wp_insert_term('Lake', 'area_category', array('parent' => 0));
}
register_activation_hook(DD_PARSER_PATH . 'ssv-material-parser.php', 'dd_parser_register_plugin');

function dd_parser_session()
{
    require_once "Wizardawn/Models/JsonObject.php";
    require_once "Wizardawn/Models/City.php";
    require_once "Wizardawn/Models/NPC.php";
    require_once "Wizardawn/Models/Map.php";
    require_once "Wizardawn/Models/MapPanel.php";
    require_once "Wizardawn/Models/MapLabel.php";
    require_once "Wizardawn/Models/Building.php";
    require_once "Wizardawn/Models/NPC.php";
    require_once "Wizardawn/Models/Product.php";
    require_once "Wizardawn/Models/Spell.php";
    require_once "Wizardawn/Models/VaultItem.php";
}

add_action('before_session_start', 'dd_parser_session', 1);
