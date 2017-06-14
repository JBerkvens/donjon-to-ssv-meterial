<?php

/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 14-6-17
 * Time: 7:15
 */
abstract class WizardawnConverter
{
    /** @var DOMDocument $file */
    private static $file;

    private static $map;
    private static $info;
    private static $rooms;

    public static function Convert($content)
    {
        self::$file = new DOMDocument();

        libxml_use_internal_errors(true);
        self::$file->loadHTML($content);

        mp_var_export(self::parseInfo(), 1);

//        self::parseMap();
//        self::parseInfo();
//        self::parseRooms();

        return array(
            'map'   => self::$map,
            'info'  => self::$info,
            'rooms' => self::$rooms,
        );
    }

    private static function parseMap()
    {
        $file = self::$file;
        $map = $file->getElementsByTagName('body')->item(0)->childNodes->item(0);
        self::$map = $file->saveHTML($map);
    }

    private static function parseInfo()
    {
        $file        = self::$file;
        foreach ($file->getElementsByTagName('hr') as $hr) {

        }
        return $file->saveHTML();
        $finder      = new DomXPath($file);
        $tableNode   = $finder->query("//*[contains(@class, 'stats standard')]")->item(0);
        $icon        = '';
        $name        = '';
        $content     = '';
        $collapsible = '<ul class="collapsible" id="test" data-collapsible="expandable">';
        /** @var DOMNode $trNode */
        foreach ($tableNode->childNodes as $trNode) {
            /** @var DOMNode $tdNode */
            foreach ($trNode->childNodes as $tdNode) {
                if ($tdNode->nodeType == XML_ELEMENT_NODE) {
                    if (empty($name)) {
                        if ($tdNode->firstChild->nodeType == 3) {
                            $name = trim($file->saveHTML($tdNode->firstChild));
                        } else {
                            /** @var DOMElement $firstChild */
                            $firstChild = $tdNode->firstChild;
                            $name       = 'room' . $firstChild->getAttribute('id');
                        }
                        switch ($name) {
                            case 'General':
                            default:
                                $icon = 'account_balance';
                                break;
                            case 'Wandering':
                                $icon = 'pets';
                                break;
                        }
                    } else {
                        $innerHTML = "";
                        foreach ($tdNode->childNodes as $child) {
                            $innerHTML .= trim($file->saveHTML($child));
                        }
                        $content = $innerHTML;
                    }
                }

                if (!empty($name) && !empty($content)) {
                    if ($name == 'General' || $name == 'Wandering') {
                        ob_start();
                        ?>
                        <li>
                            <div class="collapsible-header">
                                <i class="material-icons"><?= $icon ?></i><?= $name ?></div>
                            <div class="collapsible-body">
                                <?= $content ?>
                            </div>
                        </li>
                        <?php
                        $collapsible .= ob_get_clean();
                    }
                    $name    = '';
                    $content = '';
                }
            }
        }
        $collapsible .= '</ul>';
        self::$info  .= $collapsible;
    }

    private static function parseRooms()
    {
        $file      = self::$file;
        $finder    = new DomXPath($file);
        $tableNode = $finder->query("//*[contains(@class, 'stats standard')]")->item(0);
        $name      = '';
        $content   = '';
        /** @var DOMNode $trNode */
        foreach ($tableNode->childNodes as $trNode) {
            /** @var DOMNode $tdNode */
            foreach ($trNode->childNodes as $tdNode) {
                if ($tdNode->nodeType == XML_ELEMENT_NODE) {
                    if (empty($name)) {
                        if ($tdNode->firstChild->nodeType == 3) {
                            $name = trim($file->saveHTML($tdNode->firstChild));
                        } else {
                            $name = 'room' . $tdNode->firstChild->getAttribute('id');
                        }
                    } else {
                        $innerHTML = "";
                        foreach ($tdNode->childNodes as $child) {
                            $innerHTML .= trim($file->saveHTML($child));
                        }
                        $content = $innerHTML;
                    }
                }

                if (!empty($name) && !empty($content)) {
                    if ($name != 'General' && $name != 'Wandering') {
                        ob_start();
                        ?>
                        <div class="modal" id="<?= strtolower($name) ?>">
                            <div class="modal-content">
                                <?= $content ?>
                            </div>
                        </div>
                        <?php
                        self::$rooms .= ob_get_clean();
                    }
                    $name    = '';
                    $content = '';
                }
            }
        }
    }
}
