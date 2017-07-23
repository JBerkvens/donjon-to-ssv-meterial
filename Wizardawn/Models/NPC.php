<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 5-7-17
 * Time: 20:49
 */

namespace Wizardawn\Models;

class NPC extends JsonObject
{
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
        ob_start();
        ?>
        <table style="position: relative; display: inline-block; border: 1px solid black; margin-right: 4px;">
            <tbody>
            <tr>
                <td><label>Save</label></td>
                <td>
                    <input type="checkbox" name="npc___save[]" value="<?= $this->id ?>" title="Save" checked>
                </td>
            </tr>
            <tr>
                <td><label>type</label></td>
                <td>
                    <input name="npc___type[<?= $this->id ?>]" value="<?= $this->type ?>" title="type">
                </td>
            </tr>
            <tr>
                <td><label>profession</label></td>
                <td>
                    <input name="npc___profession[<?= $this->id ?>]" value="<?= $this->profession ?>" title="profession">
                </td>
            </tr>
            <tr>
                <td><label>level</label></td>
                <td>
                    <input name="npc___level[<?= $this->id ?>]" value="<?= $this->level ?>" title="level">
                </td>
            </tr>
            <tr>
                <td><label>class</label></td>
                <td>
                    <input name="npc___class[<?= $this->id ?>]" value="<?= $this->class ?>" title="class">
                </td>
            </tr>
            <tr>
                <td><label>name</label></td>
                <td>
                    <input name="npc___name[<?= $this->id ?>]" value="<?= $this->name ?>" title="name">
                </td>
            </tr>
            <tr>
                <td><label>height</label></td>
                <td>
                    <input name="npc___height[<?= $this->id ?>]" value="<?= $this->height ?>" title="height">
                </td>
            </tr>
            <tr>
                <td><label>weight</label></td>
                <td>
                    <input name="npc___weight[<?= $this->id ?>]" value="<?= $this->weight ?>" title="weight">
                </td>
            </tr>
            <tr>
                <td><label>description</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___description[<?= $this->id ?>]" title="description"><?= $this->description ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>clothing</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___clothing[<?= $this->id ?>]" title="clothing"><?= $this->clothing ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>possessions</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___possessions[<?= $this->id ?>]" title="possessions"><?= $this->possessions ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label>arms_armor</label></td>
                <td>
                    <textarea style="width: 100%;" name="npc___arms_armor[<?= $this->id ?>]" title="arms_armor"><?= $this->arms_armor ?></textarea>
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
