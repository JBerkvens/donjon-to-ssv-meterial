<?php

namespace ssv_material_parser;

use Wizardawn\Models\NPC;

require_once 'Converter.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    ?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="hidden" name="save" value="upload">
        <input type="file" name="html_file"><br/>
        <select name="parse_output">
            <option value="mp_dd">D&D Objects</option>
            <option value="html">HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
        <input type="submit" value="Test" name="submit">
    </form>
    <?php
} else {
    $nextPage = '';
    if (!isset($_POST['save'])) {
        mp_var_export($_POST, 1);
    }
    switch ($_POST['save']) {
        case 'upload':
            $nextPage = 'npcs';
            if ($_POST['submit'] == 'Test') {
                $fileContent = file_get_html(Parser::URL . 'test/001.html');
            } else {
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                $uploadedFile    = $_FILES['html_file'];
                $uploadOverrides = array('test_form' => false);
                $movedFile       = wp_handle_upload($uploadedFile, $uploadOverrides);
                if (!$movedFile || isset($movedFile['error']) || $movedFile['type'] != 'text/html') {
                    echo $movedFile['error'];
                    return;
                }
                $fileContent = file_get_html($movedFile['file']);
            }
            $city             = Converter::Convert($fileContent);
            $_SESSION['city'] = $city;
            if ($_POST['parse_output'] == 'html') {
                ?><textarea><?= $city->getHTML() ?></textarea><?php
            }
            break;
        case 'npcs':
            $nextPage = 'buildings';
            foreach ($_POST['npc___save'] as $id) {
                mp_var_export(NPC::getFromPOST($id), 1);
            }
            break;
        case 'buildings':
            $nextPage = 'city';
            break;
        case 'city':
            break;
    }

    switch ($nextPage) {
        case 'npcs':
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <input type="hidden" name="save" value="npcs">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    foreach ($building->getNPCs() as $npc) {
                        echo $npc->getHTML();
                    }
                }
                echo get_submit_button('Save NPCs');
                ?>
            </form>
            <?php
            break;
        case 'buildings':
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    echo $building->getHTML();
                }
                echo get_submit_button('Save buildings');
                ?>
                <input type="hidden" name="next_page" value="buildings">
            </form>
            <?php
            break;
    }
}
