<?php
require_once('DBMysql.class.php');

use Psr\Log\LoggerInterface;
use Karybu\Exception\DBConnectionFailedException;

/**
 * Class to use MySQL innoDB DBMS
 * mysql innodb handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author Arnia (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 **/
class DBMysql_innodb extends DBMysql
{

    /**
     * Constructor
     * @return void
     **/
    function DBMysql_innodb(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->_setDBInfo();
        $connected = $this->_connect();
        if (!$connected) {
            throw new DBConnectionFailedException ("Connection to MySQL failed!");
        }
    }

    /**
     * Create an instance of this class
     * @return DBMysql_innodb return DBMysql_innodb object instance
     */
    function create(LoggerInterface $logger = null)
    {
        return new DBMysql_innodb($logger);
    }

    /**
     * DB disconnection
     * this method is private
     * @param resource $connection
     * @return void
     */
    function _close($connection)
    {
        $this->_query("commit", $connection);
        @mysql_close($connection);
    }

    /**
     * DB transaction start
     * this method is private
     * @return boolean
     */
    function _begin()
    {
        $connection = $this->_getConnection('master');
        $this->_query("begin", $connection);
        return true;
    }

    /**
     * DB transaction rollback
     * this method is private
     * @return boolean
     */
    function _rollback()
    {
        $connection = $this->_getConnection('master');
        $this->_query("rollback", $connection);
        return true;
    }

    /**
     * DB transaction commit
     * this method is private
     * @return boolean
     */
    function _commit()
    {
        $connection = $this->_getConnection('master');
        $this->_query("commit", $connection);
        return true;
    }

    /**
     * Execute the query
     * this method is private
     * @param string $query
     * @param resource $connection
     * @return resource
     */
    function __query($query, $connection)
    {
        // Run the query statement
        $result = @mysql_query($query, $connection);
        // Error Check
        if (mysql_error($connection)) {
            $this->setError(mysql_errno($connection), mysql_error($connection));
        }
        // Return result
        return $result;
    }

    /**
     * Create table by using the schema xml
     *
     * type : number, varchar, tinytext, text, bigtext, char, date, \n
     * opt : notnull, default, size\n
     * index : primary key, index, unique\n
     * @param string $xml_doc xml schema contents
     * @return void|object
     */
    function _createTable($xml_doc)
    {
        // xml parsing
        $oXml = new XmlParser();
        $xml_obj = $oXml->parse($xml_doc);
        // Create a table schema
        $table_name = $xml_obj->table->attrs->name;
        if ($this->isTableExists($table_name)) {
            return;
        }
        $table_name = $this->prefix . $table_name;

        if (!is_array($xml_obj->table->column)) {
            $columns[] = $xml_obj->table->column;
        }
        else {
            $columns = $xml_obj->table->column;
        }

        foreach ($columns as $column) {
            $name = $column->attrs->name;
            $type = $column->attrs->type;
            $size = $column->attrs->size;
            $notnull = $column->attrs->notnull;
            $primary_key = $column->attrs->primary_key;
            $index = $column->attrs->index;
            $unique = $column->attrs->unique;
            $default = $column->attrs->default;
            $auto_increment = $column->attrs->auto_increment;

            $column_schema[] = sprintf(
                '`%s` %s%s %s %s %s',
                $name,
                $this->column_type[$type],
                $size ? '(' . $size . ')' : '',
                isset($default) ? "default '" . $default . "'" : '',
                $notnull ? 'not null' : '',
                $auto_increment ? 'auto_increment' : ''
            );

            if ($primary_key) {
                $primary_list[] = $name;
            }
            else {
                if ($unique) {
                    $unique_list[$unique][] = $name;
                } else {
                    if ($index) {
                        $index_list[$index][] = $name;
                    }
                }
            }
        }

        if (count($primary_list)) {
            $column_schema[] = sprintf("primary key (%s)", '`' . implode($primary_list, '`,`') . '`');
        }

        if (count($unique_list)) {
            foreach ($unique_list as $key => $val) {
                $column_schema[] = sprintf("unique %s (%s)", $key, '`' . implode($val, '`,`') . '`');
            }
        }

        if (count($index_list)) {
            foreach ($index_list as $key => $val) {
                $column_schema[] = sprintf("index %s (%s)", $key, '`' . implode($val, '`,`') . '`');
            }
        }

        $schema = sprintf(
            'create table `%s` (%s%s) %s;',
            $this->addQuotes($table_name),
            "\n",
            implode($column_schema, ",\n"),
            "ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci"
        );

        $output = $this->_query($schema);
        if (!$output) {
            return false;
        }
    }
}
?>
