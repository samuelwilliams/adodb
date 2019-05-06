<?php

/**
 * class for caching.
 */
class ADODB_Cache_File
{
    public $createdir = true; // requires creation of temp dirs

    public function ADODB_Cache_File()
    {
        global $ADODB_INCLUDED_CSV;
        if (empty($ADODB_INCLUDED_CSV)) {
            include_once ADODB_DIR.'/adodb-csvlib.inc.php';
        }
    }

    // write serialised recordset to cache item/file
    public function writecache($filename, $contents, $debug, $secs2cache)
    {
        return adodb_write_file($filename, $contents, $debug);
    }

    // load serialised recordset and unserialise it
    public function &readcache($filename, &$err, $secs2cache, $rsClass)
    {
        $rs = csv2rs($filename, $err, $secs2cache, $rsClass);

        return $rs;
    }

    // flush all items in cache
    public function flushall($debug = false)
    {
        global $ADODB_CACHE_DIR;

        $rez = false;

        if (strlen($ADODB_CACHE_DIR) > 1) {
            $rez = $this->_dirFlush($ADODB_CACHE_DIR);
            if ($debug) {
                ADOConnection::outp("flushall: $dir<br><pre>\n".$rez.'</pre>');
            }
        }

        return $rez;
    }

    // flush one file in cache
    public function flushcache($f, $debug = false)
    {
        if (!@unlink($f)) {
            if ($debug) {
                ADOConnection::outp("flushcache: failed for $f");
            }
        }
    }

    public function getdirname($hash)
    {
        global $ADODB_CACHE_DIR;
        if (!isset($this->notSafeMode)) {
            $this->notSafeMode = !ini_get('safe_mode');
        }

        return ($this->notSafeMode) ? $ADODB_CACHE_DIR.'/'.substr($hash, 0, 2) : $ADODB_CACHE_DIR;
    }

    // create temp directories
    public function createdir($hash, $debug)
    {
        $dir = $this->getdirname($hash);
        if ($this->notSafeMode && !file_exists($dir)) {
            $oldu = umask(0);
            if (!@mkdir($dir, 0771)) {
                if (!is_dir($dir) && $debug) {
                    ADOConnection::outp("Cannot create $dir");
                }
            }
            umask($oldu);
        }

        return $dir;
    }

    /**
     * Private function to erase all of the files and subdirectories in a directory.
     *
     * Just specify the directory, and tell it if you want to delete the directory or just clear it out.
     * Note: $kill_top_level is used internally in the function to flush subdirectories.
     */
    public function _dirFlush($dir, $kill_top_level = false)
    {
        if (!$dh = @opendir($dir)) {
            return;
        }

        while (($obj = readdir($dh))) {
            if ('.' == $obj || '..' == $obj) {
                continue;
            }
            $f = $dir.'/'.$obj;

            if (strpos($obj, '.cache')) {
                @unlink($f);
            }
            if (is_dir($f)) {
                $this->_dirFlush($f, true);
            }
        }
        if (true === $kill_top_level) {
            @rmdir($dir);
        }

        return true;
    }
}
