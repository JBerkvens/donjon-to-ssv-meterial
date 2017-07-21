<?php

namespace ssv_material_parser;

require_once 'Converter.php';
require_once "Models/City.php";
require_once "Models/NPC.php";
require_once "Models/Map.php";
require_once "Models/MapPanel.php";
require_once "Models/MapLabel.php";
require_once "Models/Building.php";
require_once "Models/NPC.php";
require_once "Models/Product.php";

?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="file" name="html_file"><br/>
        <select name="parse_output">
            <option value="mp_dd">D&D Objects</option>
            <option value="html">HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
        <input type="submit" value="Test" name="submit">
    </form>
    <?php
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
} elseif (isset($_POST['parse_output'])) {
    $type = $_POST['parse_output'];
    if ($_POST['submit'] == 'Test') {
        $fileContent = file_get_html(Parser::URL.'test/001.html');
    } else {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH.'wp-admin/includes/file.php');
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
    $city = Converter::Convert($fileContent);
    if ($type == 'mp_dd') {
        /**
         * @var int $npcID
         * @var NPC $npc
         */
        ?>
        <form action="#" method="POST">
            <?php
            foreach ($city['npcs'] as $npcID => $npc) {
                echo $npc->getHTML();
            }
            echo get_submit_button('Save To Objects');
            ?>
        </form>
        <?php
        return;
    } else {
        ?>
        Result<br/>
        <textarea title="Result" style="width: 100%; height: 500px;">
            <style>
                .collapsible-body p {
                    padding: 0;
                }
            </style>
            <?= isset($city['map']) ? $city['map'] : '' ?>
            <ul class="collapsible" id="test" data-collapsible="expandable">
                <?php foreach ($city as $name => $value): ?>
                    <?php if ($name == 'map' || $name == 'title' || $name == 'buildings'): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php if (!empty($value)): ?>
                        <li>
                            <div class="collapsible-header" style="line-height: initial; margin-top: 10px;">
                                <img src="<?= Parser::URL ?>/images/<?= $name ?>.jpg">
                            </div>
                            <div class="collapsible-body">
                                <?= $value ?>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?= isset($city['buildings']) ? $city['buildings'] : '' ?>
        </textarea>
        <br/><br/>
        <?php foreach ($city as $name => $value): ?>
            <br/><br/>
            <?= mp_to_title($name) ?><br/>
            <textarea title="<?= $name ?>" style="width: 100%; height: 100px;">
            <style>
                .collapsible-body p {
                    padding: 0;
                }
            </style>
                <?= $value ?>
        </textarea>
        <?php endforeach; ?>
        <?php
    }
} else {
    if (isset($_POST['save_single'])) {
        $npc = NPC::getFromPOST($_POST['save_single']);
        mp_var_export($npc, 1);
    } else {
        mp_var_export($_POST, 1);
        $city = [];
        foreach ($_POST['npc___save'] as $index => $value) {
            if ($value == 'true') {
                $city['npcs'][] = NPC::getFromPOST($index);
            }
        }
        mp_var_export($city, 1);
        $title = $city['title'];
        $city['npcs'] = array_reverse($city['npcs'], true);
        foreach ($city['npcs'] as &$npc) {
            NPCParser::toWordPress($npc, $city['npcs']);
        }
        foreach ($city['buildings'] as &$building) {
            BuildingParser::toWordPress($building, $city['npcs'], $title);
        }
        if (isset($city['map'])) {
            $mapID = MapParser::toWordPress($city['map'], $city['buildings'], $title);
        }
        if (isset($city['rulers'])) {
            $rulers = RulersParser::toWordPress($city['rulers'], $title);
        }
        /** @var \wpdb $wpdb */
        global $wpdb;
        /** @var \WP_Post $foundCity */
        $foundCity = $wpdb->get_row("SELECT p.ID FROM $wpdb->posts AS p WHERE p.post_type = 'city' AND p.post_title = '$title'");
        if ($foundCity) {
            $map['wp_id'] = $foundCity->ID;
        } else {
            $cityHTML = isset($city['map']) ? '[map-'.$city['map']['wp_id'].']' : '';
            $cityHTML .= '</li>';
            if (isset($rulers)) {
                $cityHTML .= '<ul class="collapsible" id="test" data-collapsible="expandable">';
                $url = Parser::URL.'/images/rulers.jpg';
                $cityHTML .= '<li>';
                $cityHTML .= '<div class="collapsible-header" style="line-height: initial; margin-top: 10px;">';
                $cityHTML .= "<img src=\"$url\">";
                $cityHTML .= '</div>';
                $cityHTML .= '<div class="collapsible-body">';
                $cityHTML .= '[building-content-'.$rulers['wp_id'].']';
                $cityHTML .= '</div>';
            }
            foreach (['houses', 'merchants', 'guardhouses', 'churches', 'guilds'] as $part) {
                $buildingsHTML = '<ul class="browser-default">';
                foreach ($city['buildings'] as $building) {
                    if ($building['type'] == $part) {
                        if ($part == 'merchants') {
                            $buildingsHTML .= '<li>[building-link-with-type-'.$building['wp_id'].']</li>';
                        } else {
                            $buildingsHTML .= '<li>[building-link-'.$building['wp_id'].']</li>';
                        }
                    }
                }
                $buildingsHTML .= '</ul>';
                if (empty($buildingsHTML)) {
                    continue;
                }

                $url = Parser::URL.'/images/'.$part.'.jpg';
                $cityHTML .= '<li>';
                $cityHTML .= '<div class="collapsible-header" style="line-height: initial; margin-top: 10px;">';
                $cityHTML .= "<img src=\"$url\">";
                $cityHTML .= '</div>';
                $cityHTML .= '<div class="collapsible-body">';
                $cityHTML .= $buildingsHTML;
                $cityHTML .= '</div>';
                $cityHTML .= '</li>';
            }
            $cityID = wp_insert_post(
                [
                    'post_title'   => $title,
                    'post_content' => $cityHTML,
                    'post_type'    => 'city',
                    'post_status'  => 'publish',
                ]
            );
            if (isset($mapID)) {
                update_post_meta($mapID, 'visible_cities', [$cityID]);
            }
            foreach ($city['buildings'] as &$building) {
                update_post_meta($building['wp_id'], 'city', $cityID);
            }
            foreach ($city['npcs'] as &$npc) {
                $buildingID = get_post_meta($npc['wp_id'], 'building_id', true);
                update_post_meta($npc['wp_id'], 'building_id', $city['buildings'][$buildingID]['wp_id']);
            }
        }
    }
}
