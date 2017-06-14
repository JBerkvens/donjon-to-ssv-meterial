<?php


/**
 * This function is for development purposes only and lets the developer print a variable in the PHP formatting to inspect what the variable is set to.
 *
 * @param mixed $variable any variable that you want to be printed.
 * @param bool  $die      set true if you want to call die() after the print. $die is ignored if $return is true.
 * @param bool  $return   set true if you want to return the print as string.
 * @param bool  $newline  set false if you don't want to print a newline at the end of the print.
 *
 * @return mixed|null|string returns the print in string if $return is true, returns null if $return is false, and doesn't return if $die is true.
 */
function mp_var_export($variable, $die = false, $return = false, $newline = true)
{
    if (mp_has_circular_reference($variable)) {
        ob_start();
        var_dump($variable);
        $var_dump = ob_get_clean();
        $print    = highlight_string("<?php " . $var_dump, true);
    } else {
        $print = highlight_string("<?php " . var_export($variable, true), true);
    }
    $print = trim($print);
    $print = preg_replace("|^\\<code\\>\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>|", "", $print, 1);  // remove prefix
    $print = preg_replace("|\\</code\\>\$|", "", $print, 1);
    $print = trim($print);
    $print = preg_replace("|\\</span\\>\$|", "", $print, 1);
    $print = trim($print);
    $print = preg_replace("|^(\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>)(&lt;\\?php&nbsp;)(.*?)(\\</span\\>)|", "\$1\$3\$4", $print);
    $print .= ';';
    if ($return) {
        return $print;
    } else {
        echo $print;
        if ($newline) {
            echo '<br/>';
        }
    }

    if ($die) {
        die();
    }
    return null;
}

/**
 * This function checks if the given $variable is recursive.
 *
 * @param mixed $variable is the variable to be checked.
 *
 * @return bool true if the $variable contains circular reference.
 */
function mp_has_circular_reference($variable)
{
    $dump = print_r($variable, true);
    if (strpos($dump, '*RECURSION*') !== false) {
        return true;
    } else {
        return false;
    }
}