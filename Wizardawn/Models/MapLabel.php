<?php

namespace Wizardawn\Models;

class MapLabel extends JsonObject
{
    protected $label;
    protected $left;
    protected $top;

    public function __construct(int $label, int $left, int $top)
    {
        parent::__construct();
        $this->label = $label;
        $this->left = $left;
        $this->top = $top;
    }
}
