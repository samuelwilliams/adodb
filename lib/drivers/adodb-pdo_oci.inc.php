<?php

/*
V5.15 19 Jan 2012  (c) 2000-2012 John Lim (jlim#natsoft.com). All rights reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 8.

*/

class ADODB_pdo_oci extends ADODB_pdo_base
{
    public $concat_operator = '||';
    public $sysDate = 'TRUNC(SYSDATE)';
    public $sysTimeStamp = 'SYSDATE';
    public $NLS_DATE_FORMAT = 'YYYY-MM-DD';  // To include time, use 'RRRR-MM-DD HH24:MI:SS'
    public $random = 'abs(mod(DBMS_RANDOM.RANDOM,10000001)/10000000)';
    public $metaTablesSQL = "select table_name,table_type from cat where table_type in ('TABLE','VIEW')";
    public $metaColumnsSQL = "select cname,coltype,width, SCALE, PRECISION, NULLS, DEFAULTVAL from col where tname='%s' order by colno";

    public $_initdate = true;
    public $_hasdual = true;

    public function _init($parentDriver)
    {
        $parentDriver->_bindInputArray = true;
        $parentDriver->_nestedSQL = true;
        if ($this->_initdate) {
            $parentDriver->Execute("ALTER SESSION SET NLS_DATE_FORMAT='".$this->NLS_DATE_FORMAT."'");
        }
    }

    public function MetaTables($ttype = false, $showSchema = false, $mask = false)
    {
        if ($mask) {
            $save = $this->metaTablesSQL;
            $mask = $this->qstr(strtoupper($mask));
            $this->metaTablesSQL .= " AND table_name like $mask";
        }
        $ret = ADOConnection::MetaTables($ttype, $showSchema);

        if ($mask) {
            $this->metaTablesSQL = $save;
        }

        return $ret;
    }

    public function MetaColumns($table, $normalize = true)
    {
        global $ADODB_FETCH_MODE;

        $false = false;
        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        if (false !== $this->fetchMode) {
            $savem = $this->SetFetchMode(false);
        }

        $rs = $this->Execute(sprintf($this->metaColumnsSQL, strtoupper($table)));

        if (isset($savem)) {
            $this->SetFetchMode($savem);
        }
        $ADODB_FETCH_MODE = $save;
        if (!$rs) {
            return $false;
        }
        $retarr = array();
        while (!$rs->EOF) { //print_r($rs->fields);
            $fld = new ADOFieldObject();
            $fld->name = $rs->fields[0];
            $fld->type = $rs->fields[1];
            $fld->max_length = $rs->fields[2];
            $fld->scale = $rs->fields[3];
            if ('NUMBER' == $rs->fields[1] && 0 == $rs->fields[3]) {
                $fld->type = 'INT';
                $fld->max_length = $rs->fields[4];
            }
            $fld->not_null = (0 === strncmp($rs->fields[5], 'NOT', 3));
            $fld->binary = (false !== strpos($fld->type, 'BLOB'));
            $fld->default_value = $rs->fields[6];

            if (ADODB_FETCH_NUM == $ADODB_FETCH_MODE) {
                $retarr[] = $fld;
            } else {
                $retarr[strtoupper($fld->name)] = $fld;
            }
            $rs->MoveNext();
        }
        $rs->Close();
        if (empty($retarr)) {
            return  $false;
        } else {
            return $retarr;
        }
    }
}
