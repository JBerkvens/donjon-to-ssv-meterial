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

if (!defined('ABSPATH')) {
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SSV_MATERIAL_PARSER_PATH', plugin_dir_path(__FILE__));
define('SSV_MATERIAL_PARSER_URL', plugins_url() . '/ssv-material-parser/');

#region Require Once
require_once 'functions.php';
require_once 'admin-page.php';
#endregion

#region SSV_Users class
class Parser
{
    #region Constants
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
    #endregion
}
#endregion
