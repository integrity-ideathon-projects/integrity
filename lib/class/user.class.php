<?php

/* User table SQL */
    
/**
 * User table SQL (query, insert, update, delete) 
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
require_once(LIB_PATH.DS.'class'.DS.'database.class.php');

class User extends Database 
{
    /**
     * User table name
     * @var string Name of user table
     */
    private $table = 'user';

    /**
     * Connects to database
     */
    public function __construct(){
        /**
         * Call parent constructor
         */
        parent::__construct();
   }

    /**
     * Insert into table
     *
     * @param   array   $params User details
     * @return  bool            Row updated or not
     */
    public function add(array $params){
        $params = $this->addDataTypeToParamsKeys($params);
        $res = $this->insert($this->table, $params);
        return $res; 
    }

    /**
     * Add data type to parameters keys
     *
     * @param    array $params Fields to be inserted with value
     * @return   array Fields to be inserted with value and key value prefixed with str datatype 
     */
    private function addDataTypeToParamsKeys($params){
        foreach($params as $key => $value){
            $params['str '.$key] = $value;
            unset($params[$key]);
        }
        return $params;
    }

    public function update($table, $params){
        echo 'update was called';
    }

}
