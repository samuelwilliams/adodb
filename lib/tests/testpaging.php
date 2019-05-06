<?php
/*
V4.80 8 Mar 2006  (c) 2000-2012 John Lim (jlim#natsoft.com). All rights reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net
*/

error_reporting(E_ALL);

include_once '../adodb.inc.php';
include_once '../adodb-pager.inc.php';

$driver = 'oci8';
$sql = 'select  ID, firstname as "First Name", lastname as "Last Name" from adoxyz  order  by  id';
//$sql = 'select count(*),firstname from adoxyz group by firstname order by 2 ';
//$sql = 'select distinct firstname, lastname from adoxyz  order  by  firstname';

if ('postgres' == $driver) {
    $db = NewADOConnection('postgres');
    $db->PConnect('localhost', 'tester', 'test', 'test');
}

if ('access' == $driver) {
    $db = NewADOConnection('access');
    $db->PConnect('nwind', '', '', '');
}

if ('ibase' == $driver) {
    $db = NewADOConnection('ibase');
    $db->PConnect('localhost:e:\\firebird\\examples\\employee.gdb', 'sysdba', 'masterkey', '');
    $sql = 'select distinct firstname, lastname  from adoxyz  order  by  firstname';
}
if ('mssql' == $driver) {
    $db = NewADOConnection('mssql');
    $db->Connect('JAGUAR\vsdotnet', 'adodb', 'natsoft', 'northwind');
}
if ('oci8' == $driver) {
    $db = NewADOConnection('oci8');
    $db->Connect('', 'scott', 'natsoft');

    $sql = 'select * from (select  ID, firstname as "First Name", lastname as "Last Name" from adoxyz 
	 order  by  1)';
}

if ('access' == $driver) {
    $db = NewADOConnection('access');
    $db->Connect('nwind');
}

if (empty($driver) or 'mysql' == $driver) {
    $db = NewADOConnection('mysql');
    $db->Connect('localhost', 'root', '', 'test');
}

//$db->pageExecuteCountRows = false;

$db->debug = true;

if (0) {
    $rs = $db->Execute($sql);
    include_once '../toexport.inc.php';
    echo '<pre>';
    echo rs2csv($rs); // return a string

    echo '<hr />';
    $rs->MoveFirst(); // note, some databases do not support MoveFirst
echo rs2tab($rs); // return a string

echo '<hr />';
    $rs->MoveFirst();
    rs2tabout($rs); // send to stdout directly
    echo '</pre>';
}

$pager = new ADODB_Pager($db, $sql);
$pager->showPageLinks = true;
$pager->linksPerPage = 10;
$pager->cache = 60;
$pager->Render($rows = 7);
