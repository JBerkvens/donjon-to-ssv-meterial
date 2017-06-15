<form action="#" method="post" enctype="multipart/form-data">
    <input type="file" name="html_file"><br/>
    <input type="submit" value="Upload" name="submit">
</form>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'WizardawnConverter.php';
require_once 'tmp.php';
$map       = '';
$title     = '';
$ruler     = '';
$merchants = '';
$guilds    = '';
$guards    = '';
$churches  = '';
if (isset($_POST["submit"])) {
//
//    $url  = 'http://wizardawn.and-mag.com/tool_ftown.php?run=1';
//    $data = array(
//        'name'     => 'test',
//        'built'    => 'City',
//        'map_wide' => 1,
//        'map_high' => 1,
//        'rulers'   => 1,
//        'stores'   => 1,
//        'guilds'   => 1,
//        'police'   => 1,
//        'church'   => 1,
//        'water'    => 1,
//        'stock'    => 70,
//        'shelf'    => 1,
//    );
//
//    $options = array(
//        'http' => array(
//            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
//            'method'  => 'POST',
//            'content' => http_build_query($data),
//        ),
//    );
//    $context = stream_context_create($options);
//    $result  = file_get_contents($url, false, $context);

    $target_file = '/var/www/moridrin.com/tmp/' . basename($_FILES["html_file"]["name"]);
    move_uploaded_file($_FILES["html_file"]["tmp_name"], $target_file);
    $converted = WizardawnConverter::Convert(file_get_contents($target_file));
    $map       = $converted['map'];
    $title     = $converted['title'];
    $ruler     = $converted['ruler'];
    $merchants = $converted['merchants'];
    $guilds    = $converted['guilds'];
    $guards    = $converted['guards'];
    $churches  = $converted['churches'];
}
?>
Result<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <?= $map ?>
    <?= $title ?>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Ruler
            </div>
            <div class="collapsible-body">
                <?= $ruler ?>
            </div>
        </li>
        <li>
            <div class="collapsible-header">
                Merchants
            </div>
            <div class="collapsible-body">
                <?= $merchants ?>
            </div>
        </li>
        <li>
            <div class="collapsible-header">
                Guilds
            </div>
            <div class="collapsible-body">
                <?= $guilds ?>
            </div>
        </li>
        <li>
            <div class="collapsible-header">
                Guards
            </div>
            <div class="collapsible-body">
                <?= $guards ?>
            </div>
        </li>
        <li>
            <div class="collapsible-header">
                Churches
            </div>
            <div class="collapsible-body">
                <?= $churches ?>
            </div>
        </li>
    </ul>
</textarea><br/><br/>
<br/><br/>
Map<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <?= $map ?>
</textarea><br/><br/>
Title<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <?= $title ?>
</textarea><br/><br/>
Ruler<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Ruler
            </div>
            <div class="collapsible-body">
                <?= $ruler ?>
            </div>
        </li>
    </ul>
</textarea>
<br/><br/>
Merchants<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Merchants
            </div>
            <div class="collapsible-body">
                <?= $merchants ?>
            </div>
        </li>
    </ul>
</textarea>
<br/><br/>
Guilds<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Guilds
            </div>
            <div class="collapsible-body">
                <?= $guilds ?>
            </div>
        </li>
    </ul>
</textarea>
<br/><br/>
Guards<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Guards
            </div>
            <div class="collapsible-body">
                <?= $guards ?>
            </div>
        </li>
    </ul>
</textarea>
<br/><br/>
Churches<br/>
<textarea>
    <style>
        .collapsible-body p {
            padding: 0;
        }
    </style>
    <ul class="collapsible" id="test" data-collapsible="expandable">
        <li>
            <div class="collapsible-header">
                Churches
            </div>
            <div class="collapsible-body">
                <?= $churches ?>
            </div>
        </li>
    </ul>
</textarea>
<br/><br/>
