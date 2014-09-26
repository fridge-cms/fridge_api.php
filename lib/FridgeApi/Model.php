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
                            $this->raw[$i]['value'] = $this->attrs[$part_name];
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

// private
    protected function is_part($val)
    {
        return isset($val[0]) && isset($val[0]['part_definition_id']);
    }

    protected function part_name($part)
    {
        return isset($part['part'])? $part['part']['name'] : $part['name'];
    }

    protected function part_value($part)
    {
        $value = isset($part['value'])? $part['value'] : null;

        if (!$value) return $value;

        if (isset($part['part'])) {
            if ($part['part']['type'] == "file" || $part['part']['type'] == "image") {
                $value = json_decode($value);
            }
        }

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
                } else {
                    $hash[$key] = array_map(function($v) {
                        return isset($v['id'])? new Model($v) : $v;
                    }, $value);
                }
                continue;
            }

            $hash[$key] = $value;
        }

        return $hash;
    }
}
