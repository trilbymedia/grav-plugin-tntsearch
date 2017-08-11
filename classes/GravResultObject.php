<?php
namespace Grav\Plugin\TNTSearch;

class GravResultObject
{
    protected $items;
    protected $counter;

    public function __construct($items)
    {
        $this->counter = 0;
        $this->items   = $items;
    }
    public function fetch($options)
    {
        return $this->items[$this->counter++];
    }
}