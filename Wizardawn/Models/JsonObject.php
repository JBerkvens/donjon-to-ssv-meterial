<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 22-7-17
 * Time: 15:24
 */

namespace Wizardawn\Models;

use ReflectionClass;

class JsonObject
{

    public $id;

    public function __construct()
    {
        $this->id = uniqid(strtolower((new ReflectionClass($this))->getShortName()) . '_');
    }

    public function toJSON()
    {
        $vars     = get_object_vars($this);
        $jsonVars = [];
        foreach ($vars as $var => $value) {
            if ($value instanceof JsonObject) {
                $array[$var] = $value->getID();
            } elseif (is_array($value)) {
                $array = [];
                foreach ($value as $key => $item) {
                    if ($item instanceof JsonObject) {
                        $array[$key] = $item->getID();
                    } else {
                        $array[$key] = $item;
                    }
                }
                $jsonVars[$var] = $array;
            } else {
                $jsonVars[$var] = $value;
            }
        }
        return json_encode($jsonVars);
    }

    public function fromJSON($json)
    {
        $vars   = json_decode($json);
        $object = new self();
        foreach ($vars as $var => $value) {
            $object->$var = $value;
        }
        return $object;
    }

    public function setID(string $id)
    {
        $this->id = $id;
    }

    public function getID()
    {
        return $this->id;
    }
}
