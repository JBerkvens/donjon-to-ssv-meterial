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
    public $wp_id;
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

    public function getHTML()
    {
        $id = uniqid('npc');
        ob_start();
        ?>
        <table style="position: relative; display: inline-block; border: 1px solid black; margin-right: 4px;">
            <tbody>
            <tr>
                <td><label>Save</label></td>
                <td>
                    <select name="npc___save[<?= $id ?>]" title="Save">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                    <button type="submit" name="save_single" value="<?= $id ?>">Save</button>
                </td>
            </tr>
            <tr>
                <td><label>spouse</label></td>
                <td>
                    <input type="text" name="npc___spouse[<?= $id ?>]" value="<?= $this->spouse ?>" title="spouse">
                </td>
            </tr>
            <tr>
                <td><label>children</label></td>
                <td>
                    <input type="text" name="npc___children[<?= $id ?>]" value="<?= implode(', ', $this->children) ?>" title="children">
                </td>
            </tr>
            <tr>
                <td><label>type</label></td>
                <td>
                    <input type="text" name="npc___type[<?= $id ?>]" value="<?= $this->type ?>" title="type">
                </td>
            </tr>
            <tr>
                <td><label>profession</label></td>
                <td>
                    <input type="text" name="npc___profession[<?= $id ?>]" value="<?= $this->profession ?>" title="profession">
                </td>
            </tr>
            <tr>
                <td><label>level</label></td>
                <td>
                    <input type="text" name="npc___level[<?= $id ?>]" value="<?= $this->level ?>" title="level">
                </td>
            </tr>
            <tr>
                <td><label>class</label></td>
                <td>
                    <input type="text" name="npc___class[<?= $id ?>]" value="<?= $this->class ?>" title="class">
                </td>
            </tr>
            <tr>
                <td><label>name</label></td>
                <td>
                    <input type="text" name="npc___name[<?= $id ?>]" value="<?= $this->name ?>" title="name">
                </td>
            </tr>
            <tr>
                <td><label>height</label></td>
                <td>
                    <input type="text" name="npc___height[<?= $id ?>]" value="<?= $this->height ?>" title="height">
                </td>
            </tr>
            <tr>
                <td><label>weight</label></td>
                <td>
                    <input type="text" name="npc___weight[<?= $id ?>]" value="<?= $this->weight ?>" title="weight">
                </td>
            </tr>
            <tr>
                <td><label>description</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___description[<?= $id ?>]" title="description"><?= $this->description ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>clothing</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___clothing[<?= $id ?>]" title="clothing"><?= $this->clothing ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>possessions</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___possessions[<?= $id ?>]" title="possessions"><?= $this->possessions ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>arms_armor</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___arms_armor[<?= $id ?>]" title="arms_armor"><?= $this->arms_armor ?></textarea>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    public static function getFromPOST($id)
    {
        $npc = new self();
        $fields = array(
            'spouse',
            'children',
            'type',
            'profession',
            'level',
            'class',
            'name',
            'height',
            'weight',
            'description',
            'clothing',
            'possessions',
            'arms_armor',
        );
        foreach ($fields as $field) {
            $value = $_POST['npc___'.$field][$id];
            if (!empty($_POST['npc___'.$field][$id])) {
                $npc->$field = $value;
            }
        }
        return $npc;
    }
}
