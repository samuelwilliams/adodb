<?php

class ADODB_Iterator_empty implements Iterator
{
    /**
     * @var
     */
    private $rs;

    /**
     * @param $rs
     */
    public function __construct($rs)
    {
        $this->rs = $rs;
    }

    public function rewind()
    {
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !$this->rs->EOF;
    }

    /**
     * @return bool
     */
    public function key()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function current()
    {
        return false;
    }

    public function next()
    {
    }

    /**
     * @param $func
     * @param $params
     *
     * @return mixed
     */
    public function __call($func, $params)
    {
        return call_user_func_array(array($this->rs, $func), $params);
    }

    /**
     * @return bool
     */
    public function hasMore()
    {
        return false;
    }
}
