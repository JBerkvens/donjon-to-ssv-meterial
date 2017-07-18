<?php

namespace Wizardawn\Models;

class MapLabel
{
    private $id;
    private $left;
    private $top;

    public function __construct(int $id, int $left, int $top)
    {
        $this->id = $id;
        $this->left = $left;
        $this->top = $top;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
