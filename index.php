<!DOCTYPE html>
<html>
<head>
<style type="text/css">
pre, table {
	background-color: rgba(255, 255, 0, 0.2);
	border: 1px solid #ccc;
	padding: 10px;
	color: #3844A2;
	margin: 10px;
}

pre::BEFORE {
	content: "Function result:";
	display: block;
	color: #000;
}

.new_ext {
	background-color: #00adee;
}

.ext_body {
	padding-left: 20px;
}

.success {
	background-color: green;
}

.error {
	background-color: tomato;
}

pre, .new_ext, .success, .error {
	border-radius: 5px;
}

.new_ext, .success, .error {
	padding: 5px;
}

pre, .success, .error {
	display: inline-block;
}

.new_ext, .success, .error {
	color: white;
}

ol li a {
	color: #31849F;
}

ol li a:HOVER {
	color: #0A5A75;
}

li a {
	color: #FFCB95;
}

li a:HOVER {
	color: white;
}

ol li a:HOVER {
	background-color: #FFCB95;
}

li a:HOVER, ol li a:HOVER {
	font-weight: bold;
}

label {
	font-weight: bold;
}
</style>
</head>
<body>
	<h1>MySQLWrapper</h1>
	<table>
		<tr>
			<td><label>Author</label></td>
			<td>:</td>
			<td>Eugen Mihailescu &lt;<a href="mailto:eugenmihailescux@gmail.com">eugenmihailescux@gmail.com</a>&gt;
			</td>
		</tr>
		<tr>
			<td><label>Version</label></td>
			<td>:</td>
			<td>1.0</td>
		</tr>
		<tr>
			<td><label>Since</label></td>
			<td>:</td>
			<td>2016-02-24</td>
		</tr>
		<tr>
			<td><label>URL</label></td>
			<td>:</td>
			<td><a href="https://github.com/eugenmihailescu/mysqlwrapper" target="_blank">https://github.com/eugenmihailescu/mysqlwrapper</a></td>
		</tr>
		<tr>
			<td><label>Requires</label></td>
			<td>:</td>
			<td>PHP 5.3+</td>
		</tr>
		<tr>
			<td><label>Tested</label></td>
			<td>:</td>
			<td>PHP 5.3@IIS6, PHP5.4@Apache2.2.31(Linux)</td>
		</tr>
	</table>
	<h3>Abstract</h3>
	<p>MySQLWrapper is a MySQL database access abstraction layer that provides a
		lightweight interface for accessing a MySQL database through three different
		native PHP interfaces :</p>
	<ol>
		<li><a href="http://php.net/manual/en/book.mysql.php" target="_blank">MySQL
				Original API</a> (deprecated as of PHP 5.5.0)</li>
		<li><a href="http://php.net/manual/en/book.mysqli.php" target="_blank">MySQL
				Improved Extension (MySQLi)</a></li>
		<li><a href="http://php.net/manual/en/book.pdo.php" target="_blank">PDO Data
				Objects</a> (MySQL driver)</li>
	</ol>
	<h3>Background</h3>
	<p>
		<i>Necessity is the mother of invention</i>. The idea of creating this wrapper
		class came to me from necessity: I had a PHP project where I wanted to work
		with a MySQL server using whatever PHP extension the web server might have.
		Due the fact that MySQL Original API was deprecated since PHP 5.5.0+ and
		removed from PHP 7.0.0+ there remains "only" two other options: the MySQLi and
		PDO MySQL PHP extension. The problem is that each of these PHP MySQL
		extensions expose a different programming interfaces. Therefore writing an
		application and maintaining its code for each of these PHP MySQL extensions
		would become a nightmare, thus this is out of question. One solutions would be
		to have a wrapper class that would provide the developer an unique|shared
		programming interface of MySQL|MySQLi|PDO functions. So MySQLWrapper was the
		answer.
	</p>
	<h3>Method</h3>
	<p>By creating an instance of MySQLWrapper one specifies the PHP extension to
		be used. When a wrapper function is invoked it will dispatch the call to the
		respective extension specific function. It means there is a unique|shared
		programming interface. It is expected to obtain the same MySQL response for
		each of those three PHP extensions.</p>
	<h3>Results</h3>
	<p>Below you can find an automated self-test for each of the above PHP
		extensions for each implemented class function. It is expected they generate
		the same output, otherwise we can assume there is something wrong with the
		class implementation for that specific|buggy function.</p> 
	<?php
	require_once 'MySQLWrapper.php';
	
	// the PHP extensions we will test
	$extensions = array( 'mysql', 'mysqli', 'pdo_mysql' );
	
	// the connection parameters to a test MySQL server
	$params = array( 
		'mysql_host' => 'localhost', 
		'mysql_port' => 3306, 
		'mysql_user' => 'my_user', 
		'mysql_pwd' => 'my_pwd', 
		'mysql_db' => 'test_db', 
		'mysql_charset' => 'utf8', 
		'mysql_collate' => '', 
		'mysql_ext' => 'mysql' );
	
	// print-out a header/footer group
	$print_test_group = function ( $str, $ident = 6, $class = '', $id = '' ) {
		printf( 
			'%s<div%s>', 
			! empty( $id ) ? '<a id="' . $id . '"></a>' : '', 
			! empty( $class ) ? ' class="' . $class . '"' : '' );
		echo str_repeat( '&nbsp;', $ident ), $ident ? '* ' : '', $str;
		print ( '</div>' ) ;
	};
	
	// print-out a function result box
	$print_test_result = function ( $sample ) {
		echo '<pre>', $sample, '</pre><br>';
	};
	
	// print-out the test header
	$print_test_function = function ( $i, $name, $ext, &$ext_index ) use(&$print_test_group ) {
		$print_test_group( 
			sprintf( '[%d] Testing <span style="color:fuchsia;font-family:courier;">%s</span> function:', $i, $name ), 
			0, 
			'', 
			"{$ext}_{$i}" );
		$ext_index .= sprintf( '<li><a href="#%s_%d">%s</a></li>', $ext, $i, $name );
	};
	
	// stores the last test error(s)
	$set_error = function ( $i, &$obj, &$has_error ) {
		$error = $obj->get_last_error();
		if ( $error['code'] || ! empty( $error['message'] ) ) {
			$str = ( empty( $error['message'] ) ? 'Unknown error' : $error['message'] ) . ' (code : ' . $error['code'] .
				 ')';
			in_array( $str, $has_error ) || $has_error[$i] = $str;
		}
	};
	
	// runs a specific test by callback
	$run_test = function ( &$test_id, $name, $ext, &$ext_index, $callback, $ignore_errors = false ) use(
	&$obj, 
	&$res, 
	&$has_error, 
	$print_test_function, 
	$print_test_result, 
	$set_error ) {
		$print_test_function( $test_id, $name, $ext, $ext_index );
		$print_test_result( print_r( call_user_func( $callback, $obj, $res, $ext ), 1 ) );
		
		// test #13 will generate an error by design
		13 == $test_id || $set_error( $test_id++, $obj, $has_error );
	};
	
	// array of functions that enclose the tests
	$tests_callbacks = array( 
		'get_connection_info' => function ( $obj ) {
			return $obj->get_connection_info();
		}, 
		'escape_sql_string' => function ( $obj ) {
			return $obj->escape_sql_string( "what's up my friend?" );
		}, 
		'non-prepared query' => function ( $obj ) use(&$res ) {
			$sql = "select TABLE_SCHEMA, TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS, CREATE_TIME from INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() LIMIT 5";
			$res = $obj->query( $sql );
			return $sql . PHP_EOL . PHP_EOL . print_r( $res, 1 );
		}, 
		'fetch_array' => function ( $obj, $res ) {
			$rows = array();
			while ( $array = $obj->fetch_array( $res, MySQLWrapper::MYSQL_ASSOC ) )
				$rows[] = $array;
			return $rows;
		}, 
		'get_affected_rows' => function ( $obj, $res ) {
			return $obj->get_affected_rows( $res );
		}, 
		'get_rows_count' => function ( $obj, $res ) {
			return $obj->get_rows_count( $res );
		}, 
		'get_cols_count' => function ( $obj, $res ) {
			return $obj->get_cols_count( $res );
		}, 
		'seek_row' => function ( $obj, $res, $ext ) {
			$rows = $obj->seek_row( $res, 0 );
			return print_r( $rows, 1 ) .
				 ( 'pdo_mysql' == $ext ? 'On PDO this is not expected to work therefore it will affect the result of test #9' : '' );
		}, 
		'fetch_row' => function ( $obj, $res ) {
			$rows = array();
			while ( $array = $obj->fetch_row( $res ) )
				$rows[] = $array;
			return $rows;
		}, 
		'free_result' => function ( $obj, $res ) {
			return $obj->free_result( $res );
		}, 
		'prepared-query' => function ( $obj, $dummy, $ext ) use(&$res, &$tbl ) {
			$rows = array();
			$cr_sql = sprintf( 
				"CREATE TEMPORARY TABLE IF NOT EXISTS %s\n(\n\tc0 INT NOT NULL AUTO_INCREMENT,\n\tc1 VARCHAR(50), c2 INT,\n\tPRIMARY KEY(c0)\n) AUTO_INCREMENT=5", 
				$tbl );
			$result = $cr_sql . PHP_EOL;
			if ( $res = $obj->query( $cr_sql ) ) {
				$c1 = uniqid( 'v_' );
				$c2 = time();
				$args = 'pdo_mysql' == $ext ? ':c1,:c2' : '?,?';
				$ins_sql = sprintf( 'INSERT INTO %s (c1,c2) values (%s) /*(\'%s\', %s)*/', $tbl, $args, $c1, $c2 );
				$result .= $ins_sql . PHP_EOL;
				if ( $res = $obj->query( $ins_sql, array( 'c1' => $c1, 'c2' => $c2 ) ) ) {
					$sel_sql = sprintf( 'SELECT * FROM %s', $tbl );
					$result .= $sel_sql . PHP_EOL;
					if ( $res = $obj->query( $sel_sql ) )
						$rows = $obj->fetch_array( $res, 1 );
					else
						$rows = false;
				}
				$obj->free_result( $res );
			}
			return $result . PHP_EOL . print_r( $rows, 1 );
		}, 
		
		'get_insert_id' => function ( $obj, $res ) {
			return $obj->get_insert_id();
		}, 
		'get_last_error' => function ( $obj ) {
			$obj->query( 
				"SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='This is a custom error raised at MySQL server. Only PDO should output its state=45000.'" );
			return $obj->get_last_error();
		} );
	
	// generate the table of contents by running the tests for each PHP extensions
	$ext_index = '<ol>';
	ob_start();
	foreach ( $extensions as $ext ) {
		$start = microtime( true );
		$has_error = array();
		$ext_index .= sprintf( '<li><a href="#%s">%s</a><ol>', $ext, $ext );
		
		echo '<div id="', $ext, '" class="new_ext">Testing class MySQLWrapper with <span style="color:red;font-weight:bold">', $ext, '</span> PHP extension...</div>';
		echo '<div class="ext_body">';
		$params['mysql_ext'] = $ext;
		
		// iterate through each test
		try {
			$res = null; // shared between #3-#10
			$test_id = 1;
			$tbl = uniqid( 'tbl_' );
			
			$obj = new MySQLWrapper( $params );
			$link = $obj->connect();
			
			$print_test_group( 'connected successfully :-)', 1 );
			echo '<br>';
			
			foreach ( $tests_callbacks as $test_name => $callback ) {
				$run_test( $test_id, $test_name, $ext, $ext_index, $callback );
			}
			
			// clean-up
			$obj->query( 'DROP TABLE ' . $tbl );
			
			$obj->disconnect();
		} catch ( Exception $e ) {
			$has_error[0] = $e->getMessage();
		}
		
		// print-out the test summary
		if ( ! empty( $has_error ) ) {
			array_walk( 
				$has_error, 
				function ( &$item, $key ) use(&$ext ) {
					if ( 0 == $key )
						$item = sprintf( 'Terminated unexpectedly : %s', $item );
					else
						$item = sprintf( '[<a href="#%s_%d">%d</a>] : %s', $ext, $key, $key, $item );
				} );
			$print_test_group( 
				':-( The following ' . count( $has_error ) . ' test(s) of extension `' . $ext .
					 '` failed<ul style="list-style-type: none;"><li>' . implode( '</li><li>', $has_error ) .
					 '</li></ul>', 
					0, 
					'error' );
		} else {
			$diff = microtime( true ) - $start;
			$print_test_group( sprintf( 'Test finished successfully in %.4f sec :-)', $diff ), 0, 'success' );
		}
		
		echo '</div>';
		$ext_index .= '</ol></li>';
		echo '<hr>';
		
		$obj = null;
	}
	
	$test_body = ob_get_clean();
	
	// at this point $ext_index contains the table of contents, $test_body contains the tests outputs
	echo $ext_index, '</ol>', $test_body;
	
	?>
	<h3>Conclusion</h3>
	<p>
		The MySQLWrapper provides a transparent interface to 17+ different
		MySQL|MySQLi|PDO functions out of 48 MySQL Original API functions or 63 mySQLi
		API functions. Until all of these <i>max(48,63)</i> functions are fully
		supported the MySQLWrapper should be used only for testing purpose. It was
		only 1-day project including the class itself and this page too.
	</p>
</body>
</html>