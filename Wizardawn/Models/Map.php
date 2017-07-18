<?php

namespace Wizardawn\Models;

class Map
{
    /** @var MapLabel[] */
    private $labels = [];
    private $width = 500;
    private $image = null;
    private $wp_id = null;

    public function addLabel(MapLabel $label)
    {
        $this->labels[$label->getID()] = $label;
    }

    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    public function setImage(string $image)
    {
        $this->image = $image;
    }

    /**
     * @param array[] $buildings
     * @param string  $title
     *
     * @return int postID
     */
    public function toWordPress($buildings, $title)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;
        /** @var \WP_Post $foundMap */
        $foundMap = $wpdb->get_row("SELECT p.ID FROM $wpdb->posts AS p WHERE p.post_type = 'map' AND p.post_title = '$title'");
        if ($foundMap) {
            $this->wp_id = $foundMap->ID;
            return $this->wp_id;
        }

        $widthImageCount = ((($this->width - 100) - 300) / 300) + 2;
        $xModifier       = 0;
        $yModifier       = 0;
        $xImage          = 1;
        $yImage          = 1;
        $buildingLabels  = array();
        foreach ($this->labels as &$panel) {
            foreach ($panel['building_labels'] as &$buildingLabel) { //TODO BuildingLabels to Visible Objects
                if (isset($buildings[$buildingLabel['id']])) {
                    $building                 = $buildings[$buildingLabel['id']];
                    $buildingLabel['left']    += $xModifier;
                    $buildingLabel['top']     += $yModifier;
                    $buildingLabel['showing'] = true;
                    if (isset($building['wp_id'])) {
                        $buildingLabel['wp_id'] = $building['wp_id'];
                    }
                    switch ($building['type']) {
                        case 'merchants':
                            $buildingLabel['color'] = '#6a1b9a';
                            break;
                        case 'guardhouses':
                            $buildingLabel['color'] = '#1976d2';
                            break;
                        case 'churches':
                            $buildingLabel['color'] = '#d50000';
                            break;
                        case 'guilds':
                            $buildingLabel['color'] = '#1b5e20';
                            break;
                        default:
                            $buildingLabel['color'] = '#000000';
                            break;
                    }
                    $buildingLabels[] = array(
                        'id'      => $buildingLabel['wp_id'],
                        'color'   => $buildingLabel['color'],
                        'showing' => $buildingLabel['showing'],
                        'label'   => $buildingLabel['id'],
                        'top'     => $buildingLabel['top'],
                        'left'    => $buildingLabel['left'],
                    );
                }
            }

            if ($xImage == 1) {
                $xModifier += 150;
            } else {
                $xModifier += 300;
            }

            $xImage++;
            if ($xImage > $widthImageCount) {
                $xModifier = 0;
                $xImage    = 1;
                if ($yImage == 1) {
                    $yModifier += 150;
                } else {
                    $yModifier += 300;
                }
                $yImage++;
            }
        }
        $postID = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_content' => $this->toHTML(),
                'post_type'    => 'map',
                'post_status'  => 'publish',
            )
        );
        update_post_meta($postID, 'building_labels', $buildingLabels);
        $map['wp_id'] = $postID;
        return $postID;
    }

    private function toHTML() //TODO Fix it so that the images are joined into one and uploaded. @see https://diceattack.wordpress.com/2011/01/03/combining-multiple-images-using-php-and-gd/
    {
        $zIndex = count($this->labels);
        ob_start();
        ?>
        <div style="overflow-x: auto; overflow-y: hidden;">
            <div id="map" style="width: <?= $this->width ?>px; margin: auto; position: relative">
                <?php foreach ($this->labels as $panel): ?>
                    <div style="display: inline-block; position:relative; padding: 0; z-index: <?= $zIndex ?>;">
                        <img src="http://wizardawn.and-mag.com/maps/<?= $panel['image'] ?>">
                    </div>
                    <?php --$zIndex; ?>
                <?php endforeach; ?>
                [building-labels]
            </div>
        </div>
        <div class="row">
            <div class="col s12"><h2>Legend</h2></div>
            <div class="col s6 m2" style="background-color: #000000; color: #FFFFFF;border: 3px solid black;">House</div>
            <div class="col s6 m2" style="background-color: #6a1b9a; color: #FFFFFF;border: 3px solid black;">Merchant</div>
            <div class="col s6 m2" style="background-color: #1976d2; color: #FFFFFF;border: 3px solid black;">Guardhouse</div>
            <div class="col s6 m2" style="background-color: #d50000; color: #FFFFFF;border: 3px solid black;">Church</div>
            <div class="col s6 m2" style="background-color: #1b5e20; color: #FFFFFF;border: 3px solid black;">Guild</div>
        </div>
        <?php
        return ob_get_clean();
    }
}
