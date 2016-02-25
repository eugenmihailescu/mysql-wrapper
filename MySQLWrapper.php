<?php

class MySQLException {

	function __construct( $message, $code = null, $previous = null ) {
		if ( ! class_exists( 'MyException' ) )
			new \Exception( $message, $code, $previous );
		else
			new MyException( $message, $code, $previous );
	}
}

/**
 * MySQL Database Access Abstraction Object
 *
 * An abstract database layer that allows the communication with a MySQL database
 * using transparently one of MySQL, MySQLi or PDO_MySQL PHP extension.
 *
 * @class MySQLWrapper
 * @extends
 *
 * @since 1.0
 * @version 1.0
 * @package MyBackup
 * @author Eugen Mihailescu
 *        
 */
class MySQLWrapper {

	const MYSQL_ASSOC = 1;

	const MYSQL_NUM = 2;

	const MYSQL_BOTH = 3;

	private $_is_opened;

	/**
	 * The constructor parameters
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $_params;

	/**
	 * The MySQL server DNS or IP address
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_host;

	/**
	 * The MySQL port
	 *
	 * @since 1.0
	 * @var int
	 */
	protected $_port;

	/**
	 * The MySQL authentication username
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_user;

	/**
	 * The password of username
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_pwd;

	/**
	 * The MySQL database to use
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_db;

	/**
	 * The MySQL charset to use
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_charset;

	/**
	 * The MySQL collation
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $_collate;

	/**
	 * True when using PDO MySQL extension, false otherwise
	 *
	 * @since 1.0
	 * @var bool
	 */
	protected $_is_pdo;

	/**
	 * True when using MySQLi extension, false otherwise
	 *
	 * @since 1.0
	 * @var bool
	 */
	protected $_is_mysqli;

	/**
	 * The connection resource link (MySQL) or object (MySQLi|PDO_MySQL)
	 *
	 * @since 1.0
	 * @var resource|object
	 */
	protected $_link;

	/**
	 * When true then connect using the WordPress global
	 * params otherwise the constructor parameters.
	 *
	 * @since 1.0
	 * @var bool
	 */
	public $is_wp;

	/**
	 * Creates and initializes the data access abstraction object
	 *
	 * @since 1.0
	 * @param array $params An array of key=value where key is one of:
	 *        mysql_host : the MySQL server DNS or host IP (default `localhost`)
	 *        mysql_port : the MySQL port (default 3306)
	 *        mysql_user : the MySQL authentication user
	 *        mysql_pwd : the password for the MySQL user
	 *        mysql_db : the default MySQL database to connect
	 *        mysql_charset : the MySQL charset to use (default 'utf8`)
	 *        mysql_collate : the MySQL collation to use
	 *        mysql_format : Either `sql` or `xml` (used for data export)
	 *        mysql_ext : The PHP extension to use. Either mysql, mysqli or pdo_mysql. When empty then it is chosen automatically.
	 */
	function __construct( $params ) {
		$this->_is_opened = false;
		$this->_link = null;
		$this->is_wp = false; // $is_wp = function_exists( '\\add_management_page' );
		$this->_params = $params;
		
		$allowed_ext = array( '', 'mysql', 'mysqli', 'pdo_mysql' );
		
		$ext = $this->_get_param( 'mysql_ext' );
		
		if ( ! in_array( $ext, $allowed_ext ) ) {
			throw new MySQLException( 
				sprintf( 
					_( 'Invalid mysql_ext argument. One of %s expected.' ), 
					implode( 
						', ', 
						array_map( function ( $item ) {
							return empty( $item ) ? '``' : $item;
						}, $allowed_ext ) ) ) );
		}
		
		$has_pdo = class_exists( 'PDO' );
		$has_mysqli = function_exists( 'mysqli_connect' );
		
		$this->_is_pdo = $has_pdo && 'pdo_mysql' == $ext;
		
		$this->_is_mysqli = $has_mysqli && in_array( $ext, array( 'mysqli', '' ) );
		
		// fallback on auto-detect when has PDO but no MySQLi
		$this->_is_pdo = $this->_is_pdo || $has_pdo && in_array( $ext, array( 'mysqli', '' ) ) && ! $this->_is_mysqli;
		
		// fallback on auto-detect when has MySQLi but no PDO
		$this->_is_mysqli = $this->_is_mysqli ||
			 $has_mysqli && in_array( $ext, array( 'pdo_mysql', '' ) ) && ! $this->_is_pdo;
		
		$this->_host = $this->_get_param( 'mysql_host' );
		$this->_port = $this->_get_param( 'mysql_port' );
		$this->_user = $this->_get_param( 'mysql_user' );
		$this->_pwd = $this->_get_param( 'mysql_pwd' );
		$this->_db = $this->_get_param( 'mysql_db' );
		$this->_charset = $this->_get_param( 'mysql_charset' );
		$this->_collate = $this->_get_param( 'mysql_collate' );
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see MySQLWrapper::__construct
	 * @since 1.0
	 * @return boolean
	 */
	function __destruct() {
		$this->_is_opened && $this->disconnect();
		return true;
	}

	/**
	 * Returns the MySQL(i) function name based on the common function sufix by
	 * prepending the $name either with mysql_ or mysqli_ prefix.
	 *
	 * @param string $name The common function sufix
	 * @since 1.0
	 * @return string
	 */
	private function _get_mysql_function( $name ) {
		return 'mysql' . ( $this->_is_mysqli ? 'i' : '' ) . '_' . $name;
	}

	/**
	 * Returns an array of arguments sent to this function in their
	 * natural order (MySQL) or in their reverse order (MySQLi).
	 *
	 * @return array
	 */
	private function _swap_args() {
		$args = func_get_args();
		return $this->_is_mysqli ? array_reverse( $args ) : $args;
	}

	/**
	 * Disconnect if already connected to different connection
	 *
	 * @since 1.0
	 * @return mixed Returns true if disconnected, the connection resource otherwise. Returns false on failure.
	 */
	private function _init_connect() {
		if ( ! $this->_is_opened ) {
			return true;
		}
		
		// if the connection is already opened
		$info = $this->get_connection_info();
		
		if ( false !== $info ) {
			$current_user = $info['user'];
			$current_host = array( $info['host'], $info['ipaddr'] );
			
			if ( preg_match( '/([^@]+)@?(.*)/', $info['user'], $matches ) ) {
				$current_user = $matches[1];
				count( $matches ) > 1 && $current_host[] = $matches[2];
			}
			
			// when already connected with the same connection params
			if ( $this->_user == $current_user && $this->_db == $info['dbname'] &&
				 ( empty( $this->_port ) || $this->_port == $info['port'] ) && $this->_charset == $info['charset'] &&
				 ( empty( $this->_collate ) || $this->_collate == $info['collation'] ) &&
				 in_array( $this->_host, $current_host ) ) {
				return $this->_link;
			}
		}
		
		// disconnect whenever the current connection is bound to another connection params
		return $this->disconnect();
	}

	/**
	 * Returns the current connection information
	 *
	 * @since 1.0
	 * @return array|boolean On success returns an array with following keys:
	 *         - user : the connection MySQL user name (user@host)
	 *         - dbname : the connection database name
	 *         - charset : the connection charachter set
	 *         - collation : the connection collation identifier
	 *         - host : the connection host name
	 *         - port : the connection port number
	 *         - ipaddr : the connection binding IP address
	 *         Returns false on failure.
	 */
	public function get_connection_info() {
		if ( $this->_is_opened &&
			 $res = $this->query( 
				'SELECT current_user() as user, database() as dbname, charset(current_user()) as charset, collation(current_user()) as collation, @@hostname as host, @@port as port, @@bind_address as ipaddr' ) )
			return $this->fetch_array( $res, 1 );
		
		return false;
	}

	/**
	 * Sets the client collation on an existent MySQL connection.
	 * In case of PDO will close the existent connection and will
	 * create a new connection to the specified database name.
	 *
	 * @since 1.0
	 * @param string $collation
	 * @return mixed Returns true on success, false otherwise
	 */
	public function set_collation( $collation ) {
		if ( empty( $collation ) || $this->_is_pdo && $collation == $this->_collate )
			return true;
		
		if ( ! ( $this->_is_opened &&
			 false === ( $res = $this->query( sprintf( "SET NAMES '%s' COLLATE '%s'", $this->_charset, $collation ) ) ) ) ) {
			$this->_collate = $collation;
			return true;
		}
		
		return false;
	}

	/**
	 * Open a connection to a MySQL server
	 *
	 * @since 1.0
	 * @param bool $persistent Use a persistent connection.
	 * @return mixed Returns a MySQL link identifier (MySQL), an object (MySQLi|PDO), false|Exception on error.
	 */
	public function connect( $persistent = true ) {
		// disconnect if connected|different connection
		if ( ! is_bool( $link = $this->_init_connect() ) )
			return $link;
		
		if ( $this->_is_pdo ) {
			
			$php_prior_536 = version_compare( PHP_VERSION, '5.3.6', '<' );
			
			$pdo_options = array( \PDO::ATTR_PERSISTENT => $persistent );
			
			$pdo_dsn = sprintf( 
				'mysql:host=%s;port=%s;dbname=%s;%s', 
				$this->_host, 
				$this->_port, 
				$this->_db, 
				$php_prior_536 ? '' : sprintf( 'charset=%s;collation=%s', $this->_charset, $this->_collate ) );
			
			$php_prior_536 && $pdo_options[\PDO::MYSQL_ATTR_INIT_COMMAND] = sprintf( 
				"SET NAMES '%s' COLLATE '%s'", 
				$this->_charset, 
				$this->_collate );
			
			$this->_link = new \PDO( $pdo_dsn, $this->_user, $this->_pwd, $pdo_options );
		} else {
			$name = 'mysql' . ( $this->_is_mysqli ? 'i' : '' ) . '_' . ( $persistent && ! $this->_is_mysqli ? 'p' : '' ) .
				 'connect';
			
			$host = ( $this->_is_mysqli && $persistent ? 'p:' : '' ) . $this->_host;
			
			$this->_link = call_user_func( $name, $host, $this->_user, $this->_pwd );
		}
		
		$this->_is_opened = ! empty( $this->_link );
		
		// on PDO these were already bound on $pdo_dsn
		if ( ! $this->_is_pdo && $this->_is_opened ) {
			$this->set_collation( $this->_collate );
			$this->set_charset( $this->_charset );
			$this->select_db( $this->_db );
		}
		
		return $this->_link;
	}

	/**
	 * Close MySQL connection
	 *
	 * @since 1.0
	 * @return boolean Returns true on success, false otherwise
	 */
	public function disconnect() {
		if ( $this->_is_pdo )
			$result = true;
		else
			$result = empty( $this->_link ) || call_user_func( $this->_get_mysql_function( 'close' ), $this->_link );
		
		$result && $this->_link = null;
		
		return $result;
	}

	/**
	 * Sets the client charset on an existent MySQL connection.
	 * In case of PDO will close the existent connection and will
	 * create a new connection to the specified database name.
	 *
	 * @since 1.0
	 * @param string $charset
	 * @return mixed Returns true on success, false otherwise
	 */
	public function set_charset( $charset ) {
		if ( empty( $charset ) || $this->_is_pdo && $charset == $this->_charset )
			return true;
		
		if ( $this->_is_pdo ) {
			$old_charset = $this->_charset;
			
			$this->_charset = $charset;
			
			// reconnect using the current charset
			if ( $this->_is_opened && false === $this->connect() ) {
				$this->_charset = $old_charset;
				return false;
			}
			
			return true;
		}
		
		return call_user_func_array( 
			$this->_get_mysql_function( 'set_charset' ), 
			$this->_swap_args( $charset, $this->_link ) );
	}

	/**
	 * Select a MySQL database.
	 * In case of PDO will close the existent connection and will
	 * create a new connection to the specified database name.
	 *
	 * @since 1.0
	 * @param string $database_name
	 * @return boolean Returns true on success, false otherwise
	 */
	public function select_db( $database_name ) {
		if ( empty( $database_name ) || $this->_is_pdo && $this->_db == $database_name )
			return true;
		
		if ( $this->_is_pdo ) {
			$this->disconnect();
			$this->_db = $database_name;
			return false !== $this->connect();
		}
		
		return call_user_func_array( 
			$this->_get_mysql_function( 'select_db' ), 
			$this->_swap_args( $database_name, $this->_link ) );
	}

	/**
	 * Returns an array of the error message from previous MySQL operation
	 *
	 * @return array An array with the following keys:
	 *         - code : the MySQL error code; 0|NULL when no error occured.
	 *         - message : the MySQL error specific message; empty when no error occured.
	 *         - state : the SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard)
	 */
	public function get_last_error() {
		if ( $this->_is_pdo ) {
			$error = $this->_link->errorInfo();
			return array( 'code' => $error[1], 'message' => $error[2], 'state' => $error[0] );
		}
		return array( 
			'code' => call_user_func( $this->_get_mysql_function( 'errno' ), $this->_link ), 
			'message' => call_user_func( $this->_get_mysql_function( 'error' ), $this->_link ), 
			'state' => null );
	}

	/**
	 * Escapes special characters in $unescaped_string for use in a SQL statement.
	 *
	 * @since 1.0
	 * @param string $unescaped_string
	 * @return string|bool Returns the escaped string, false on error.
	 */
	public function escape_sql_string( $unescaped_string ) {
		if ( $this->_is_pdo ) {
			return $this->_link->quote( $unescaped_string );
		}
		return call_user_func_array( 
			$this->_get_mysql_function( 'real_escape_string' ), 
			$this->_swap_args( $unescaped_string, $this->_link ) );
	}

	/**
	 * Prepare an SQL statement for execution
	 *
	 * @since 1.0
	 * @param string $query
	 * @return mixed|boolean On success returns a statement object (MySQLi|PDO) or true (MySQL).
	 *         Returns FALSE on error.
	 */
	private function _prepare( $query ) {
		// if MySQLi|PDO use the native PHP `prepare` function
		if ( $this->_is_pdo ) {
			return $this->_link->prepare( $query );
		} elseif ( $this->_is_mysqli ) {
			return mysqli_prepare( $this->_link, $query );
		}
		
		// if MySQL emulate a prepare function
		if ( ! ( mysql_query( sprintf( "SET @sql = '%s'", addslashes( $query ) ) ) &&
			 mysql_query( 'PREPARE stmt FROM @sql' ) ) )
			return false;
		
		return true;
	}

	/**
	 * Binds variables to a prepared statement as parameters
	 *
	 * @since 1.0
	 * @param object $stmt On MySQLi|PDO a statement object returned by _prepare. On MySQL always TRUE.
	 * @param array $params An array of param_name=param_value to be bound to the prepared statement
	 * @return boolean Returns true on success, false otherwise.
	 */
	private function _bind_params( $stmt, $params ) {
		
		/**
		 * Get the parameter type as expected by the MySQLi|PDO param_bind function
		 *
		 * @see mysqli_stmt::bind_param|PDO::bindParam
		 * @param mixed $value The SQL prepared statement parameter value
		 * @param bool $is_mysqli When true then returns the value as expected by
		 *        mysqli_stmt::bind_param, otherwise PDO::bindParam.
		 * @return string|int Returns the MySQLi|PDO param type corresponding to $value.
		 *         When $is_mysqli is true returns i|d|s, otherwise PDO::PARAM_INT|PDO::PARAM_STR
		 */
		$_get_param_type = function ( $value, $is_mysqli = true ) {
			if ( is_int( $value ) || is_bool( $value ) )
				return $is_mysqli ? 'i' : \PDO::PARAM_INT;
			elseif ( is_double( $value ) )
				return $is_mysqli ? 'd' : \PDO::PARAM_STR;
			
			return $is_mysqli ? 's' : \PDO::PARAM_STR;
		};
		
		// on MySQLi|PDO use the native `bind_param` function
		if ( $this->_is_pdo ) {
			foreach ( $params as $name => $value )
				// PDO native
				if ( $this->_is_pdo ) {
					$params[$name] = is_string( $value ) ? $this->escape_sql_string( $value ) : $value;
					if ( ! $stmt->bindParam( ":$name", $params[$name], $_get_param_type( $params[$name], false ) ) )
						return false;
				}
		} elseif ( $this->_is_mysqli ) {
			// MySQLi native
			$types = '';
			$args = array( &$stmt, &$types );
			foreach ( $params as $name => $value ) {
				$types .= $_get_param_type( $value );
				$args[] = is_string( $value ) ? $this->escape_sql_string( $value ) : $value;
				$i = count( $args ) - 1;
				$args[$i] = &$args[$i];
			}
			
			if ( ! call_user_func_array( 'mysqli_stmt_bind_param', $args ) )
				return false;
		} else
			// if MySQL emulate a bind_param function
			foreach ( $params as $name => $value ) {
				$quote = is_string( $value ) ? "'" : "";
				if ( ! mysql_query( 
					sprintf( 
						"SET @%s = %s", 
						$name, 
						$quote . ( empty( $quote ) ? $value : $this->escape_sql_string( $value ) ) . $quote ) ) )
					return false;
			}
		
		return true;
	}

	/**
	 * Sends a MySQL query
	 *
	 * @since 1.0
	 * @param string $query An SQL query
	 * @param array When $query is a prepared SQL statement then an array of param_name=param_value, otherwise ignored.
	 * @return mixed See mysql_query, mysqli_query, PDO::query
	 */
	public function query( $query, $params = null ) {
		$stmt = null;
		
		// execute the query using a prepared SQL statement
		if ( ! empty( $params ) ) {
			if ( $stmt = $this->_prepare( $query ) )
				if ( $this->_bind_params( $stmt, $params ) )
					// if MySQLi|PDO use the native PHP `execute` function
					if ( $this->_is_pdo || $this->_is_mysqli ) {
						if ( $stmt->execute() )
							return $stmt;
					} else {
						// if MySQL emulate an `execute` function
						return mysql_query( 
							sprintf( "EXECUTE stmt USING %s", '@' . implode( ',@', array_keys( $params ) ) ) );
					}
			return false;
		}
		
		// execute the unprepared SQL statement
		if ( $this->_is_pdo ) {
			return $this->_link->query( $query );
		}
		return call_user_func_array( $this->_get_mysql_function( 'query' ), $this->_swap_args( $query, $this->_link ) );
	}

	/**
	 * Frees the memory associated with the result
	 *
	 * @since 1.0
	 * @param mixed $result A resource|mysqli_result|PDOStatement
	 * @return bool Returns true on success or if MySQLi, false on error.
	 */
	public function free_result( &$result ) {
		if ( $this->_is_pdo ) {
			$result = null;
			return true;
		}
		$success = call_user_func( $this->_get_mysql_function( 'free_result' ), $result );
		return $this->_is_mysqli ? true : $success;
	}

	/**
	 * Get a result row as an enumerated array
	 *
	 * @since 1.0
	 * @param mixed $result A resource|mysqli_result|PDOStatement
	 * @return mixed Returns an array indexed by column number.
	 *         Returns false|null if there are no more rows.
	 */
	public function fetch_row( $result ) {
		if ( $this->_is_pdo ) {
			return $result->fetch( \PDO::FETCH_NUM );
		}
		return call_user_func( $this->_get_mysql_function( 'fetch_row' ), $result );
	}

	/**
	 * Fetch a result row as an associative array, a numeric array, or both
	 *
	 * @since 1.0
	 * @param mixed $result A resource|mysqli_result|PDOStatement
	 * @param int $result_type The type of array that is to be fetched: self::MYSQL_ASOC, self::MYSQL_NUM, self::MYSQL_BOTH
	 * @return mixed Returns an array that contains numerical indexes, named indexes or both (see $result_type).
	 *         Returns false|null when there are no more rows.
	 */
	public function fetch_array( $result, $result_type = self::MYSQL_BOTH ) {
		if ( $this->_is_pdo ) {
			return $result->fetch( $result_type + 1 );
		}
		return call_user_func( $this->_get_mysql_function( 'fetch_array' ), $result, $result_type );
	}

	/**
	 * Get number of affected rows in previous MySQL operation
	 *
	 * @since 1.0
	 * @param mixed $stmt A PDOStatement if PDO is used, otherwise NULL.
	 * @return int Returns the number of affected rows on success, -1 on error.
	 */
	public function get_affected_rows( $stmt = null ) {
		if ( $this->_is_pdo ) {
			return $stmt->rowCount();
		}
		return call_user_func( $this->_get_mysql_function( 'affected_rows' ), $this->_link );
	}

	/**
	 * Get the ID generated in the last query.
	 * Remember, if you use a transaction you should use lastInsertId BEFORE you commit otherwise it will return 0.
	 *
	 * @since 1.0
	 * @return mixed Returns the ID generated for an AUTO_INCREMENT column by the previous query on success, 0 when no AUTO_INCREMENT.
	 *         Returns false on error.
	 *        
	 */
	public function get_insert_id() {
		if ( $this->_is_pdo ) {
			return $this->_link->lastInsertId();
		}
		return call_user_func( $this->_get_mysql_function( 'insert_id' ), $this->_link );
	}

	/**
	 * Get number of fields in result
	 *
	 * @since 1.0
	 * @param mixed $result A resource|mysqli_result|PDOStatement
	 * @return mixed Returns the number of fields in the result set given by $result, false on error.
	 */
	public function get_rows_count( $result ) {
		if ( $this->_is_pdo ) {
			return $result->rowCount();
		}
		
		return call_user_func( $this->_get_mysql_function( 'num_rows' ), $result );
	}

	/**
	 * Get number of fields in result
	 *
	 * @since 1.0
	 * @param mixed $result A resource|mysqli_result|PDOStatement
	 * @return mixed Returns the number of fields in the result set $result on success or FALSE on failure.
	 */
	public function get_cols_count( $result ) {
		if ( $this->_is_pdo ) {
			return $result->columnCount();
		}
		return call_user_func( $this->_get_mysql_function( 'num_fields' ), $result );
	}

	/**
	 * Move internal result pointer
	 *
	 * @since 1.0
	 * @param mixed $result $result A resource|mysqli_result|PDOStatement
	 * @param int $row_number The desired row number of the new result pointer (from 0 to row count -1).
	 * @return bool When MySQL|MySQLi returns true, otherwise the row's array at current cursor position.
	 *         In all cases false is returned on failure.
	 *        
	 */
	public function seek_row( $result, $row_number ) {
		if ( $this->_is_pdo ) {
			// TODO PDO MySQL data seek doesn't work as PDO MySQL does not allow scrollable cursors :-(
			return $result->fetch( \PDO::FETCH_BOTH, \PDO::FETCH_ORI_ABS, $row_number );
		}
		return call_user_func( $this->_get_mysql_function( 'data_seek' ), $result, $row_number );
	}

	/**
	 * Returns the array's value corresponding to the key if exists, default otherwise.
	 *
	 * When $value is not array then returns default when is empty, the value otherwise.
	 *
	 * @param mixed $value
	 * @param string $key
	 * @param mixed $default
	 * @return Ambigous <string, unknown>
	 */
	private function _is_null( $value, $key, $default = null ) {
		if ( is_array( $value ) )
			return isset( $value[$key] ) ? $value[$key] : $default;
		
		return empty( $value ) ? $default : $value;
	}

	/**
	 * Returns the value of the given parameter name.
	 * On WordPress returns the corresponding global parameter value.
	 *
	 * @since 1.0
	 * @param string $param_name A fixed parameter name:
	 *        - mysql_host
	 *        - mysql_port
	 *        - mysql_user
	 *        - mysql_pwd
	 *        - mysql_db
	 *        - mysql_charset
	 *        - mysql_collate
	 *        - mysql_format
	 *        - mysql_ext
	 * @return mixed Returns the value of the given parameter name, null when parameter not found.
	 */
	private function _get_param( $param_name ) {
		$default = null;
		
		switch ( $param_name ) {
			case 'mysql_format' :
				$default = $this->_is_null( $this->_params, $param_name, 'sql' );
				break;
			case 'mysql_host' :
				$default = @constant( 'DB_HOST' ) ? DB_HOST : 'localhost';
				break;
			case 'mysql_port' :
				$default = 3306;
				break;
			case 'mysql_user' :
				$default = @constant( 'DB_USER' ) ? DB_USER : '';
				break;
			case 'mysql_pwd' :
				$default = @constant( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
				break;
			case 'mysql_db' :
				$default = @constant( 'DB_NAME' ) ? DB_NAME : '';
				break;
			case 'mysql_charset' :
				$default = @constant( 'DB_CHARSET' ) ? DB_CHARSET : 'utf8';
				break;
			case 'mysql_collate' :
				$default = @constant( 'DB_COLLATE' ) ? DB_COLLATE : '';
				break;
			case 'mysql_ext' :
				$default = '';
				break;
		}
		
		return $this->is_wp ? $default : $this->_is_null( $this->_params, $param_name, $default );
	}
}