<?php

//defined('BASEPATH') OR exit('No direct script access allowed');

class DB {
    
    public $debug = false;
    
    private $_username = '';
    private $_password = '';
    private $_servername = '127.0.0.1';
    private $_dbname = '';
    
    protected $qb_select    = array();
    protected $qb_from      = array();
    protected $qb_join      = array();
    protected $qb_where     = array();
    protected $qb_limit     = FALSE;
    protected $qb_offset    = FALSE;
    protected $qb_orderby   = array();
    protected $qb_set       = array();
    private $_result        = null;    // associative query result
    
    private $_con = null; // connection handler
    

    public function reset() {
        $this->qb_select = array();
        $this->qb_from = array();
        $this->qb_join = array();
        $this->qb_where = array();
        $this->qb_limit = FALSE;
        $this->qb_offset = FALSE;
        $this->qb_orderby = array();
        $this->qb_set = array();
        //$this->_result = null;
        return $this;
    }

    public function __construct() {

        $this->readIniFile();
        $this->_con = mysqli_connect($this->_servername, $this->_username, $this->_password, $this->_dbname);        
        
        if (!$this->_con) {
            die("Could not connect to mysql server");
        }
        
        mysqli_set_charset($this->_con, "utf8");
    }
    
    public function __destruct() {
        
        if ($this->_con) {
            mysqli_close($this->_con);
        }
    }
    
    private function readIniFile() {

        $handle = fopen("core/db_conf.ini", "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                
                $k = null;
                $v = null;

                $r = sscanf($line, "%s : %s", $k, $v);
                
                if ($r != 2) {
                    continue;
                }
                
                if ($k === 'username') {
                    $this->_username = $v;
                }
                elseif ($k == 'password') {
                    $this->_password = $v;
                }
                elseif ($k == 'servername') {
                    $this->_servername = $v;
                }
                elseif ($k == 'dbname') {
                    $this->_dbname = $v;
                }
            }

            fclose($handle);
            
        } else {
            // error opening the file.
            echo "Error";
        }
    }
    
    // @return:  associative array
    public function result() {

        return $this->_result;
    }

    // @return: integer
    public function num_rows() {

        if (!$this->_result) {
            return 0;
        }
        return count($this->_result);
    }

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param	string	the table to insert data into
     * @param	array	an associative array of insert values
     * @return	$this
     */
    public function insert($table = "", $set = NULL) {
        
        if ($set !== NULL) {
            $this->set($set, '');
        }
        
        foreach ($this->qb_set as $k => $v) {
            $keys[] = $k;
            $values[] = "'" . mysqli_real_escape_string($this->_con, $v) . "'";
        }
        
        $sql = 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values) . ')';

        $r = $this->query($sql);                        
        
        $this->freeResult($r);
        $this->reset();
        
        return $this;
    }
    
    // @return:  $this
    public function select($select = '*') {
        
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        foreach ($select as $val) {
            $val = trim($val);

            if ($val !== '') {
                $this->qb_select[] = $val;
            }
        }

        return $this;
    }
    
    // @return:  $this
    public function from($from) {
        
        foreach ((array) $from as $val) {
            if (strpos($val, ',') !== FALSE) {
                foreach (explode(',', $val) as $v) {
                    $v = trim($v);
                    $this->qb_from[] = $v;
                }
            } else {
                $val = trim($val);
                $this->qb_from[] = $val;
            }
        }

        return $this;
    }
    
    /**
     * JOIN
     *
     * Generates the JOIN portion of the query
     *
     * @param	string
     * @param	string	the join condition
     * @param	string	the type of join
     * @return	CI_DB_query_builder
     */
    public function join($table, $cond, $type = '') {
        
        if ($type !== '') {
            $type = strtoupper(trim($type));

            if (!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), TRUE)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }


        if (!$this->_has_operator($cond)) {
            $cond = ' USING (' . $cond . ')';
        } else {
            $cond = ' ON ' . $cond;
        }

        // Assemble the JOIN statement
        $this->qb_join[] = $join = $type . 'JOIN ' . $table . $cond;


        return $this;
    }

    /**
     * WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param	mixed
     * @param	mixed
     * @return	$this
     */
    public function where($key, $value = NULL) {
        return $this->_wh('qb_where', $key, $value, 'AND ');
    }

    /**
     * WHERE, HAVING
     *
     * @used-by	where()
     * @used-by	or_where()
     * @used-by	having()
     * @used-by	or_having()
     *
     * @param	string	$qb_key	'qb_where' or 'qb_having'
     * @param	mixed	$key
     * @param	mixed	$value
     * @param	string	$type
     * @return	$this
     */   
    protected function _wh($qb_key, $key, $value = NULL, $type = 'AND ') {

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            $prefix = (count($this->$qb_key) === 0) ? '' : $type;
            
            $v = "'" . mysqli_real_escape_string($this->_con, $v) . "'";

            if ($v !== NULL) {

                if (!$this->_has_operator($k)) {
                    $k .= ' = ';
                }
            } elseif (!$this->_has_operator($k)) {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            } elseif (preg_match('/\s*(!?=|<>|IS(?:\s+NOT)?)\s*$/i', $k, $match, PREG_OFFSET_CAPTURE)) {
                $k = substr($k, 0, $match[0][1]) . ($match[1][0] === '=' ? ' IS NULL' : ' IS NOT NULL');
            }

            $this->{$qb_key}[] = array('condition' => $prefix . $k . $v);
        }

        return $this;
    }
    
    /**
     * ORDER BY
     *
     * @param	string	$orderby
     * @param	string	$direction	ASC, DESC or RANDOM
     * @return	$this
     */
    public function order_by($orderby, $direction = '') {

        $direction = strtoupper(trim($direction));

        if (empty($orderby)) {
            return $this;
        } elseif ($direction !== '') {
            $direction = in_array($direction, array('ASC', 'DESC'), TRUE) ? ' ' . $direction : '';
        }

//        if ($escape === FALSE) {
//            $qb_orderby[] = array('field' => $orderby, 'direction' => $direction, 'escape' => FALSE);
//        } else {
            $qb_orderby = array();
            foreach (explode(',', $orderby) as $field) {
                $qb_orderby[] = ($direction === '' && preg_match('/\s+(ASC|DESC)$/i', rtrim($field), $match, PREG_OFFSET_CAPTURE)) ? array('field' => ltrim(substr($field, 0, $match[0][1])), 'direction' => ' ' . $match[1][0], 'escape' => TRUE) : array('field' => trim($field), 'direction' => $direction, 'escape' => TRUE);
            }
//        }

        $this->qb_orderby = array_merge($this->qb_orderby, $qb_orderby);

        return $this;
    }

    /**
     * LIMIT
     *
     * @param	int	$value	LIMIT value
     * @param	int	$offset	OFFSET value
     * @return	$this
     */
    public function limit($value, $offset = 0) {
        is_null($value) OR $this->qb_limit = (int) $value;
        empty($offset) OR $this->qb_offset = (int) $offset;

        return $this;
    }
    
    // Builders
    private function _compile_select() {
        
        $str = "SELECT ";
        $n = count($this->qb_select);
        
        if ($n == 0) {
            $str .= "* ";
            return $str;
        }
        
        $i = 0;
        
        foreach ($this->qb_select as $value) {
        
            $str .= $value;

            if ($i < $n - 1) {
                $str .= ", ";
            } else {
                $str .= " ";
            }
            $i++;
        }
        
        return $str;
    }
    
    private function _compile_from() {
        
        $n = count($this->qb_from);
        if ($n == 0) {
            return "";
        }
        
        $str = "FROM ";
        $i = 0;
        
        foreach ($this->qb_from as $value) {
            
            $str .= $value;
            
            if ($i < $n - 1) {
                $str .= ", ";
            } else {
                $str .= " ";
            }            
            $i++;
        }
        
        return $str;
    }
   
    private function _compile_where() {
        
        if (!count($this->qb_where)) {
            return "";
        }
 
        $str = "WHERE ";
        
        foreach ($this->qb_where as $k) {

            foreach ($k as $kk) {
                $str .= $kk . " "; 
            }
        }        
        return $str;
    }
    
    private function _compile_orderby() {
        
        $n = count($this->qb_orderby);
        if (!$n) {
            return "";
        }        
        
        $str = "ORDER BY ";
        $i = 0;
        
        foreach ($this->qb_orderby as $k) {
            
            $str .= $k['field'];
            if ($k['direction']) {
                $str .= " " . $k['direction'];
            }
                                    
            if ($i < $n - 1) {
                $str .= ", ";
            } else {
                $str .= " ";
            }
            $i++;
        }      

        return $str;        
    }
    
    // @return: $this
    public function query($sql) {
        
        $this->_result = null;
        $this->debugmsg($sql);
        
        $r = mysqli_query($this->_con, $sql);
            
        if ($r instanceof mysqli_result) {
            if (mysqli_num_rows($r) > 0) {
                while ($row = mysqli_fetch_assoc($r)) {
                    $this->_result[] = $row;
                }
            }
        }        
        
        $this->freeResult($r);
        $this->reset();

        return $this;
    }
    
    // @return: $this
    public function get() {
        
        $this->_result = null;
                
        $sql = $this->_compile_select();       
        $sql .= $this->_compile_from();
        
        if (count($this->qb_join) > 0) {
            $sql .= "\n".implode("\n", $this->qb_join) . " ";            
        }
        
        $sql .= $this->_compile_where();        
        $sql .= $this->_compile_orderby();  
        
        if ($this->qb_limit) {
            $sql .= "LIMIT " . $this->qb_limit;
        }
        $this->debugmsg ($sql);
        
        
        $r = mysqli_query($this->_con, $sql);
        while ($row = mysqli_fetch_assoc($r)) {
            $this->_result[] = $row;
        }               
                       
             
        $this->freeResult($r);
        $this->reset();        
        
        return $this;
    }
    
    /**
     * The "set" function.
     *
     * Allows key/value pairs to be set for inserting or updating
     *
     * @param	mixed
     * @param	string
     * @return	CI_DB_query_builder
     */
    public function set($key, $value = '') {
        $key = $this->_object_to_array($key);

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            $this->qb_set[$k] = $v;
        }

        return $this;
    }

    // UPDATE
    
    /**
     * UPDATE
     *
     * Compiles an update string and runs the query.
     *
     * @param	string	$table
     * @param	array	$set	An associative array of update values
     * @param	mixed	$where
     * @param	int	$limit
     * @return	$this
     */
    public function update($table = '', $set = NULL, $where = NULL, $limit = NULL) {

        
        if ($set !== NULL) {
            $this->set($set);
        }

        if ($where !== NULL) {
            $this->where($where);
        }

        if (!empty($limit)) {
            $this->limit($limit);
        }
        

        foreach ($this->qb_set as $key => $val) {
            $e = $key . " = '" . $val . "'";
            $valstr[] = $e;
        }
        
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $valstr) . " " 
                . $this->_compile_where() 
                . $this->_compile_orderby()
                . ($this->qb_limit ? ' LIMIT ' . $this->qb_limit : '');

        $this->debugmsg($sql);
        mysqli_query($this->_con, $sql);
        $this->reset();
        
        
        return $this;
    }
    
    protected function freeResult(&$var) {
        
        if ($var && $var instanceof mysqli_result) {
            mysqli_free_result($var);
        }                
    }
    
    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param	object
     * @return	array
     */
    protected function _object_to_array($object) {

        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            // There are some built in keys we need to ignore for this conversion
            if (!is_object($val) && !is_array($val) && $key !== '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    protected function _has_operator($str) {
        return (bool) preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i', trim($str));
    }
    
    public function debugmsg($str) {
        if ($this->debug) {
            echo $str . "<br/>";
        }
    }
}
