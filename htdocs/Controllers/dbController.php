<?php

namespace Hmg\Controllers;

class DbController
{
    public $db_resource=null;
    private $_host='localhost';
    private $_user='User';
    private $_password='';
    private $_database='';

    public function __construct($host, $user, $password, $database)
    {
        $this->_host = $host;
        $this->_user = $user;
        $this->_password = $password;
        $this->_database = $database;
    }

    public function connect()
    {
        @mysql_connect($this->_host, $this->_user, $this->_password) or die('Error connection to database' . "\n" . mysql_error());
        $this->db_resource = mysql_select_db($this->_database) or die('Could not select the database');
    }
}
