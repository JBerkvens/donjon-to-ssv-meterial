<?php

if (!defined('ABSPATH')) {
    exit;
}

function ssv_material_parser_add_menu()
{
    add_menu_page('MP D&D', 'MP D&D', 'edit_posts', 'mp_dd_settings', 'ssv_material_parser_settings_page', 'dashicons-feedback');
//    add_submenu_page('mp_dd_settings', 'Parser', 'Parser', 'publish_posts', 'ssv_material_parser_material_parser', 'ssv_material_parser_settings_page');
}

add_action('admin_menu', 'ssv_material_parser_add_menu', 9);

function ssv_material_parser_settings_page()
{
    $active_tab = "wizardawn";
    if (isset($_GET['tab'])) {
        $active_tab = $_GET['tab'];
    }
    ?>
    <div class="wrap">
        <h1>Users Options</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?= $_GET['page'] ?>&tab=wizardawn" class="nav-tab <?= $active_tab == 'wizardawn' ? 'nav-tab-active' : '' ?>">Wizardawn</a>
            <a href="?page=<?= $_GET['page'] ?>&tab=donjon" class="nav-tab <?= $active_tab == 'donjon' ? 'nav-tab-active' : '' ?>">donjon</a>
        </h2>
        <?php
        switch ($active_tab) {
            case "wizardawn":
                require_once "Wizardawn/Wizardawn.php";
                break;
            case "donjon":
                require_once "Donjon.php";
                break;
        }
        ?>
    </div>
    <?php
}
