<?php

/**
 * Lightweight recordset when there are no records to be returned
 */
class ADORecordSet_empty implements IteratorAggregate
{
    /**
     * @var string
     */
    public $dataProvider = 'empty';
    /**
     * @var bool
     */
    public $databaseType = false;
    /**
     * @var bool
     */
    public $EOF = true;
    /**
     * @var int
     */
    public $_numOfRows = 0;
    /**
     * @var bool
     */
    public $fields = false;
    /**
     * @var bool
     */
    public $connection = false;

    /**
     * @return int
     */
    public function RowCount()
    {
        return 0;
    }

    /**
     * @return int
     */
    public function RecordCount()
    {
        return 0;
    }

    /**
     * @return int
     */
    public function PO_RecordCount()
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function Close()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function FetchRow()
    {
        return false;
    }

    /**
     * @return int
     */
    public function FieldCount()
    {
        return 0;
    }

    /**
     *
     */
    public function Init()
    {
    }

    /**
     * @return ADODB_Iterator_empty
     */
    public function getIterator()
    {
        return new ADODB_Iterator_empty($this);
    }
}