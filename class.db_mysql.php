<?php

/**
 *
 * DB Adapter - MySQL <http://www.sitedesign.hu>
 * Copyright (C) 2015 Krisztian CSANYI
 * 
 */

class DB_MySQL_Exception extends Exception {
    public $backtrace;

    public function __construct($message = FALSE, $code = FALSE) {
        if (!$message) {
            $this->message = mysql_error();
        }
        if (!$code) {
            $this->code = mysql_errno();
        }
        $this->backtrace = debug_backtrace();
    }
}

class DB_MySQL {
    private $_resource = "";
    private $_ticker = 0;
    private $_log = NULL;
    private $_cursor = NULL;
    private $_debug = FALSE;
    private $_exception = TRUE;

    public function debug() {
        return $this->_debug = TRUE;
    }

    public function getLog() {
        return $this->_log;
    }

    private function log($sql) {
        $this->_log[] = array(
            "ticker" => $this->_ticker,
            "query" => $sql,
            "numRows" => $this->numRows(),
            "affectedRows" => $this->affectedRows(),
            "insertID" => $this->insertID(),
            "errorNum" => $this->errorNum(),
            "errorMsg" => $this->errorMsg()
            );
    }

    private function error($msg = FALSE, $code = 0) {
        if ($this->_exception) {
            throw new DB_MySQL_Exception($msg, $node);
        }
    }

    public function errorNum() {
        return mysql_errno($this->_resource);
    }

    public function errorMsg() {
        return mysql_error($this->_resource);
    }

    public function numRows() {
        return $this->numRows;
        // return !is_null($this->_cursor) ? mysql_num_rows($this->_cursor) : 0;
    }

    public function numColumns() {
        return $this->numColumns;
        // return $this->_cursor ? mysql_num_fields($this->_cursor) : 0;
    }

    public function affectedRows() {
        return mysql_affected_rows($this->_resource);
    }

    public function insertID() {
        return mysql_insert_id($this->_resource);
    }

    public function __construct($host = "localhost", $user, $pass, $db = "") {
        if (!function_exists("mysql_connect")) {
            $this->error("MySQL is not supported by PHP");
        }

        if (phpversion() < "4.2.0") {
            if (!($this->_resource = @mysql_connect($host, $user, $pass))) {
                $this->error();
            }
        } else {
            if (!($this->_resource = @mysql_connect( $host, $user, $pass, TRUE))) {
                $this->error();
            }
        }

        if ($db != "" && !mysql_select_db($db, $this->_resource)) {
            $this->error();
        }
        $this->_ticker = 0;
        $this->_log = array();
    }

    private function execute($sql) {
        $this->_cursor = mysql_query($sql, $this->_resource);

        if (!$this->_cursor) {
            $this->error();
        } else {
            $this->numRows = mysql_num_rows($this->_cursor);
            $this->numColumns = mysql_num_fields($this->_cursor);
        }
        return $this->_cursor;
    }

    private function realescape($s) {
        return mysql_real_escape_string($s, $this->_resource);
    }

    public function query() {
        $pc = func_num_args();
        if ($pc == 0) return NULL;
        $sql = func_get_arg(0);
        if ($pc > 1) {
            $args = func_get_args();
            for ($i=1; $i<$pc; $i++) {
                $args[$i] = $this->realescape($args[$i]);
            }
            $sql = call_user_func_array("sprintf", $args);
        }

        $this->execute($sql);
        $this->_ticker++;

        if ($this->_debug) {
            $this->log($sql);
        }

        // TODO: here cmes the fetch part -> separate function
        $a = array();
        if (is_resource($this->_cursor)) {
            while ($row = mysql_fetch_object($this->_cursor)) {
                $a[] = $row;
            }

            mysql_free_result($this->_cursor);
        }
        return $a;
    }

    public function beginTransaction() {
        return (bool) mysql_query("BEGIN WORK");
    }

    public function commit() {
        return (bool) mysql_query("COMMIT");
    }

    public function rollback() {
        return (bool) mysql_query("ROLLBACK");
    }

    public function close() {
        return mysql_close($this->_resource);
    }

}

?>
