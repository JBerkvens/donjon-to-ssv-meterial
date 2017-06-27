<?php

namespace ssv_material_parser;

require_once 'WizardawnConverter2.php';

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

    $converted = WizardawnConverter::Convert(file_get_contents($movedFile['file']), $type == 'mp_dd');
    if ($type == 'mp_dd') {
        preg_match("/<span>(.*?)<\/span>/", $converted['title'], $title);
        $cityTitle   = $title[1];
        $cityContent = '<style> .collapsible-body p {padding: 0;}</style>';
        if (isset($converted['map'])) {

            if (isset($converted['buildings'])) {
                foreach ($converted['buildings'] as $building) {
                    $buildingID     = $building['id'];
                    $buildingPostID = $building['post_id'];
                    $converted['map']    = str_replace("\"#modal_$buildingID\"", "\"[building-url-$buildingPostID]\"", $converted['map']);
                }
            }
            $mapID       = wp_insert_post(
                array(
                    'post_title'   => $cityTitle,
                    'post_content' => WizardawnConverter::finalizePart($converted['map']),
                    'post_type'    => 'maps',
                    'post_status'  => 'publish',
                )
            );
            $cityContent .= "[map-$mapID]";
        }
        ob_start();
        ?>
        <ul class="collapsible" id="test" data-collapsible="expandable">
            <?php foreach ($converted as $name => $value): ?>
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
        <?php
        $cityContent .= ob_get_clean();

        if (isset($converted['buildings'])) {
            foreach ($converted['buildings'] as $building) {
                $buildingID     = $building['id'];
                $buildingPostID = $building['post_id'];
                $cityContent    = str_replace("\"#modal_$buildingID\"", "\"[building-url-$buildingPostID]\"", $cityContent);
            }
        }

        wp_insert_post(
            array(
                'post_title'   => $cityTitle,
                'post_content' => WizardawnConverter::finalizePart($cityContent),
                'post_type'    => 'cities',
                'post_status'  => 'publish',
            )
        );
    } else {
        ?>
        Result<br/>
        <textarea title="Result" style="width: 100%; height: 500px;">
            <style>
                .collapsible-body p {
                    padding: 0;
                }
            </style>
            <?= isset($converted['map']) ? $converted['map'] : '' ?>
            <ul class="collapsible" id="test" data-collapsible="expandable">
                <?php foreach ($converted as $name => $value): ?>
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
            <?= isset($converted['buildings']) ? $converted['buildings'] : '' ?>
        </textarea>
        <br/><br/>
        <?php foreach ($converted as $name => $value): ?>
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
