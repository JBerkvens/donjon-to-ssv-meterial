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
    public static function parseMap(simple_html_dom $body)
    {
        /** @var simple_html_dom_node $html */
        $html   = $body->getElementById('myMap');
        if ($html === null) {
            return null;
        }
        $map = new Map();
        $width = $html->getAttribute("style");
        preg_match('/width: (.*?)px/', $width, $width);
        $map->setWidth(($width[1] - 5) + 100);
        /** @var simple_html_dom_node $childNode */
        foreach ($html->children() as $panelElement) {
            $map->addPanel(self::parsePanel($panelElement));
        }

        return $map;
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
}
