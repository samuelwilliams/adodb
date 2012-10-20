<?php

class ADODB_Iterator implements Iterator
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

    /**
     *
     */
    public function rewind()
    {
        $this->rs->MoveFirst();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !$this->rs->EOF;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->rs->_currentRow;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->rs->fields;
    }

    /**
     *
     */
    public function next()
    {
        $this->rs->MoveNext();
    }

    /**
     * @param $func
     * @param $params
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
        return !$this->rs->EOF;
    }
}