MySQLWrapper - MySQL Database Access Abstraction Object
===============
MySQLWrapper is a MySQL database access abstraction layer that provides a lightweight interface for accessing a MySQL database through three different native PHP interfaces :
* [MySQL Original API](http://php.net/manual/en/book.mysql.php) (deprecated as of PHP 5.5.0)
* [MySQL Improved Extension](http://php.net/manual/en/book.mysqli.php) (MySQLi)
* [PDO Data Objects](http://php.net/manual/en/book.pdo.php) (MySQL driver)

### Background
Necessity is the mother of invention. The idea of creating this wrapper class came to me from necessity: I had a PHP project where I wanted to work with a MySQL server using whatever PHP extension the web server might have. Due the fact that MySQL Original API was deprecated since PHP 5.5.0+ and removed from PHP 7.0.0+ there remains "only" two other options: the MySQLi and PDO MySQL PHP extension. The problem is that each of these PHP MySQL extensions expose a different programming interfaces. Therefore writing an application and maintaining its code for each of these PHP MySQL extensions would become a nightmare, thus this is out of question. One solutions would be to have a wrapper class that would provide the developer an unique|shared programming interface of MySQL|MySQLi|PDO functions. So MySQLWrapper was the answer. 

### Method
By creating an instance of MySQLWrapper one specifies the PHP extension to be used. When a wrapper function is invoked it will dispatch the call to the respective extension specific function. It means there is a unique|shared programming interface. It is expected to obtain the same MySQL response for each of those three PHP extensions.

### List of Supported Functions

Below you can find the list of implemented functions:

- *connect* : connects, set charset and collation, change to default database
- *disconnect*
- *escape_sql_string*
- *fetch_array*
- *fetch_row*
- *free_result*
- *get_affected_rows*
- *get_cols_count*
- *get_connection_info* : returns connection meta information
- *get_insert_id*
- *get_last_error* : returns both the error code, message and server SQLSTATE
- *get_rows_count*
- *query* : supports SQL statement prepare and parameter binding
- *seek_row*
- *select_db*
- *set_charset*
- *set_collation*

### Conclusion
The MySQLWrapper provides a transparent interface to 17+ different MySQL|MySQLi|PDO functions out of 48 MySQL Original API functions or 63 mySQLi API functions. Until all of these max(48,63) functions are fully supported the MySQLWrapper should be used only for testing purpose. It was only 1-day project including the class itself and this page too. 
