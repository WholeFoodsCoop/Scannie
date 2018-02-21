<?php
class ValueContainer implements \Iterator
{
    protected $values = array();

    protected $iterator_position = 0;

    public function __construct()
    {
    }

    public function __get($name)
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            throw new \Exception("Unknown value {$name}");
        }
    }

    public function tryGet($name, $default='')
    {
        return isset($this->values[$name]) ? $this->values[$name] : $default;
    }

    public function setMany(array $values)
    {
        foreach ($values as $k => $v) {
            $this->values[$k] = $v;
        }
    }

    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->values[$name])) {
            unset($this->values[$name]);
        }
    }

    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function current()
    {
        $keys = array_keys($this->values);
        return $this->__get($keys[$this->iterator_position]);
    }

    public function key()
    {
        $keys = array_keys($this->values);
        return $keys[$this->iterator_position];
    }

    public function next()
    {
        $this->iterator_position++;
    }

    public function valid()
    {
        $keys = array_keys($this->values);
        return isset($keys[$this->iterator_position]);
    }

    public function rewind()
    {
        $this->iterator_position = 0;
    }
}

