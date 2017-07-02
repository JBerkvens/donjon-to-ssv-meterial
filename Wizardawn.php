<?php

namespace ssv_material_parser;

require_once 'WizardawnConverter.php';

$type = isset($_POST['parse_output']) ? $_POST['parse_output'] : 'mp_dd';
?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="file" name="html_file"><br/>
        <select name="parse_output">
            <option value="mp_dd" <?= $type == 'mp_dd' ? 'selected' : '' ?>>D&D Objects</option>
            <option value="html" <?= $type == 'html' ? 'selected' : '' ?>>HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
    </form>
    <?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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

    $city = WizardawnConverter::Convert(file_get_contents($movedFile['file']));
    if ($type == 'mp_dd') {
        $title        = $city['title'];
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
            $city['buildings']['rulers'] = RulersParser::toWordPress($city['rulers'], $title);
        }
        /** @var \wpdb $wpdb */
        global $wpdb;
        /** @var \WP_Post $foundCity */
        $foundCity = $wpdb->get_row("SELECT p.ID FROM $wpdb->posts AS p WHERE p.post_type = 'city' AND p.post_title = '$title'");
        if ($foundCity) {
            $map['wp_id'] = $foundCity->ID;
        } else {
            $cityHTML = isset($city['map']) ? '[map-' . $city['map']['wp_id'] . ']' : '';
            $cityHTML .= '<ul class="collapsible" id="test" data-collapsible="expandable">';
            $url      = Parser::URL . '/images/rulers.jpg';
            $cityHTML .= '<li>';
            $cityHTML .= '<div class="collapsible-header" style="line-height: initial; margin-top: 10px;">';
            $cityHTML .= "<img src=\"$url\">";
            $cityHTML .= '</div>';
            $cityHTML .= '<div class="collapsible-body">';
            $cityHTML .= '[building-content-' . $city['buildings']['rulers']['wp_id'] . ']';
            $cityHTML .= '</div>';
            $cityHTML .= '</li>';
            foreach (array('houses', 'merchants', 'guardhouses', 'churches', 'guilds') as $part) {
                $buildingsHTML = '<ul class="browser-default">';
                foreach ($city['buildings'] as $building) {
                    if ($building['type'] == $part) {
                        if ($part == 'merchants') {
                            $buildingsHTML .= '<li>[building-link-with-type-' . $building['wp_id'] . ']</li>';
                        } else {
                            $buildingsHTML .= '<li>[building-link-' . $building['wp_id'] . ']</li>';
                        }
                    }
                }
                $buildingsHTML .= '</ul>';
                if (empty($buildingsHTML)) {
                    continue;
                }

                $url      = Parser::URL . '/images/' . $part . '.jpg';
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
                array(
                    'post_title'   => $title,
                    'post_content' => $cityHTML,
                    'post_type'    => 'city',
                    'post_status'  => 'publish',
                )
            );
            if (isset($mapID)) {
                update_post_meta($mapID, 'visible_cities', array($cityID));
            }
            foreach ($city['buildings'] as &$building) {
                update_post_meta($building['wp_id'], 'city', $cityID);
            }
        }
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
}
