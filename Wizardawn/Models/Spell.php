<?php

namespace Wizardawn\Models;


class Spell extends JsonObject
{
    protected $spell;
    protected $cost;

    public function __construct(string $spell, string $cost)
    {
        parent::__construct();
        $this->spell = $spell;
        $this->cost = $cost;
    }
}
