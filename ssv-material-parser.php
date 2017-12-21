<?php
/**
 * Plugin Name: SSV Material Parser
 * Plugin URI: http://moridrin.com/ssv-material-parser
 * Description: With this plugin you can parse generated HTML (from Wizardawn or from Donjon) to the SSV-Material theme.
 * Version: 1.0.0
 * Author: Jeroen Berkvens
 * Author URI: http://nl.linkedin.com/in/jberkvens/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/txt/copying/
 */

namespace ssv_material_parser;

use DOMDocument;

if (!defined('ABSPATH')) {
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SSV_MATERIAL_PARSER_PATH', plugin_dir_path(__FILE__));
define('SSV_MATERIAL_PARSER_URL', plugins_url() . '/ssv-material-parser/');

require_once 'functions.php';
require_once 'admin-page.php';
if (!class_exists('simple_html_dom_node')) {
    require_once 'include/simple_html_dom.php';
}
require_once 'ImageCombiner.php';

class Parser
{
        const PATH = SSV_MATERIAL_PARSER_PATH;
    const URL = SSV_MATERIAL_PARSER_URL;

    const REMOVE_HTML
        = array(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">',
            '<html>',
            '</html>',
            '<body>',
            '</body>',
            '&Atilde;',
            '&#130;',
            '&Acirc;',
            '&nbsp;',
            '&#13;',
        );
    
    /**
     * This function converts to a UTF-8 string, removes all redundant spaces, tabs, etc. and returns all usable code after the closing head tag.
     *
     * @param string $html
     *
     * @return string
     */
    protected static function cleanCode($html)
    {
        $html = str_replace(Parser::REMOVE_HTML, '', $html);
        $html = preg_replace('!\s+!', ' ', $html);
        $html = iconv("UTF-8", "UTF-8//IGNORE", utf8_decode($html));
        $html = str_replace('> <', '><', $html);
        $html = trim(preg_replace('/.*<\/head>/', '', $html));
        return $html;
    }

    /**
     * This function fixes some last issues such as image URLs, '<font>' blocks are replaced with '<span>' blocks, etc.
     *
     * @param string $part
     *
     * @return string
     */
    protected static function finalizePart($part)
    {
        $part = self::cleanCode($part);
        $file = new DOMDocument();
        libxml_use_internal_errors(true);
        $file->loadHTML($part);

        $images = $file->getElementsByTagName('img');
        foreach ($images as $image) {
            $imageStart = self::cleanCode($file->saveHTML($image));
            if (strpos($imageStart, 'wizardawn.and-mag.com') === false) {
                $imageNew = self::cleanCode(preg_replace('/.\/[\s\S]+?\//', 'http://wizardawn.and-mag.com/maps/', $imageStart));
                $part     = str_replace($imageStart, $imageNew, $part);
            }
        }
        $part = preg_replace("/<font.*?>(.*?)<\/font>/", "<span>$1</span>", $part);
        return self::cleanCode($part);
    }
}
