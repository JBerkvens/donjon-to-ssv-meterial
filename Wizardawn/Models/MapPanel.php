<?php

namespace Wizardawn\Models;

class MapPanel
{
    private $imageURL;
    /** @var MapLabel[] */
    private $labels = [];

    public function __construct($imageURL)
    {
        $this->imageURL = $imageURL;
    }

    public function addLabel(MapLabel $label)
    {
        $this->labels[] = $label;
    }
}
