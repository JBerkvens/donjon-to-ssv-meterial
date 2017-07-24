<?php

namespace Wizardawn\Models;

use mp_dd\MP_DD;

class Building extends JsonObject
{
    public $label;
    protected $type;
    protected $title = null;
    /** @var NPC[] */
    protected $npcs = [];
    /** @var Product[] */
    protected $products = [];
    /** @var Spell[] */
    protected $spells = [];

    public function __construct(int $label, string $type)
    {
        parent::__construct();
        $this->label = $label;
        $this->type  = $type;
    }

    public function getID()
    {
        return $this->label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addNPC(NPC $npc, bool $overrideOwner = false)
    {
        if ($overrideOwner && !empty($this->npcs)) {
            $this->npcs[array_keys($this->npcs)[0]] = $npc;
        } else {
            $this->npcs[$npc->id] = $npc;
        }
    }

    public function getNPCs()
    {
        return $this->npcs;
    }

    public function setProducts(array $products)
    {
        $this->products = $products;
    }

    public function addProduct(Product $product)
    {
        $this->products[] = $product;
    }

    public function addSpell(Spell $spell)
    {
        $this->spells[] = $spell;
    }

    public function getSpells()
    {
        return $this->spells;
    }

    public function updateWith(Building $building)
    {
        if ($this->label != $building->label) {
            throw new \Exception("The Buildings have different Labels (indicating that they are different buildings)");
        }
        $this->type  = $building->type;
        $this->title = $building->title;
        foreach ($building->npcs as $npc) {
            if (!in_array($npc->name, array_column($this->npcs, 'name'))) {
                $this->npcs[] = $npc;
            }
        }
        foreach ($building->products as $product) {
            if (!in_array($product, $this->products)) {
                $this->products[] = $product;
            }
        }
        foreach ($building->spells as $spells) {
            if (!in_array($spells, $this->spells)) {
                $this->spells[] = $spells;
            }
        }

        return $this;
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
                    <input type="checkbox" name="building___save[]" value="<?= $this->id ?>" title="Save" checked>
                    <button name="save_single" value="<?= $this->id ?>" title="Save Single">Save <?= $this->title ?></button>
                </td>
            </tr>
            <tr>
                <td><label>Title</label></td>
                <td>
                    <input name="building___title[<?= $this->id ?>]" value="<?= $this->title ?>" title="Title">
                </td>
            </tr>
            <tr>
                <td><label>Label</label></td>
                <td>
                    <input name="building___label[<?= $this->id ?>]" value="<?= $this->label ?>" title="Label">
                </td>
            </tr>
            <tr>
                <td><label>NPCs</label></td>
                <td>
                    <select name="building___npcs[<?= $this->id ?>][]" title="NPCs" multiple>
                        <?php
                        global $wpdb;
                        $sql     = "SELECT ID, post_title FROM $wpdb->posts WHERE (post_type = 'area' OR post_type = 'npc' OR post_type = 'item') AND post_status = 'publish'";
                        $objects = $wpdb->get_results($sql);
                        foreach ($objects as $object) {
                            ?><option value="<?= $object->ID ?>" <?= in_array($object->ID, $this->npcs) ? 'selected' : '' ?>><?= $object->post_title ?></option><?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <td>
                <td><label>Products</label></td>
                <td>
                    <table>
                        <?php foreach ($this->products as $product): ?>
                            <tr>
                                <td><input name="building___products[<?= $this->id ?>][name][]" value="<?= $product->name ?>" title="NPCs"></td>
                                <td><input name="building___products[<?= $this->id ?>][cost][]" value="<?= $product->cost ?>" title="NPCs"></td>
                                <td><input name="building___products[<?= $this->id ?>][in_stock][]" value="<?= $product->inStock ?>" title="NPCs"></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>
            </tr>
            <td>
                <td><label>Spells</label></td>
                <td>
                    <table>
                        <?php foreach ($this->spells as $spell): ?>
                            <tr>
                                <td><input name="building___spells[<?= $this->id ?>][spell][]" value="<?= $spell->spell ?>" title="Spell name"></td>
                                <td><input name="building___spells[<?= $this->id ?>][cost][]" value="<?= $spell->cost ?>" title="Spell cost"></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td><label>Spells</label></td>
                <td>
                    <select name="building___spells[<?= $this->id ?>][]" title="NPCs" multiple>
                        <?php
                        global $wpdb;
                        $sql     = "SELECT ID, post_title FROM $wpdb->posts WHERE (post_type = 'area' OR post_type = 'npc' OR post_type = 'item') AND post_status = 'publish'";
                        $objects = $wpdb->get_results($sql);
                        foreach ($objects as $object) {
                            ?><option value="<?= $object->ID ?>" <?= in_array($object->ID, $this->npcs) ? 'selected' : '' ?>><?= $object->post_title ?></option><?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Type</label></td>
                <td>
                    <input name="building___type[<?= $this->id ?>]" value="<?= $this->type ?>" title="Type">
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    public static function getFromPOST($id, $unset = false)
    {
        $building = new self($_POST['building___label'][$id], $_POST['building___type'][$id]);
        $fields = [
            'title',
            'npcs',
            'products',
            'spells',
        ];
        foreach ($fields as $field) {
            $value = $_POST['building___'.$field][$id];
            if (!empty($_POST['building___'.$field][$id])) {
                $building->$field = $value;
            }
            if ($unset) {
                unset($_POST['building___'.$field][$id]);
            }
        }
        mp_var_export($building, true);
        return $building;
    }

    /**
     * @return int|\WP_Error
     */
    public function toWordPress()
    {
        $title = $this->title;
        $content = $this->description;

        /** @var \wpdb $wpdb */
        global $wpdb;
        $sql = "SELECT p.ID FROM $wpdb->posts AS p";
        $keysToCheck = ['height', 'weight'];
        foreach ($keysToCheck as $key) {
            $sql .= " LEFT JOIN $wpdb->postmeta AS pm_$key ON pm_$key.post_id = p.ID";
        }
        $sql .= " WHERE p.post_type = 'npc' AND p.post_title = '$title' AND p.post_content = '$content'";
        foreach ($keysToCheck as $key) {
            $value = $this->$key;
            $sql .= " AND pm_$key.meta_key = '$key' AND pm_$key.meta_value = '$value'";
        }
        /** @var \WP_Post $foundNPC */
        $foundNPC = $wpdb->get_row($sql);
        if ($foundNPC) {
            // The NPC has been found (not saving another instance but returning the found ID).
            return $foundNPC->ID;
        }

        $thisTypeTerm = term_exists(ucfirst($this->type), 'npc_type', 0);
        if (!$thisTypeTerm) {
            $thisTypeTerm = wp_insert_term(ucfirst($this->type), 'npc_type', ['parent' => 0]);
        }

        $custom_tax = [
            'npc_type' => [
                $thisTypeTerm['term_taxonomy_id'],
            ],
        ];

        $wp_id = wp_insert_post(
            [
                'post_title'   => $this->name,
                'post_content' => $this->description,
                'post_type'    => 'npc',
                'post_status'  => 'publish',
                'tax_input'    => $custom_tax,
            ]
        );
        foreach ($this as $key => $value) {
            if ($key == 'name' || $key == 'description' || $key == 'html') {
                continue;
            }
            update_post_meta($wp_id, $key, $value);
        }
        return $wp_id;
    }
}
