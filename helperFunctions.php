<?php

/**
 * Synonym for ADOLoadCode. Private function. Do not use.
 *
 * @deprecated
 */
function ADOLoadDB($dbType)
{
    return ADOLoadCode($dbType);
}

/**
 * Load the code for a specific database driver. Private function. Do not use.
 */
function ADOLoadCode($dbType)
{
    global $ADODB_LASTDB;

    if (!$dbType) return false;
    $db = strtolower($dbType);
    switch ($db) {
        case 'ado':
            if (PHP_VERSION >= 5) $db = 'ado5';
            $class = 'ado';
            break;
        case 'ifx':
        case 'maxsql':
            $class = $db = 'mysqlt';
            break;
        case 'postgres':
        case 'postgres8':
        case 'pgsql':
            $class = $db = 'postgres7';
            break;
        default:
            $class = $db;
            break;
    }

    $file = ADODB_DIR . "/drivers/adodb-" . $db . ".inc.php";
    @include_once($file);
    $ADODB_LASTDB = $class;
    if (class_exists("ADODB_" . $class)) return $class;

    //ADOConnection::outp(adodb_pr(get_declared_classes(),true));
    if (!file_exists($file)) ADOConnection::outp("Missing file: $file");
    else ADOConnection::outp("Syntax error in file: $file");
    return false;
}

/**
 * Instantiate a new Connection class for a specific database driver.
 *
 * @param [db]  is the database Connection object to create. If undefined,
 *     use the last database driver that was loaded by ADOLoadCode().
 *
 * @return ADOConnection the freshly created instance of the Connection class.
 */
function ADONewConnection($db = '')
{
    GLOBAL $ADODB_NEWCONNECTION, $ADODB_LASTDB;

    if (!defined('ADODB_ASSOC_CASE')) define('ADODB_ASSOC_CASE', 2);
    $errorfn = (defined('ADODB_ERROR_HANDLER')) ? ADODB_ERROR_HANDLER : false;
    $false = false;
    if (($at = strpos($db, '://')) !== FALSE) {
        $origdsn = $db;
        $fakedsn = 'fake' . substr($origdsn, $at);
        if (($at2 = strpos($origdsn, '@/')) !== FALSE) {
            // special handling of oracle, which might not have host
            $fakedsn = str_replace('@/', '@adodb-fakehost/', $fakedsn);
        }

        if ((strpos($origdsn, 'sqlite')) !== FALSE && stripos($origdsn, '%2F') === FALSE) {
            // special handling for SQLite, it only might have the path to the database file.
            // If you try to connect to a SQLite database using a dsn like 'sqlite:///path/to/database', the 'parse_url' php function
            // will throw you an exception with a message such as "unable to parse url"
            list($scheme, $path) = explode('://', $origdsn);
            $dsna['scheme'] = $scheme;
            if ($qmark = strpos($path, '?')) {
                $dsn['query'] = substr($path, $qmark + 1);
                $path = substr($path, 0, $qmark);
            }
            $dsna['path'] = '/' . urlencode($path);
        } else
            $dsna = @parse_url($fakedsn);

        if (!$dsna) {
            return $false;
        }
        $dsna['scheme'] = substr($origdsn, 0, $at);
        if ($at2 !== FALSE) {
            $dsna['host'] = '';
        }

        if (strncmp($origdsn, 'pdo', 3) == 0) {
            $sch = explode('_', $dsna['scheme']);
            if (sizeof($sch) > 1) {

                $dsna['host'] = isset($dsna['host']) ? rawurldecode($dsna['host']) : '';
                if ($sch[1] == 'sqlite')
                    $dsna['host'] = rawurlencode($sch[1] . ':' . rawurldecode($dsna['host']));
                else
                    $dsna['host'] = rawurlencode($sch[1] . ':host=' . rawurldecode($dsna['host']));
                $dsna['scheme'] = 'pdo';
            }
        }

        $db = @$dsna['scheme'];
        if (!$db) return $false;
        $dsna['host'] = isset($dsna['host']) ? rawurldecode($dsna['host']) : '';
        $dsna['user'] = isset($dsna['user']) ? rawurldecode($dsna['user']) : '';
        $dsna['pass'] = isset($dsna['pass']) ? rawurldecode($dsna['pass']) : '';
        $dsna['path'] = isset($dsna['path']) ? rawurldecode(substr($dsna['path'], 1)) : ''; # strip off initial /

        if (isset($dsna['query'])) {
            $opt1 = explode('&', $dsna['query']);
            foreach ($opt1 as $k => $v) {
                $arr = explode('=', $v);
                $opt[$arr[0]] = isset($arr[1]) ? rawurldecode($arr[1]) : 1;
            }
        } else $opt = array();
    }
    /*
     *  phptype: Database backend used in PHP (mysql, odbc etc.)
     *  dbsyntax: Database used with regards to SQL syntax etc.
     *  protocol: Communication protocol to use (tcp, unix etc.)
     *  hostspec: Host specification (hostname[:port])
     *  database: Database to use on the DBMS server
     *  username: User name for login
     *  password: Password for login
     */
    if (!empty($ADODB_NEWCONNECTION)) {
        $obj = $ADODB_NEWCONNECTION($db);

    }

    if (empty($obj)) {

        if (!isset($ADODB_LASTDB)) $ADODB_LASTDB = '';
        if (empty($db)) $db = $ADODB_LASTDB;

        if ($db != $ADODB_LASTDB) $db = ADOLoadCode($db);

        if (!$db) {
            if (isset($origdsn)) $db = $origdsn;
            if ($errorfn) {
                // raise an error
                $ignore = false;
                $errorfn('ADONewConnection', 'ADONewConnection', -998,
                    "could not load the database driver for '$db'",
                    $db, false, $ignore);
            } else
                ADOConnection::outp("<p>ADONewConnection: Unable to load database driver '$db'</p>", false);

            return $false;
        }

        $cls = 'ADODB_' . $db;
        if (!class_exists($cls)) {
            adodb_backtrace();
            return $false;
        }

        $obj = new $cls();
    }

    # constructor should not fail
    if ($obj) {
        if ($errorfn) $obj->raiseErrorFn = $errorfn;
        if (isset($dsna)) {
            if (isset($dsna['port'])) $obj->port = $dsna['port'];
            foreach ($opt as $k => $v) {
                switch (strtolower($k)) {
                    case 'new':
                        $nconnect = true;
                        $persist = true;
                        break;
                    case 'persist':
                    case 'persistent':
                        $persist = $v;
                        break;
                    case 'debug':
                        $obj->debug = (integer)$v;
                        break;
                    #ibase
                    case 'role':
                        $obj->role = $v;
                        break;
                    case 'dialect':
                        $obj->dialect = (integer)$v;
                        break;
                    case 'charset':
                        $obj->charset = $v;
                        $obj->charSet = $v;
                        break;
                    case 'buffers':
                        $obj->buffers = $v;
                        break;
                    case 'fetchmode':
                        $obj->SetFetchMode($v);
                        break;
                    #ado
                    case 'charpage':
                        $obj->charPage = $v;
                        break;
                    #mysql, mysqli
                    case 'clientflags':
                        $obj->clientFlags = $v;
                        break;
                    #mysql, mysqli, postgres
                    case 'port':
                        $obj->port = $v;
                        break;
                    #mysqli
                    case 'socket':
                        $obj->socket = $v;
                        break;
                    #oci8
                    case 'nls_date_format':
                        $obj->NLS_DATE_FORMAT = $v;
                        break;
                    case 'cachesecs':
                        $obj->cacheSecs = $v;
                        break;
                    case 'memcache':
                        $varr = explode(':', $v);
                        $vlen = sizeof($varr);
                        if ($vlen == 0) break;
                        $obj->memCache = true;
                        $obj->memCacheHost = explode(',', $varr[0]);
                        if ($vlen == 1) break;
                        $obj->memCachePort = $varr[1];
                        if ($vlen == 2) break;
                        $obj->memCacheCompress = $varr[2] ? true : false;
                        break;
                }
            }
            if (empty($persist))
                $ok = $obj->Connect($dsna['host'], $dsna['user'], $dsna['pass'], $dsna['path']);
            else if (empty($nconnect))
                $ok = $obj->PConnect($dsna['host'], $dsna['user'], $dsna['pass'], $dsna['path']);
            else
                $ok = $obj->NConnect($dsna['host'], $dsna['user'], $dsna['pass'], $dsna['path']);

            if (!$ok) return $false;
        }
    }
    return $obj;
}


// $perf == true means called by NewPerfMonitor(), otherwise for data dictionary
function _adodb_getdriver($provider, $drivername, $perf = false)
{
    switch ($provider) {
        case 'odbtp':
            if (strncmp('odbtp_', $drivername, 6) == 0) return substr($drivername, 6);
        case 'odbc' :
            if (strncmp('odbc_', $drivername, 5) == 0) return substr($drivername, 5);
        case 'ado'  :
            if (strncmp('ado_', $drivername, 4) == 0) return substr($drivername, 4);
        case 'native':
            break;
        default:
            return $provider;
    }

    switch ($drivername) {
        case 'mysqlt':
        case 'mysqli':
            $drivername = 'mysql';
            break;
        case 'postgres7':
        case 'postgres8':
            $drivername = 'postgres';
            break;
        case 'firebird15':
            $drivername = 'firebird';
            break;
        case 'oracle':
            $drivername = 'oci8';
            break;
        case 'access':
            if ($perf) $drivername = '';
            break;
        case 'db2'   :
            break;
        case 'sapdb' :
            break;
        default:
            $drivername = 'generic';
            break;
    }
    return $drivername;
}

function NewPerfMonitor(&$conn)
{
    $false = false;
    $drivername = _adodb_getdriver($conn->dataProvider, $conn->databaseType, true);
    if (!$drivername || $drivername == 'generic') return $false;
    include_once(ADODB_DIR . '/adodb-perf.inc.php');
    @include_once(ADODB_DIR . "/perf/perf-$drivername.inc.php");
    $class = "Perf_$drivername";
    if (!class_exists($class)) return $false;
    $perf = new $class($conn);

    return $perf;
}

function NewDataDictionary(&$conn, $drivername = false)
{
    $false = false;
    if (!$drivername) $drivername = _adodb_getdriver($conn->dataProvider, $conn->databaseType);

    include_once(ADODB_DIR . '/adodb-lib.inc.php');
    include_once(ADODB_DIR . '/adodb-datadict.inc.php');
    $path = ADODB_DIR . "/datadict/datadict-$drivername.inc.php";

    if (!file_exists($path)) {
        ADOConnection::outp("Dictionary driver '$path' not available");
        return $false;
    }
    include_once($path);
    $class = "ADODB2_$drivername";
    $dict = new $class();
    $dict->dataProvider = $conn->dataProvider;
    $dict->connection = $conn;
    $dict->upperName = strtoupper($drivername);
    $dict->quote = $conn->nameQuote;
    if (!empty($conn->_connectionID))
        $dict->serverInfo = $conn->ServerInfo();

    return $dict;
}


/*
    Perform a print_r, with pre tags for better formatting.
*/
function adodb_pr($var, $as_string = false)
{
    if ($as_string) ob_start();

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        echo " <pre>\n";
        print_r($var);
        echo "</pre>\n";
    } else
        print_r($var);

    if ($as_string) {
        $s = ob_get_contents();
        ob_end_clean();
        return $s;
    }
}

/*
    Perform a stack-crawl and pretty print it.

    @param printOrArr  Pass in a boolean to indicate print, or an $exception->trace array (assumes that print is true then).
    @param levels Number of levels to display
*/
function adodb_backtrace($printOrArr = true, $levels = 9999, $ishtml = null)
{
    global $ADODB_INCLUDED_LIB;
    if (empty($ADODB_INCLUDED_LIB)) include(ADODB_DIR . '/adodb-lib.inc.php');
    return _adodb_backtrace($printOrArr, $levels, 0, $ishtml);
}