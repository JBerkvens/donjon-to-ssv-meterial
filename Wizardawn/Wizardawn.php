<?php

namespace ssv_material_parser;

use Exception;
use Wizardawn\Models\Building;
use Wizardawn\Models\City;
use Wizardawn\Models\Map;
use Wizardawn\Models\NPC;

require_once 'Converter.php';

ini_set('max_input_vars', '100000');

?>
    <h1>Convert Wizardawn Files to the SSV Material theme</h1>
<?php
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    ?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="hidden" name="save" value="upload">
        <input type="file" name="html_file" required><br/>
        <select name="parse_output">
            <option value="mp_dd">D&D Objects</option>
            <option value="html">HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
    </form>
    <?php
} else {
    $nextPage = '';
    switch ($_POST['save']) {
        case 'upload':
            $nextPage = 'npcs';
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
            $city             = Converter::Convert($fileContent);
            $_SESSION['city'] = $city;
            $_SESSION['saved_npcs'] = [];
            $_SESSION['saved_buildings'] = [];
            if ($_POST['parse_output'] == 'html') {
                ?><textarea><?= $city->getHTML() ?></textarea><?php
            }
            break;
        case 'npcs':
            if (isset($_POST['next'])) {
                $nextPage = 'buildings';
                break;
            }
            if (isset($_POST['save_single'])) {
                $nextPage = 'npcs';
                $id = $_POST['save_single'];
                NPC::getFromPOST($id, true)->toWordPress();
            } else {
                $nextPage = 'buildings';
                foreach ($_POST['npc___save'] as $id) {
                    NPC::getFromPOST($id)->toWordPress();
                }
            }
            break;
        case 'buildings':
            if (isset($_POST['next'])) {
                $nextPage = 'city';
                break;
            }
            if (isset($_POST['previous'])) {
                $nextPage = 'npcs';
                break;
            }
            if (isset($_POST['save_single'])) {
                $nextPage = 'buildings';
                $id = $_POST['save_single'];
                Building::getFromPOST($id, true)->toWordPress();
            } else {
                $nextPage = 'city';
                foreach ($_POST['building___save'] as $id) {
                    Building::getFromPOST($id)->toWordPress();
                }
            }
            break;
        case 'city':
            if (isset($_POST['previous'])) {
                $nextPage = 'buildings';
                break;
            }
            $nextPage = 'done';
            /** @var City $city */
            $city = $_SESSION['city'];
            if (isset($_POST['saveCity']) && $_POST['saveCity'] == 'false') {
                break;
            }
            if (isset($_POST['saveMap']) && $_POST['saveMap'] == 'true') {
                $city->getMap()->updateFromPOST();
            }
            $city->toWordPress();
            break;
    }

    switch ($nextPage) {
        case 'npcs':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="next" class="button button-primary button-large" value="Buildings >">
                </div>
                <br/>
                <?= get_submit_button('Save all NPCs'); ?>
                <br/>
                <input type="hidden" name="save" value="npcs">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    if ($building instanceof Building) {
                        foreach ($building->getNPCs() as $npc) {
                            if ($npc instanceof NPC) {
                                echo $npc->getHTML();
                            }
                        }
                    }
                }
                ?>
            </form>
            <?php
            break;
        case 'buildings':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large" value="< NPC's">
                    <input type="submit" name="next" id="submit" class="button button-primary button-large" value="City >">
                </div>
                <br/>
                <?= get_submit_button('Save all Buildings'); ?>
                <br/>
                <input type="hidden" name="save" value="buildings">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    if ($building instanceof Building) {
                        echo $building->getHTML();
                    }
                }
                ?>
            </form>
            <?php
            break;
        case 'city':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large" value="< Buildings">
                </div>
                <br/>
                <?= get_submit_button('Save city') ?>
                <br/>
                <input type="hidden" name="save" value="city">
                <?php
                echo $city->getHTML();
                ?>
            </form>
            <?php
            break;
        case 'done':
            echo 'Finished parsing!';
            break;
    }
}
