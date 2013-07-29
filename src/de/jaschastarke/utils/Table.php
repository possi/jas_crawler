<?php

namespace de\jaschastarke\utils;
#use de\jaschastarke\Exception;
use \Exception;

class Table implements \Iterator, \Countable {
    protected $headers = array();
    protected $data = array();
    protected $_columns = null;
    protected $_tmp_cols = null;

    public function __construct() {

    }
    public function add($row) {
        $this->data[] = $this->dataRow($row);
    }

    protected function labelRow($row) {
        $c = $this->_getCols();
        $ret = array();
        foreach ($c as $idx => $head) {
            $ret[$head] = isset($row[$idx]) ? $row[$idx] : null;
        }
        return $ret;
    }
    protected function orderedRow($row) {
        $c = $this->_getCols();
        $ret = array();
        foreach ($c as $idx => $head) {
            $ret[] = isset($row[$idx]) ? $row[$idx] : null;
        }
        return $ret;
    }
    protected function dataRow($row) {
        $r = array();
        foreach ($row as $head => $val) {
            $idx = array_search($head, $this->headers);
            if ($idx === false) {
                $this->headers[] = $head;
                $idx = array_search($head, $this->headers);
                $this->_tmp_cols = null;
            }
            $r[$idx] = $val;
        }
        return $r;
    }
    public function setRow($search, $row) {
        $line = null;
        $s = $this->dataRow($search);
        foreach ($this->data as $idx => &$_r) {
            if (self::matches($_r, $s)) {
                $line = $idx;
                break;
            }
        }
        if ($line === null) {
            $this->add(array_merge($search, $row));
        } else {
            $row = $this->dataRow($row);
            foreach ($row as $idx => $val) {
                $this->data[$line][$idx] = $val;
            }
        }
    }

    protected static function matches($row, $search) {
        if (empty($search))
            throw new Exception("empty search criterias");
        foreach ($search as $c => $v) {
            if (!isset($row[$c]) || $row[$c] != $v)
                return false;
        }
        return true;
    }

    /**
     * @param $cols array - null for auto
     */
    public function setColumns(array $cols = null) {
        $this->_columns = $cols;
        $this->_tmp_cols = null;
    }

    public function _getCols() {
        if ($this->_columns !== null) {
            if (!$this->_tmp_cols) {
                $this->_tmp_cols = array();
                foreach ($this->_columns as $c) {
                    $idx = array_search($c, $this->headers);
                    if ($idx === false)
                        $idx = uniqid('_');
                    $this->_tmp_cols[$idx] = $c;
                }
            }
            return $this->_tmp_cols;
        } else {
            return $this->headers;
        }
    }

    public function getColumns() {
        return array_values($this->_getCols());
    }

    public function toArray($labeled = true) {
        $output = array();
        foreach ($this->data as &$row) {
            $output[] = $labeled ? $this->labelRow($row) : $this->orderedRow($row);
        }
        return $output;
    }

    public function toCSV($del = ',', $encl = '"') {
        $f = fopen('php://memory', 'w+');
        $this->writeCSV($f, $del, $encl);
        rewind($f);
        return stream_get_contents($f);
    }
    public function writeCSV($file, $del = ',', $encl = '"') {
        $res = is_resource($file);
        if (!$res)
            $file = fopen($file, 'w');
        fputcsv($file, $this->getColumns(), $del, $encl);
        foreach ($this as $row) {
            fputcsv($file, $row, $del, $encl);
        }
        if (!$res)
            fclose($file);
    }
    public function readCSV($file, $del = ',', $encl = '"') {
        $res = is_resource($file);
        if (!$res) {
            if (!file_exists($file))
                return null;
            $file = fopen($file, 'r');
        }
        $head = fgetcsv($file, 0, $del, $encl);
        while ($row = fgetcsv($file, 0, $del, $encl)) {
            $this->add(array_combine($head, $row));
        }
        if (!$res)
            fclose($file);
    }

    /**
     * Iterator
     */
    public function current() {
        $row = current($this->data);
        return $this->labelRow($row);
    }
    public function key() {
        return key($this->data);
    }
    public function next() {
        return next($this->data) ? $this->current() : false;
    }
    public function rewind() {
        return reset($this->data);
    }
    public function valid() {
        return $this->key() !== null;
    }

    public function count() {
        return count($this->data);
    }
}

