<?php

namespace Wizardawn\Models;


use ssv_material_parser\NPC;

class Building
{
    private $id;
    private $type;
    private $title = null;
    /** @var NPC[] */
    private $npcs = [];
    private $products = [];

    public function __construct(int $id, string $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function getID()
    {
        return $this->id;
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
        if ($overrideOwner) {
            $this->npcs[0] = $npc;
        } else {
            $this->npcs[] = $npc;
        }
    }

    public function setProducts(array $products)
    {
        $this->products = $products;
    }

    public function addProduct(Product $product)
    {
        $this->products[] = $product;
    }

    public function updateWith(Building $building) {
        if ($this->id != $building->id) {
            throw new \Exception("The ID's don't match.");
        }
        $this->type = $building->type;
        $this->title = $building->title;
        foreach ($building->npcs as $npc) {
            if (!in_array($npc, $this->npcs)) {
                $this->npcs[] = $npc;
            }
        }
        $this->products = $building->products;
    }
}
