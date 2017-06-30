<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 26-6-17
 * Time: 20:58
 */

namespace ssv_material_parser;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMText;

class MapParser extends Parser
{
    private $map = array('width' => 0, 'panels' => array());
    private static $parser;

    private function __construct()
    {
    }

    public static function getParser()
    {
        if (self::$parser == null) {
            self::$parser = new MapParser();
        }
        return self::$parser;
    }

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array
     */
    public function parseMap($basePart)
    {
        $part = $this->cleanCode($basePart);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $map   = $file->getElementById('myMap');
        $width = $map->getAttribute("style");
        preg_match('/width: (.*?)px/', $width, $width);
        $this->map['width'] = ($width[1] - 5) + 100;
        for ($i = 0; $i < $map->childNodes->length; $i++) {
            $panelElement = $map->childNodes->item($i);
            if ($panelElement instanceof DOMElement) {
                $this->map['panels'][] = $this->parsePanel($panelElement);
            }
        }

        return $this->map;
    }

    /**
     * @param DOMElement $panelElement
     *
     * @return array panel
     */
    private function parsePanel($panelElement)
    {
        $panel = array(
            'image'           => '',
            'building_labels' => array(),
        );
        /** @var DOMNodeList $elements */
        $elements = $panelElement->getElementsByTagName('div');
        $image    = $panelElement->getElementsByTagName('img')->item(0);
        preg_match('/\/[\s\S]+?\/([\s\S]+?)"/', $image->ownerDocument->saveHTML($image), $image);
        $panel['image'] = $image[1];

        for ($i = 0; $i < $elements->length; $i++) {
            $panelBuilding       = $elements->item($i);
            $panelBuildingNumber = $panelBuilding->childNodes->item(0);
            if ($panelBuildingNumber instanceof DOMText) {
                $style = $panelBuilding->getAttribute("style");
                preg_match("/top:([0-9]+)px/", $style, $top);
                preg_match("/left:([0-9]+)px/", $style, $left);
                $panel['building_labels'][] = array(
                    'top'  => $top[1],
                    'left' => $left[1],
                    'id'   => $panelBuildingNumber->ownerDocument->saveHTML($panelBuildingNumber),
                );
            }
        }
        return $panel;
    }

    /**
     * @param array   $map
     * @param array[] $buildings
     * @param string  $title
     */
    public static function toWordPress(&$map, $buildings, $title)
    {
        foreach ($map['panels'] as &$panel) {
            foreach ($panel['building_labels'] as &$buildingLabel) {
                if (isset($buildings[$buildingLabel['id']])) {
                    $buildingLabel['link'] = true;
                    $building              = $buildings[$buildingLabel['id']];
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
                            $buildingLabel['color'] = 'rgba(0,0,0,0.75)';
                            break;
                    }
                } else {
                    $buildingLabel['link'] = false;
                }
            }
        }
        $postID       = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_content' => self::toHTML($map),
                'post_type'    => 'map',
                'post_status'  => 'publish',
            )
        );
        $map['wp_id'] = $postID;
    }

    private static function toHTML($map)
    {
        $width  = $map['width'];
        $zIndex = count($map['panels']);
        ob_start();
        ?>
        <div style="overflow-x: auto; overflow-y: hidden;">
            <div style="width: <?= $width ?>px;">
                <?php foreach ($map['panels'] as $panel): ?>
                    <div style="display: inline-block; position:relative; padding: 0; z-index: <?= $zIndex ?>;">
                        <img src="http://wizardawn.and-mag.com/maps/<?= $panel['image'] ?>">
                        <?php foreach ($panel['building_labels'] as $buildingLabel): ?>
                            <div style="position:absolute; top:<?= $buildingLabel['top'] ?>px; left:<?= $buildingLabel['left'] ?>px;">
                                <?php if ($buildingLabel['link']): ?>
                                    <?php $url = isset($buildingLabel['wp_id']) ? '[building-url-' . $buildingLabel['wp_id'] . ']' : '#modal' . $buildingLabel['id']; ?>
                                    <a href="<?= $url ?>"
                                       style="color: #FFFFFF; background: rgba(0,0,0,0.6); height: 30px; width: 30px; text-align: center; display: block; border: 3px solid <?= $buildingLabel['color'] ?>; border-radius: 20%;font-size: 9px;line-height: 25px;">
                                        <?= $buildingLabel['id'] ?>
                                    </a>
                                <?php else: ?>
                                    <p style="color: #000000; background: #FFFFFF; height: 30px; width: 30px; text-align: center; display: block; border: 3px solid black; border-radius: 20%; margin: 0;font-size: 9px;line-height: 25px;">
                                        <?= $buildingLabel['id'] ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php $zIndex--; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="row">
            <div class="col s12"><h2>Legend</h2></div>
            <div class="col s6 m2" style="background-color: #000000; color: #FFFFFF;border: 3px solid black;">House</div>
            <div class="col s6 m2" style="background-color: #6a1b9a; color: #FFFFFF;border: 3px solid black;">Merchant</div>
            <div class="col s6 m2" style="background-color: #1976d2; color: #FFFFFF;border: 3px solid black;">Guardhouse</div>
            <div class="col s6 m2" style="background-color: #d50000; color: #FFFFFF;border: 3px solid black;">Church</div>
            <div class="col s6 m2" style="background-color: #1b5e20; color: #FFFFFF;border: 3px solid black;">Guild</div>
            <div class="col s6 m2" style="background-color: #FFFFFF; color: #000000;border: 3px solid black;">[Empty]</div>
        </div>
        <?php
        return self::cleanCode(ob_get_clean());
    }
}
