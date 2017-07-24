<?php

namespace Wizardawn\Models;

class Product extends JsonObject
{
    public $name;
    public $cost;
    public $inStock;

    public function __construct(string $name, string $cost, int $inStock)
    {
        parent::__construct();
        $this->name = $name;
        $this->cost = $cost;
        $this->inStock = $inStock;
    }
}
