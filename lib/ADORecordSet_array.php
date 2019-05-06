<?php

/**
 * This class encapsulates the concept of a recordset created in memory
 * as an array. This is useful for the creation of cached recordsets.
 *
 * Note that the constructor is different from the standard ADORecordSet
 */
class ADORecordSet_array extends ADORecordSet
{
    public $databaseType = 'array';

    public $_array; // holds the 2-dimensional data array
    public $_types; // the array of types of each column (C B I L M)
    public $_colnames; // names of each column in array
    public $_skiprow1; // skip 1st row because it holds column names
    public $_fieldobjects; // holds array of field objects
    public $canSeek = true;
    public $affectedrows = false;
    public $insertid = false;
    public $sql = '';
    public $compat = false;

    /**
     * Constructor.
     */
    public function ADORecordSet_array($fakeid = 1)
    {
        global $ADODB_FETCH_MODE, $ADODB_COMPAT_FETCH;

        // fetch() on EOF does not delete $this->fields
        $this->compat = !empty($ADODB_COMPAT_FETCH);
        $this->ADORecordSet($fakeid); // fake queryID
        $this->fetchMode = $ADODB_FETCH_MODE;
    }

    public function _transpose($addfieldnames = true)
    {
        global $ADODB_INCLUDED_LIB;

        if (empty($ADODB_INCLUDED_LIB)) {
            include ADODB_DIR.'/adodb-lib.inc.php';
        }
        $hdr = true;

        $fobjs = $addfieldnames ? $this->_fieldobjects : false;
        adodb_transpose($this->_array, $newarr, $hdr, $fobjs);
        //adodb_pr($newarr);

        $this->_skiprow1 = false;
        $this->_array = $newarr;
        $this->_colnames = $hdr;

        adodb_probetypes($newarr, $this->_types);

        $this->_fieldobjects = array();

        foreach ($hdr as $k => $name) {
            $f = new ADOFieldObject();
            $f->name = $name;
            $f->type = $this->_types[$k];
            $f->max_length = -1;
            $this->_fieldobjects[] = $f;
        }
        $this->fields = reset($this->_array);

        $this->_initrs();
    }

    /**
     * Setup the array.
     *
     * @param array        is a 2-dimensional array holding the data.
     *            The first row should hold the column names
     *            unless paramter $colnames is used.
     * @param typearr    holds an array of types. These are the same types
     *            used in MetaTypes (C,B,L,I,N).
     * @param [colnames]    array of column names. If set, then the first row of
     *            $array should not hold the column names.
     */
    public function InitArray($array, $typearr, $colnames = false)
    {
        $this->_array = $array;
        $this->_types = $typearr;
        if ($colnames) {
            $this->_skiprow1 = false;
            $this->_colnames = $colnames;
        } else {
            $this->_skiprow1 = true;
            $this->_colnames = $array[0];
        }
        $this->Init();
    }

    /**
     * Setup the Array and datatype file objects.
     *
     * @param array        is a 2-dimensional array holding the data.
     *            The first row should hold the column names
     *            unless paramter $colnames is used.
     * @param fieldarr    holds an array of ADOFieldObject's.
     */
    public function InitArrayFields(&$array, &$fieldarr)
    {
        $this->_array = $array;
        $this->_skiprow1 = false;
        if ($fieldarr) {
            $this->_fieldobjects = $fieldarr;
        }
        $this->Init();
    }

    public function GetArray($nRows = -1)
    {
        if (-1 == $nRows && $this->_currentRow <= 0 && !$this->_skiprow1) {
            return $this->_array;
        } else {
            $arr = ADORecordSet::GetArray($nRows);

            return $arr;
        }
    }

    public function _initrs()
    {
        $this->_numOfRows = sizeof($this->_array);
        if ($this->_skiprow1) {
            --$this->_numOfRows;
        }

        $this->_numOfFields = (isset($this->_fieldobjects)) ?
                sizeof($this->_fieldobjects) : sizeof($this->_types);
    }

    /* Use associative array to get fields array */
    public function Fields($colname)
    {
        $mode = isset($this->adodbFetchMode) ? $this->adodbFetchMode : $this->fetchMode;

        if ($mode & ADODB_FETCH_ASSOC) {
            if (!isset($this->fields[$colname]) && !is_null($this->fields[$colname])) {
                $colname = strtolower($colname);
            }

            return $this->fields[$colname];
        }
        if (!$this->bind) {
            $this->bind = array();
            for ($i = 0; $i < $this->_numOfFields; ++$i) {
                $o = $this->FetchField($i);
                $this->bind[strtoupper($o->name)] = $i;
            }
        }

        return $this->fields[$this->bind[strtoupper($colname)]];
    }

    public function FetchField($fieldOffset = -1)
    {
        if (isset($this->_fieldobjects)) {
            return $this->_fieldobjects[$fieldOffset];
        }
        $o = new ADOFieldObject();
        $o->name = $this->_colnames[$fieldOffset];
        $o->type = $this->_types[$fieldOffset];
        $o->max_length = -1; // length not known

        return $o;
    }

    public function _seek($row)
    {
        if (sizeof($this->_array) && 0 <= $row && $row < $this->_numOfRows) {
            $this->_currentRow = $row;
            if ($this->_skiprow1) {
                ++$row;
            }
            $this->fields = $this->_array[$row];

            return true;
        }

        return false;
    }

    public function MoveNext()
    {
        if (!$this->EOF) {
            ++$this->_currentRow;

            $pos = $this->_currentRow;

            if ($this->_numOfRows <= $pos) {
                if (!$this->compat) {
                    $this->fields = false;
                }
            } else {
                if ($this->_skiprow1) {
                    ++$pos;
                }
                $this->fields = $this->_array[$pos];

                return true;
            }
            $this->EOF = true;
        }

        return false;
    }

    public function _fetch()
    {
        $pos = $this->_currentRow;

        if ($this->_numOfRows <= $pos) {
            if (!$this->compat) {
                $this->fields = false;
            }

            return false;
        }
        if ($this->_skiprow1) {
            ++$pos;
        }
        $this->fields = $this->_array[$pos];

        return true;
    }

    public function _close()
    {
        return true;
    }
} // ADORecordSet_array
