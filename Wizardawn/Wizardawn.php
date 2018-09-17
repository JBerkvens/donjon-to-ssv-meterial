<?php

namespace dd_parser;

use Exception;
use dd_parser\Wizardawn\Models\Building;
use dd_parser\Wizardawn\Models\City;
use dd_parser\Wizardawn\Models\Map;
use dd_parser\Wizardawn\Models\NPC;

require_once 'Converter.php';

ini_set('max_input_vars', '100000');

const DEVELOP = true;

?><h1>Convert Wizardawn Files to the SSV Material theme</h1><?php
if (!DEVELOP && $_SERVER['REQUEST_METHOD'] != 'POST') {
    ?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="hidden" name="save" value="upload">
        <input type="file" name="html_file"><br/>
        <select name="parse_output">
            <option value="mp_dd">D&D Objects</option>
            <option value="html">HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
    </form>
    <?php
} else {
    $nextPage = '';
    switch ($_POST['save'] ?? 'upload') {
        case 'upload':
            $nextPage = 'npcs';
            if (DEVELOP) {
                $movedFile = [
                    'file' => Parser::PATH . 'test/Eversprings.html',
                ];
            } else {
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                $uploadedFile = $_FILES['html_file'];
                $uploadOverrides = array('test_form' => false);
                $movedFile = wp_handle_upload($uploadedFile, $uploadOverrides);
                if (!$movedFile || isset($movedFile['error']) || $movedFile['type'] != 'text/html') {
                    echo $movedFile['error'];
                    return;
                }
            }
            $city = Converter::Convert(file_get_contents($movedFile['file']));
            $_SESSION['city'] = $city;
            $_SESSION['saved_npcs'] = [];
            $_SESSION['saved_buildings'] = [];
            if (isset($_POST['parse_output']) && $_POST['parse_output'] === 'html') {
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
                /** @var City $city */
                $city = $_SESSION['city'];
                foreach ($city->getBuildings() as $building) {
                    foreach ($building->getNPCs() as $npc) {
                        $npc->toWordPress();
                    }
                }
                $nextPage = 'buildings';
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
                    <input type="submit" name="next" class="button button-primary button-large" value="buildings">
                </div>
                <br/>
                <?= get_submit_button('Save all NPCs'); ?>
                <br/>
                <input type="hidden" name="save" value="npcs">
                <?php
                $npcNr= 0;
                $from = $_POST['loadMore'] ?? 0;
                $till = $from + 100;
                foreach ($city->getBuildings() as $key => $building) {
                    foreach ($building->getNPCs() as $npc) {
                        if ($npcNr >= $from && $npcNr < $till) {
                            echo $npc->getHTML();
                        }
                        ++$npcNr;
                    }
                    if ($npcNr > $till) {
                        ?><br/><button type="button" id="loadMore" name="loadMore" value="<?= $till ?>">Load More</button><?php
                        break;
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
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large"
                           value="< NPC's">
                    <input type="submit" name="next" id="submit" class="button button-primary button-large"
                           value="City >">
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
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large"
                           value="< Buildings">
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
