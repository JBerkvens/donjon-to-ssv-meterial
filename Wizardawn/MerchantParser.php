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

class MerchantParser extends BuildingParser
{
    /**
     * This function parses the Map and adds links to the modals.
     *
     * @param string $basePart
     *
     * @return array of buildings
     */
    public static function parseMerchant($basePart)
    {
        self::parseBase($basePart);
        self::parseOwner();
        self::parseTable();
        return self::$buildings;
    }

    /**
     * This function parses the products table into an array and adding it to the building.
     */
    private static function parseTable()
    {
        foreach (self::$buildings as &$building) {
            /** @var DOMElement $html */
            $html     = $building['html'];
            $file     = $html->ownerDocument;
            $table    = $file->getElementsByTagName('tbody')->item(0);
            $products = array();
            for ($i = 1; $i < $table->childNodes->length; $i++) { // The first row is a header row and can be skipped.
                $row        = $table->childNodes->item($i);
                $item       = $row->childNodes->item(2)->firstChild->firstChild->textContent;
                $cost       = $row->childNodes->item(4)->firstChild->firstChild->textContent;
                $inStock    = $row->childNodes->item(6)->firstChild->firstChild->textContent;
                $products[] = array(
                    'item'     => $item,
                    'cost'     => $cost,
                    'in_stock' => $inStock,
                );
            }
            $building['products'] = $products;
        }
    }
}
