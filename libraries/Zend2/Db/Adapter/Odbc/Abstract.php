<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
 
/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Adapter/Abstract.php';

/**
 * @see Zend_Db_Statement_Odbc
 */
require_once 'Zend/Db/Statement/Odbc.php';

/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Adapter_Odbc_Abstract extends Zend_Db_Adapter_Abstract
{
    /**
     * User-provided configuration.
     *
     * Basic keys are:
     *
     * user => (string) Connect to the database as this user.
     * password => (string) Password associated with the user.
     * dsn   => (string) The database source name for the connection.
     *
     * @var array
     */
    protected $_config = array(
        'dsn'       => null,
        'user'     => null,
        'password'     => null,
    );


    /**
     * Default class name for a DB statement.
     *
     * @var string
     */
    protected $_defaultStmtClass = 'Zend_Db_Statement_Odbc';

    /**
     * Creates a connection resource.
     *
     * @return void
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    protected function _connect()
    {
        if (is_resource($this->_connection)) {
            // connection already exists
            return;
        }

        if (!extension_loaded('odbc')) {
            /**
             * @see Zend_Db_Adapter_Odbc_Exception
             */
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception('The Odbc extension is required for this adapter but the extension is not loaded');
        }

        $dsn = $this->_config['dsn'];
        $user 	= $this->_config['user'];
        $password	= $this->_config['password'];
		
        $this->_connection = odbc_connect($dsn, $user, $password);

        if (!$this->_connection) {
            /**
             * @see Zend_Db_Adapter_Odbc_Exception
             */
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception(odbc_errormsg());
        }
    }

    /**
     * Check for config options that are mandatory.
     * Throw exceptions if any are missing.
     *
     * @param array $config
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _checkRequiredOptions(array $config)
    {
        // we need at least a dsn
        if (! array_key_exists('dsn', $config)) {
            /** @see Zend_Db_Adapter_Exception */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'dsn' that names the database source");
        }

        if (! array_key_exists('password', $config) && array_key_exists('user', $config)) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'password' for login credentials.
                                                If Windows Authentication is desired, both keys 'user' and 'password' should be ommited from config.");
        }

        if (array_key_exists('password', $config) && !array_key_exists('user', $config)) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'user' for login credentials.
                                                If Windows Authentication is desired, both keys 'user' and 'password' should be ommited from config.");
        }
    }

    /**
     * Set the transaction isoltion level.
     *
     * @param integer|null $level A fetch mode from SQL_TXN_*.
     * @return true
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    public function setTransactionIsolationLevel($level = null)
    {
        $this->_connect();
        $sql = null;

        // Default transaction level in sql server
        if ($level === null)
        {
            $level = SQL_TXN_READ_COMMITTED;
        }

        switch ($level) {
            case SQL_TXN_READ_UNCOMMITTED:
                $sql = "READ UNCOMMITTED";
                break;
            case SQL_TXN_READ_COMMITTED:
                $sql = "READ COMMITTED";
                break;
            case SQL_TXN_REPEATABLE_READ:
                $sql = "REPEATABLE READ";
                break;
            case SQL_TXN_SNAPSHOT:
                $sql = "SNAPSHOT";
                break;
            case SQL_TXN_SERIALIZABLE:
                $sql = "SERIALIZABLE";
                break;
            default:
                require_once 'Zend/Db/Adapter/Odbc/Exception.php';
                throw new Zend_Db_Adapter_Odbc_Exception("Invalid transaction isolation level mode '$level' specified");
        }

        if (!odbc_exec($this->_connection, "SET TRANSACTION ISOLATION LEVEL $sql;")) {
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception("Transaction cannot be changed to '$level'");
        }

        return true;
    }

    /**
     * Test if a connection is active
     *
     * @return boolean
     */
    public function isConnected()
    {
        return (is_resource($this->_connection)
                && (get_resource_type($this->_connection) == 'odbc link')
        );
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        if ($this->isConnected()) {
            odbc_close($this->_connection);
        }
        $this->_connection = null;
    }

    /**
     * Returns an SQL statement for preparation.
     *
     * @param string $sql The SQL statement with placeholders.
     * @return Zend_Db_Statement_Odbc
     */
    public function prepare($sql)
    {
        $this->_connect();
        $stmtClass = $this->_defaultStmtClass;

        if (!class_exists($stmtClass)) {
            /**
             * @see Zend_Loader
             */
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($stmtClass);
        }

        $stmt = new $stmtClass($this, $sql);		
        $stmt->setFetchMode($this->_fetchMode);

        return $stmt;
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }




    /**
     * Leave autocommit mode and begin a transaction.
     *
     * @return void
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    protected function _beginTransaction()
    {
        if (!odbc_autocommit($this->_connection, false)) {
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception(odbc_errormsg());
        }
    }

    /**
     * Commit a transaction and return to autocommit mode.
     *
     * @return void
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    protected function _commit()
    {
        if (!odbc_commit($this->_connection)) {
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception(odbc_errormsg());
        }
    }

    /**
     * Roll back a transaction and return to autocommit mode.
     *
     * @return void
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    protected function _rollBack()
    {
        if (!odbc_rollback($this->_connection)) {
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception(odbc_errormsg());
        }
    }

    /**
     * Set the fetch mode.
     *
     * @todo Support FETCH_CLASS and FETCH_INTO.
     *
     * @param integer $mode A fetch mode.
     * @return void
     * @throws Zend_Db_Adapter_Odbc_Exception
     */
    public function setFetchMode($mode)
    {
        switch ($mode) {
            case Zend_Db::FETCH_NUM:   // seq array
            case Zend_Db::FETCH_ASSOC: // assoc array
            case Zend_Db::FETCH_BOTH:  // seq+assoc array
            case Zend_Db::FETCH_OBJ:   // object
                $this->_fetchMode = $mode;
                break;
            case Zend_Db::FETCH_BOUND: // bound to PHP variable
                require_once 'Zend/Db/Adapter/Odbc/Exception.php';
                throw new Zend_Db_Adapter_Odbc_Exception('FETCH_BOUND is not supported yet');
                break;
            default:
                require_once 'Zend/Db/Adapter/Odbc/Exception.php';
                throw new Zend_Db_Adapter_Odbc_Exception("Invalid fetch mode '$mode' specified");
                break;
        }
    }


    /**
     * Retrieve server version in PHP style
     *
     * @return string
     */
    public function getServerVersion()
    {
		//	odbc_data_source 
/*        $this->_connect();
        $version = odbc_client_info($this->_connection);

        if ($version !== false) {
            return $version['DriverVer'];
        }*/

        return null;
    }
	
    /**
     * Executes an SQL Statement directly on the connection
     *
     * @param string SQL
     * @return bool
     */
    public function exec($sql)
    {
		$this->_connect();
        if (!@odbc_exec($this->_connection, $sql)) {
            require_once 'Zend/Db/Adapter/Odbc/Exception.php';
            throw new Zend_Db_Adapter_Odbc_Exception(odbc_errormsg());
        }

        return true;
    }	
}
