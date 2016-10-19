<?php
namespace FridgeApi;

class Model
{
    protected $attrs, $raw;

    public function __construct($json)
    {
        $this->raw = $json;
        $this->attrs = $this->parse();
    }

    public function attrs()
    {
        return $this->attrs;
    }

    public function raw()
    {
        return $this->raw;
    }

    public function __isset($prop)
    {
        return isset($this->attrs[$prop]);
    }

    public function __get($prop)
    {
        return isset($this->attrs[$prop])? $this->attrs[$prop] : null;
    }

    public function __set($prop, $val)
    {
        $this->attrs[$prop] = $val;
    }

    public function __debugInfo()
    {
        return $this->attrs();
    }

    public function commit()
    {
        foreach ($this->raw as $key => $value) {
            if (is_array($value) && !Util::is_assoc_array($value)) {
                if ($this->is_part($value)) {
                    foreach ($value as $i => $part) {
                        $part_name = $this->part_name($part);
                        if ($this->part_value($part) != $this->attrs[$part_name]) {
                            $this->raw[$key][$i]['value'] = $this->attrs[$part_name];
                        }
                    }
                    continue;
                }
            }

            if ($value != $this->attrs[$key]) $this->raw[$key] = $this->attrs[$key];
        }

        return $this->raw();
    }

    public function __toString()
    {
        return print_r($this->attrs(), true);
    }

    public static function new_from_type($type)
    {
        if (is_string($type)) $type = $this->get("types/{$type}");

        return new Model(array(
            'site_id' => $type->site_id,
            'document_definition_id' => $type->uuid,
            'content' => array_map(function ($part) {
                return array(
                    'part_definition_id' => $part->id,
                    'name' => $part->name
                );
            }, $type->parts)
        ));
    }

// private
    protected function is_part($val)
    {
        return isset($val[0]) && isset($val[0]['part_definition_id']);
    }

    protected function is_part_definition($val)
    {
        if (!isset($val[0])) return false;
        return isset($val[0]['definition_id']);
    }

    protected function part_name($part)
    {
        return isset($part['part'])? $part['part']['name'] : $part['name'];
    }

    protected function part_value($part)
    {
        $value = isset($part['value'])? $part['value'] : null;

        return $value;
    }

    protected function parse()
    {
        $hash = array();

        foreach ($this->raw as $key => $value) {
            if (is_array($value) && !Util::is_assoc_array($value)) {
                if ($this->is_part($value)) {
                    foreach ($value as $part) {
                        $hash[$this->part_name($part)] = $this->part_value($part);
                    }
                } elseif ($this->is_part_definition($value)) {
                    foreach ($value as $part) {
                        $hash[$this->part_name($part)] = $part;
                    }
                } else {
                    $hash[$key] = array_map(function($v) {
                        return isset($v['id'])? new Model($v) : $v;
                    }, $value);
                    continue;
                }
            }

            $hash[$key] = $value;
        }

        return $hash;
    }
}
