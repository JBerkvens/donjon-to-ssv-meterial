<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 5-7-17
 * Time: 20:49
 */

namespace ssv_material_parser;

class NPC
{
    public $id;
    public $wp_id;
    public $building_id;
    public $html = '';
    public $spouse = null;
    public $children = array();
    public $type = 'citizen';
    public $profession = '';
    public $level = 1;
    public $class = '';
    public $name = '';
    public $height;
    public $weight;
    public $description = '';
    public $clothing = '';
    public $possessions = '';
    public $arms_armor = '';

    public function __construct($id, $buildingID, $html)
    {
        $this->id          = $id;
        $this->building_id = $buildingID;
        $this->html        = $html;
    }

    public function getHTML()
    {
        ob_start();
        ?>
        <table style="position: relative; display: inline-block; border: 1px solid black; margin-right: 4px;">
            <tbody>
            <tr>
                <td><label>Save</label></td>
                <td>
                    <input type="hidden" name="save[]" value="false" title="Save">
                    <input type="checkbox" name="save[]" value="true" checked title="Save">
                </td>
            </tr>
            <tr>
                <td><label>id</label></td>
                <td>
                    <input type="text" name="id[]" value="<?= $this->id ?>" title="id">
                </td>
            </tr>
            <tr>
                <td><label>building_id</label></td>
                <td>
                    <input type="text" name="building_id[]" value="<?= $this->building_id ?>" title="building_id">
                </td>
            </tr>
            <tr>
                <td><label>spouse</label></td>
                <td>
                    <input type="text" name="spouse[]" value="<?= $this->spouse ?>" title="spouse">
                </td>
            </tr>
            <tr>
                <td><label>children</label></td>
                <td>
                    <input type="text" name="children[]" value="<?= implode(', ', $this->children) ?>" title="children">
                </td>
            </tr>
            <tr>
                <td><label>type</label></td>
                <td>
                    <input type="text" name="type[]" value="<?= $this->type ?>" title="type">
                </td>
            </tr>
            <tr>
                <td><label>profession</label></td>
                <td>
                    <input type="text" name="profession[]" value="<?= $this->profession ?>" title="profession">
                </td>
            </tr>
            <tr>
                <td><label>level</label></td>
                <td>
                    <input type="text" name="level[]" value="<?= $this->level ?>" title="level">
                </td>
            </tr>
            <tr>
                <td><label>class</label></td>
                <td>
                    <input type="text" name="class[]" value="<?= $this->class ?>" title="class">
                </td>
            </tr>
            <tr>
                <td><label>name</label></td>
                <td>
                    <input type="text" name="name[]" value="<?= $this->name ?>" title="name">
                </td>
            </tr>
            <tr>
                <td><label>height</label></td>
                <td>
                    <input type="text" name="height[]" value="<?= $this->height ?>" title="height">
                </td>
            </tr>
            <tr>
                <td><label>weight</label></td>
                <td>
                    <input type="text" name="weight[]" value="<?= $this->weight ?>" title="weight">
                </td>
            </tr>
            <tr>
                <td><label>description</label></td>
                <td>
                    <textarea style="width: 100%;" name="description[]" title="description"><?= $this->description ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>clothing</label></td>
                <td>
                    <textarea style="width: 100%;" name="clothing[]" title="clothing"><?= $this->clothing ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>possessions</label></td>
                <td>
                    <textarea style="width: 100%;" name="possessions[]" title="possessions"><?= $this->possessions ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>arms_armor</label></td>
                <td>
                    <textarea style="width: 100%;" name="arms_armor[]" title="arms_armor"><?= $this->arms_armor ?></textarea>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}
