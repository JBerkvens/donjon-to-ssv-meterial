<?php

namespace Wizardawn\Models;


use ssv_material_parser\NPC;

class City
{
    private $title = 'Test City';
    private $map = null;
    /** @var Building[] */
    private $buildings = [];
    /** @var NPC[] */
    private $npcs = [];
    private $other = [];

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setMap($map)
    {
        $this->map = $map;
    }

    public function addBuilding(Building $building)
    {
        $buildingID = $building->getID();
        if (isset($this->buildings[$buildingID])) {
            $this->buildings[$buildingID]->updateWith($building);
        } else {
            $this->buildings[$buildingID] = $building;
        }
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
