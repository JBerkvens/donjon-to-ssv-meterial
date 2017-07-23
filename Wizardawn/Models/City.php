<?php

namespace Wizardawn\Models;

class City extends JsonObject
{
    protected $title = 'Test City';
    /** @var Map */
    protected $map = null;
    /** @var Building[] */
    protected $buildings = [];

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setMap($map)
    {
        $this->map = $map;
    }

    public function getMap(): Map
    {
        return $this->map;
    }

    public function addBuilding(Building $building)
    {
        if ($oldBuilding = $this->getBuildingByLabel($building->label)) {
            $this->buildings[$oldBuilding->id] = $oldBuilding->updateWith($building);
        } else {
            $this->buildings[$building->id] = $building;
        }
    }

    public function getBuildings()
    {
        return $this->buildings;
    }

    /**
     * @param string $label
     *
     * @return null|Building
     */
    public function getBuildingByLabel(string $label)
    {
        foreach ($this->buildings as $building) {
            if ($building->label == $label) {
                return $building;
            }
        }
        return null;
    }

    public function getHTML()
    {
        ob_start();
        ?>
        <table style="position: relative; display: inline-block; border: 1px solid black; margin-right: 4px;">
            <tbody>
            <tr>
                <td><label>Save</label></td>
                <td>
                    <select name="save" title="Save">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Include Map</label></td>
                <td>
                    <select name="map" title="Include Map">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                    <input name="map" value="<?= $this->map->getID() ?>" title="Save Map">
                </td>
            </tr>
            <tr>
                <td><label>Map</label></td>
                <td>
                    <?= $this->map->getHTML(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    public function getHiddenField()
    {
        ob_start();
        ?>
        <input type="hidden" name="city" value='<?= $this->serialize() ?>'>
        <?php
        return ob_get_clean();
    }
}
