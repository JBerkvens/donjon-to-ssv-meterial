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
    ?>
    Result<br/>
    <textarea title="Result" style="width: 100%; height: 500px;">
        <style>
            .collapsible-body p {
                padding: 0;
            }
        </style>
        <?= isset($converted['map']) ? $converted['map'] : '' ?>
        <ul class="collapsible" id="test" data-collapsible="expandable">
            <?php foreach ($converted as $name => $value): ?>
                <?php if ($name == 'map' || $name == 'title' || $name == 'houses'): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php if (!empty($value)): ?>
                    <li>
                        <div class="collapsible-header" style="line-height: initial; margin-top: 10px;">
                            <img src="/convert/images/<?= $name ?>.jpg">
                        </div>
                        <div class="collapsible-body">
                            <?= $value ?>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?= isset($converted['houses']) ? $converted['houses'] : '' ?>
    </textarea>
    <br/><br/>
    <?php foreach ($converted as $name => $value): ?>
        <br/><br/>
        <?= mp_to_title($name) ?><br/>
        <textarea title="<?= $name ?>" style="width: 100%; height: 100px;">
            <style>
                .collapsible-body p {
                    padding: 0;
                }
            </style>
            <?= $value ?>
        </textarea>
    <?php endforeach; ?>
    <?php
}
