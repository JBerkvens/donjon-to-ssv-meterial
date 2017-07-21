<?php

namespace Wizardawn\Models;

class Product
{
    private $name;
    private $cost;
    private $inStock;

    public function __construct(string $name, string $cost, int $inStock)
    {
        $this->name = $name;
        $this->cost = $cost;
        $this->inStock = $inStock;
    }
}
