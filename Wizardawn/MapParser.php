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
    private static $map = array('width' => 0, 'panels' => array());

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array
     */
    public static function parseMap($basePart)
    {
        $part = self::cleanCode($basePart);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $map   = $file->getElementById('myMap');
        $width = $map->getAttribute("style");
        preg_match('/width: (.*?)px/', $width, $width);
        self::$map['width'] = ($width[1] - 5) + 100;
        for ($i = 0; $i < $map->childNodes->length; $i++) {
            $panelElement = $map->childNodes->item($i);
            if ($panelElement instanceof DOMElement) {
                self::$map['panels'][] = self::parsePanel($panelElement);
            }
        }

        return self::$map;
    }

    /**
     * @param DOMElement $panelElement
     *
     * @return array panel
     */
    private static function parsePanel($panelElement)
    {
        $panel = array(
            'image'           => '',
            'building_labels' => array(),
        );
        /** @var DOMNodeList $elements */
        $elements = $panelElement->getElementsByTagName('div');
        for ($i = 0; $i < $elements->length; $i++) {
            $panelBuilding       = $elements->item($i);
            $panelBuildingNumber = $panelBuilding->childNodes->item(0);
            if ($panelBuildingNumber instanceof DOMText) {
                $image = $panelBuilding->parentNode->getElementsByTagName('img')->item(0);
                preg_match('/\/[\s\S]+?\/([\s\S]+?)"/', $image->ownerDocument->saveHTML($image), $image);
                $panel['image'] = $image[1];

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

    public static function toHTML($map)
    {
        $width = $map['width'];
        ob_start();
        ?>
        <div style="overflow: auto;">
            <div style="width: <?= $width ?>px;">
                <?php foreach ($map['panels'] as $panel): ?>
                    <div style="display: inline-block; position:relative; padding: 0;">
                        <img src="http://wizardawn.and-mag.com/maps/<?= $panel['image'] ?>">
                        <?php foreach ($panel['building_labels'] as $buildingLabel): ?>
                            <div style="position:absolute; top:<?= $buildingLabel['top'] ?>px; left:<?= $buildingLabel['left'] ?>px;">
                                <a href="#modal_<?= $buildingLabel['id'] ?>" style="color: #FFFFFF; background: rgba(0,0,0,0.75); height: 20px; width: 20px; text-align: center; display: block; border-radius: 20%;">
                                    <?= $buildingLabel['id'] ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return self::cleanCode(ob_get_clean());
    }
}
