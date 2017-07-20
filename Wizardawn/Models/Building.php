<?php

namespace Wizardawn\Models;


use ssv_material_parser\NPC;

class Building
{
    private $id;
    private $type;
    private $title = null;
    private $npcs = [];
    private $other = [];

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

    public function addNPC(NPC $npc)
    {
        $this->npcs[] = $npc;
    }

    public function addOther(string $key, mixed $other)
    {
        $this->other[$key] = $other;
    }
}
