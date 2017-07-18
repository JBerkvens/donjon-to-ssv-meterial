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
use simple_html_dom;
use simple_html_dom_node;
use Wizardawn\Models\Map;
use Wizardawn\Models\MapLabel;
use Wizardawn\Models\MapPanel;

class MapParser extends Parser
{
    private function __construct()
    {
    }

    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param simple_html_dom $body
     *
     * @return Map
     */
    public static function parseMap(simple_html_dom $body): Map
    {
        /** @var simple_html_dom_node $html */
        $html = $body->getElementById('myMap');
        if ($html === null) {
            return null;
        }
        $map   = new Map();
        $width = $html->getAttribute("style");
        preg_match('/width: (.*?)px/', $width, $width);
        $mapWidth = ($width[1] - 5) + 100;
        $map->setWidth($mapWidth);
        $srcImagePaths = [];
        /** @var simple_html_dom_node $panelElement */
        foreach ($html->children() as $panelElement) {
            self::parsePanel($map, $panelElement);
            /** @var simple_html_dom_node $image */
            $image    = $panelElement->getElementByTagName('img');
            preg_match('/\/[\s\S]+?\/([\s\S]+?)"/', (string)$image, $image);
            $srcImagePaths[] = 'http://wizardawn.and-mag.com/maps/'.$image[1];
        }
        $map->setImage(\ImageCombiner::convertToSingle($srcImagePaths, $mapWidth - 100));

        return $map;
    }

    private static function parsePanel(Map &$map, simple_html_dom_node $panelElement)
    {
        $style = $panelElement->getAttribute("style");
        preg_match("/top:([0-9]+)px/", $style, $topTranslation);
        $topTranslation = $topTranslation[1] - 10;
        preg_match("/left:([0-9]+)px/", $style, $leftTranslation);
        $leftTranslation = $leftTranslation[1] - 10;

        /** @var simple_html_dom_node[] $elements */
        $elements = $panelElement->getElementsByTagName('div');

        /** @var simple_html_dom_node $panelBuilding */
        foreach ($elements as $panelBuilding) {
            $panelBuildingNumber = $panelBuilding->text();
            if (is_numeric($panelBuildingNumber)) {
                $style = $panelBuilding->getAttribute("style");
                preg_match("/top:([0-9]+)px/", $style, $top);
                preg_match("/left:([0-9]+)px/", $style, $left);
                $map->addLabel(new MapLabel((string)$panelBuildingNumber, $left[1] + $leftTranslation, $top[1] + $topTranslation));
            }
        }
    }
}
