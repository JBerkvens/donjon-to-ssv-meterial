<?php

namespace Wizardawn\Models;

class City extends JsonObject
{
    protected $title = 'Test City';
    /** @var Map */
    protected $map = null;
    /** @var Building[] */
    protected $buildings = [];

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setMap(Map $map)
    {
        $this->map = $map;
    }

    public function getMap(): Map
    {
        return $this->map;
    }

    public function addBuilding(Building $building)
    {
        if ($oldBuilding = $this->getBuildingByLabel($building->label)) {
            $this->buildings[$oldBuilding->id] = $oldBuilding->updateWith($building);
        } else {
            $this->buildings[$building->id] = $building;
        }
    }

    public function getBuildings()
    {
        return $this->buildings;
    }

    /**
     * @param string $label
     *
     * @return null|Building
     */
    public function getBuildingByLabel(string $label)
    {
        foreach ($this->buildings as $building) {
            if ($building->label == $label) {
                return $building;
            }
        }
        return null;
    }

    public function getHTML()
    {
        ob_start();
        ?>
        <table style="position: relative; display: inline-block; border: 1px solid black; margin-right: 4px;">
            <tbody>
            <tr>
                <td><label>Save</label></td>
                <td>
                    <select name="saveCity" title="Save">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Include Map</label></td>
                <td>
                    <select name="saveMap" title="Include Map">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Map</label></td>
                <td>
                    <?= $this->map->getHTML(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    public function getHiddenField()
    {
        ob_start();
        ?>
        <input type="hidden" name="city" value='<?= $this->serialize() ?>'>
        <?php
        return ob_get_clean();
    }

    public function toWordPress()
    {
        $title   = $this->title;
        $content = $this->getWordPressContent();

        /** @var \wpdb $wpdb */
        global $wpdb;
        $sql         = "SELECT p.ID FROM $wpdb->posts AS p WHERE p.post_type = 'area' AND p.post_title = '$title' AND p.post_content = '$content'";
        /** @var \WP_Post $foundCity */
        $foundCity = $wpdb->get_row($sql);
        if ($foundCity) {
            // The NPC has been found (not saving another instance but returning the found ID).
            return $foundCity->ID;
        }

        $cityTerm = term_exists('City', 'area_type', 0);
        if (!$cityTerm) {
            $cityTerm = wp_insert_term('City', 'area_type', ['parent' => 0]);
        }

        $custom_tax = [
            'area_type' => [
                $cityTerm['term_taxonomy_id'],
            ],
        ];

        $wp_id = wp_insert_post(
            [
                'post_title'   => $title,
                'post_content' => $content,
                'post_type'    => 'area',
                'post_status'  => 'publish',
                'tax_input'    => $custom_tax,
            ]
        );
        update_post_meta($wp_id, 'visible_objects', $this->getMap()->getVisibleBuildings());
        update_post_meta($wp_id, 'label_translations', $this->getMap()->getLabelTranslations());
        $mapImageID = media_sideload_image($this->getMap()->getImage(), $wp_id, 'The map of ' . $this->title, 'id');
        update_post_meta($wp_id, 'map_image_id', $mapImageID);
        return $wp_id;
    }

    private function getWordPressContent()
    {
        $visibleBuildings = $this->getMap()->getVisibleBuildings();
        ob_start();
        foreach ($visibleBuildings as $building) {
            echo '[area-'.$building.']';
        }
        return ob_get_clean();
    }
}
