<?php

/* Database SQL */
    
/**
 * Database SQL (query, insert, update, delete) 
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the ... License, available
 * at http://
 *
 * @author      Deepak Adhikari <deeps.adhi@gmail.com>
 * @copyright   2013
 * @license     http://
 * @version     1.0.0
 */

/**
 * Load database configuration file
 */
require_once(LIB_PATH.DS.'class'.DS.'db_connect.class.php');

class Database
{
    /**
     * Stores a database object
     *
     * @var object A database object
     */
    protected $db;

    /**
     * Stores SQL query
     *
     * @var string A SQL query
     */
    protected $sql;

    /**
     * Stores a PDO statement
     *
     * @var string A PDO statement
     */
    protected $stmt;

    /**
     * Stores paramater for SQL
     *
     * @var array Paramaters to execute query
     */
    protected $params;

    /**
     * Stores data type of keys
     *
     * @var array Data type of keys
     */
    protected $dataTypes;

    /**
     * Stores lastError, result, keys, lastQuery, numResults, lastInsertId, affectedRows
     *
     * @var array Query datas
     */
    protected $data = array();


    /**
     * Connects to database
     */
    public function __construct(){
        /**
         * Create a database object
         */
        if(!$this->db){
            $this->db = DB_Connect::getConnection();
        }
    }


    /**
     * Insert into table
     *
     * @param   string   $table  Name of table
     * @param   array    $params Fields to be inserted with value
     * @return  bool             Row updated or not
     */
    public function insert($table, array $params){
        $this->initData();
        $this->params = $params;
        $this->extractKeys();
        $this->sql = 'INSERT INTO '.
                    '`' . $table . '` '.  '(`'. implode('`, `', $this->keys) . '`) '.
                'VALUES '.
                    '(:' . implode(', :', $this->keys) . ')';
        $res = $this->query();
        $this->data['lastInsertId'] = $this->db->lastInsertId();
        $this->db->commit();
        $this->stmt->closeCursor();
        return $res; 
    }

    /**
     * Extract array keys
     */
    protected function extractKeys(){
        $keys = array();
        foreach($this->params as $key => $value){
            $exp = explode(' ', $key);
            array_push($keys, $exp[1]);
        }
        $this->keys = $keys;
        $this->data['keys'] = $keys;
    } 

    /**
     * Extract data types
     */
    protected function extractDataTypes(){
        $dataTypes = array();
        foreach($this->params as $key => $value){
            $exp = explode(' ', $key);
            array_push($dataTypes, strtoupper($exp[0]));
        }
        $this->dataTypes = $dataTypes;
    } 

    /**
     * Perform SQL query
     *
     * @return bool SQL Query sucessfully executed or not
     */
    protected function query(){
        $this->data['lastQuery'] = $this->sql;
        try {
            $this->stmt = $this->db->prepare($this->sql);
            $this->db->beginTransaction();
            $this->bindParams();     
            $this->stmt->execute();
            return true;
        } catch(Exception $e){
            $this->db->rollback();
            $this->data['lastError'] = $e->getMessage();
            Error_Report\logger(__CLASS__, 'fatal', $e->getMessage(), 'Could not complete your request :(');
            return false;
        }
    }

    /**
     * Bind paramaters before executing SQL statements
     */
    protected function bindParams(){
        $this->extractDataTypes();
        $i = 0;
        foreach($this->params as $key => $value){
            switch($this->dataTypes[$i]){
            case 'BOOL':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_BOOL);
                break;
            case 'NULL':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_NULL);
                break;
            case 'INT':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_INT);
                break;
            case 'STR':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_STR);
                break;
            case 'LOB':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_LOB);
                break;
            case 'STMT':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_STMT);
                break;
            case 'INPUT_OUTPUT':
                $this->stmt->bindParam(':'.$this->keys[$i], $this->params[$key], PDO::PARAM_INPUT_OUTPUT);
                break;
            }
            ++$i;
        } 
    }

    /**
     * Set $_data to null
     */
    protected function initData(){
        foreach($this->data as $key => $value){
            $this->data[$key] = null;
        }
    }

    /**
     * Perform a SQL query
     *
     * @param   string   $sql    A SQL statement
     * @return  bool            SQL statement sucessfully executed or not
     */
    public function sql($sql){
        $this->initData();
        $this->sql = $sql;
        $this->data['lastQuery'] = $sql;
        try {
            $this->stmt = $this->db->prepare($this->sql);
            $this->db->beginTransaction();
            $this->stmt->execute();
            $this->data['results'] = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->data['numResults'] = count($this->data['results']);
            if($this->data['numResults'] > 0){
                $this->data['keys'] = array_keys($this->data['results'][0]);
            }
            $this->db->commit();
            $this->stmt->closeCursor();
            return true;
        } catch(Exception $e){
            $this->db->rollBack();
            $this->data['lastError'] = $e->getMessage();
            Error_Report\logger(__CLASS__, 'fatal', $e->getMessage(), 'Could not complete your request :(');
            return false;
        }
    }

    /**
     * Update table row
     *
     * @param   string   $table  Name of table
     * @param   array   $params Fields to be changed with value
     * @param   string  $where  Update condition
     * @return  bool            Row updated or not
     */
    public function update($table, $params, $where=''){
        $this->initData();
        $this->params = $params;
        $this->extractKeys();
        $this->sql = 'UPDATE `'.$table.'` SET ';
        foreach($this->keys as $key){
            $this->sql .= '`'.$key.'`=:'.$key.', ';
        }
        $this->sql = rtrim($this->sql, ', ');

        if(!empty($where)){
            $this->sql .= ' WHERE '.$where;
        }
        
        $this->query();
        $this->data['affectedRows'] = $this->stmt->rowCount();
        $this->db->commit();
        $this->stmt->closeCursor();
        return $this->data['affectedRows'] != 0 ? true : false;
    }

    /**
     * Delete from table
     *
     * @param   string $table  Name of table
     * @param   int   $int    Id of row
     * @return  bool          Row deleted or not
     */
    public function delete($table, $id){
        $this->initData();
        $this->sql = 'DELETE FROM `'. $table .'` WHERE `id`=:id LIMIT 1';
        $this->data['lastQuery'] = $this->sql;
        try {
            $this->stmt = $this->db->prepare($this->sql);
            $this->db->beginTransaction();
            $this->stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $this->stmt->execute();
            $this->data['affectedRows'] = $this->stmt->rowCount();
            $this->db->commit();
            $this->stmt->closeCursor();
            return $this->data['affectedRows'] != 0 ? true : false;
        } catch(Exception $e){
            $this->db->rollBack();
            $this->data['lastError'] = $e->getMessage();
            Error_Report\logger(__CLASS__, 'fatal', $e->getMessage(), 'Could not complete your request :(');
            return false;
        }
    }

    /**
     * Database get methods
     *
     * @param   string      $name   Name of variable
     * @return  string|int          Database query resullt
     */
    public function __get($name){
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
}
