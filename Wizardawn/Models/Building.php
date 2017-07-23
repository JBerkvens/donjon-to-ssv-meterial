<?php

namespace Wizardawn\Models;

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
}
