<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class DBHelper
{
    const TYPE_INSERT = 'INSERT';
    const TYPE_REPLACE = 'REPLACE';
    const TYPE_INSERT_IGNORE = 'INSERT IGNORE';
    const TYPE_INSERT_UPDATE = 'ON DUPLICATE KEY UPDATE';

    private $supportTypes = array('Pgsql', 'Mysql');

    private $dbType;

    public function __construct($dbType)
    {
        if (in_array($dbType, $this->supportTypes)) {
            $this->dbType = $dbType;
        } else {
            throw new HelperException('Unsupported DB type ' . $dbType);
        }
    }

    /**
     * @param array $array
     * @param bool $withBinary
     * @return string
     */
    public function fillPlaceholders($array, $withBinary = false)
    {
        return implode(',', array_fill(0, count($array), ($withBinary ? 'binary ' : '') . '?'));
    }

    /**
     * @param array $array
     * @return string
     */
    public function fillPlaceholdersForInsert($array)
    {
        $placeholderArray = array();
        foreach ($array as $item) {
            $placeholderArray[] = $this->fillPlaceholders($item);
        }
        return '(' . implode('), (', $placeholderArray) . ')';
    }

    /**
     * When we want use sql expression (ex now() ) instead placeholder
     *
     * $helpObj->execMultiInsertQuery(......, $updateColumnValues)
     * $updateColumnValues = array(
     *    'updated_at' => $helpObj->expr('now()')
     *    'created_at' => $helpObj->expr('now()')
     * );
     * @author psaharov
     * @param string $sql
     * @return \stdClass
     */
    public function expr($sql)
    {
        $std = new \stdClass;
        $std->sql = $sql;

        return $std;
    }


    /**
     * @param string $tableName
     * @param array $rows
     * @param string $type
     * @param null|array $updateColumnValues if null then update all columns in insert section
     * @return array
     * @throws HelperException
     */
    public function execMultiInsertQuery(
        $tableName,
        $rows,
        $type = self::TYPE_INSERT,
        $updateColumnValues = null
    ) {
        if ($type != self::TYPE_INSERT && $this->dbType != 'Mysql') {
            throw new HelperException('Database ' . $this->dbType . ' not support ' . $type);
        }

        $values = array();
        $columns = array();
        foreach (array_values($rows) as $rowIndex => $row) { //vsafronov: use array_values() for correct $rowIndex
            foreach ($row as $column => $value) {
                if (!$rowIndex) {
                    $this->checkColumnName($column);
                    $columns[] = $column;
                }
                $values[] = $value; //instanceof Doctrine_Null ? null : $value;
            }
        }

        $additionalSql = '';
        if ($type == self::TYPE_INSERT_UPDATE) {

            $updateSqlSetArray = array();
            if ($updateColumnValues === null) {
                foreach ($columns as $column) {
                    $updateColumnValues[$column]
                        = $this->expr('VALUES(' . $this->encloseColumn($column) . ')');
                }
            }
            foreach ($updateColumnValues as $column => $value) {
                $this->checkColumnName($column);
                if ($value instanceof \stdClass) {
                    $columnValue = $value->sql;
                } else {
                    $columnValue = '?';
                    $values[] = $value;
                }
                $updateSqlSetArray[] = $this->encloseColumn($column) . ' = ' . $columnValue;
            }
            if ($updateSqlSetArray) {
                $additionalSql = ' ' . self::TYPE_INSERT_UPDATE . ' ' . implode(', ', $updateSqlSetArray);
            }
        }

        return array(
            (in_array($type, array(self::TYPE_INSERT_IGNORE, self::TYPE_REPLACE)) ? $type : self::TYPE_INSERT) .
            ' INTO ' . $tableName . ' (' . implode(', ', $this->encloseColumns($columns)). ') ' .
            'VALUES ' . $this->fillPlaceholdersForInsert($rows) . $additionalSql,
            $values
        );
    }


    /**
     * @param string $columnName
     * @throws HelperException
     */
    public function checkColumnName($columnName)
    {
        if (!preg_match('/[a-z0-9_\-]/i', $columnName)) {
            throw new HelperException('Invalid column name "' . $columnName . '"');
        }
    }


    /**
     * @param string $column
     * @return string
     */
    public function encloseColumn($column)
    {
        return $this->dbType == 'Mysql' ? '`' . $column . '`' : $column;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function encloseColumns($columns)
    {
        foreach ($columns as $index => $column) {
            $columns[$index] = $this->encloseColumn($column);
        }
        return $columns;
    }
}
