<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ASE\ASEException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\Keywords\ASEKeywords;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * The ASEPlatform provides the behavior, features and SQL dialect of the
 * ASE database platform.
 *
 * @since 2.6
 * @author Maximilian Ruta <mr@xtain.net>
 */
class ASEPlatform extends AbstractPlatform
{
    /**
     * @const int
     */
    const LENGTH_LIMIT_VARCHAR = 16384;

    /**
     * @const int
     */
    const LENGTH_LIMIT_BINARY = 16384;

    /**
     * @const int
     */
    const LENGTH_LIMIT_VARBINARY = 16384;

    /**
     * @const int
     */
    const CS_DATES_SHORT_ALT = 0;

    /**
     * @const int
     */
    const CS_DATES_SHORT = 1;

    /**
     * @const int
     */
    const CS_DATES_LONG = 2;

    /**
     * @const int
     */
    const CS_DATES_MDYHMS = 3;

    /**
     * @const string
     */
    const CS_DATES_SHORT_ALT_DATETIME = 'M j Y g:iA';

    /**
     * @const string
     */
    const CS_DATES_SHORT_ALT_DATE = 'M j Y \\1\\2\\:\\0\\0\\A\\M';

    /**
     * @const string
     */
    const CS_DATES_SHORT_ALT_TIME = '\\J\\a\\n\\ \\1\\ \\1\\9\\0\\0\\ g:iA';

    /**
     * @const string
     */
    const CS_DATES_SHORT_DATETIME = 'M j Y g:iA';

    /**
     * @const string
     */
    const CS_DATES_SHORT_DATE = 'M j Y';

    /**
     * @const string
     */
    const CS_DATES_SHORT_TIME = 'g:iA';

    /**
     * @const string
     */
    const CS_DATES_LONG_DATETIME = 'M j Y h:i:s:uA';

    /**
     * @const string
     */
    const CS_DATES_LONG_DATE = 'M j Y';

    /**
     * @const string
     */
    const CS_DATES_LONG_TIME = 'h:i:s:uA';

    /**
     * @const string
     */
    const CS_DATES_MDYHMS_DATETIME = 'M j Y H:i:s';

    /**
     * @const string
     */
    const CS_DATES_MDYHMS_DATE = 'M j Y';

    /**
     * @const string
     */
    const CS_DATES_MDYHMS_TIME = 'H:i:s';

    /**
     * @var array
     */
    protected $config;

    /**
     * ASEPlatform constructor.
     *
     * @param $config
     */
    public function __construct($config = array())
    {
        $this->config = $config;

        if (!isset($this->config['date_format'])) {
            $this->config['date_format'] = self::CS_DATES_SHORT_ALT;
        }
    }

    /**
     * {@inheritDoc}
     * @license New BSD, code from Zend Framework
     */
    public static function quote($value, $type=null)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if ($type === null) {
            if (is_float($value)) {
                return sprintf('%F', $value);
            } elseif (is_int($value)) {
                return $value;
            }

            return "'" . str_replace("'", "''", $value) . "'";
        } else {
            switch ($type) {
                case \PDO::PARAM_INT:
                    return $value;
                case \PDO::PARAM_STR:
                default:
                    return "'" . str_replace("'", "''", $value) . "'";
            }
        }
    }

    public function wrapByMasterContext($sql)
    {
        // In ASE some statements can only be executed in the context of the master database
        return 'DECLARE @olddb varchar(255) ' .
               'SELECT @olddb = DB_NAME() ' .
               'USE master ' .
               $sql . ' ' .
               'USE @olddb';
    }

    public function getCreateDatabaseDeviceSQL(array $device)
    {
        if (!isset($device['name'])) {
            throw new ASEException('Device without name given');
        }

        if (!ctype_alnum($device['name'])) {
            throw new ASEException('Devicename only allows alphanumeric characters');
        }

        $sql = $device['name'];
        if (isset($device['size'])) {
            $sql .= '=' . self::quote($device['size']);
        }

        return $sql . ', ';
    }

    public function getCreateDatabaseOnSQL()
    {
        $sql = '';

        $dataDevices = array();
        $logDevices = array();
        if (isset($this->config['devices']) && !empty($this->config['devices'])) {
            if (isset($this->config['devices']['data']) && !empty($this->config['devices']['data'])) {
                $dataDevices = $this->config['devices']['data'];
            }

            if (isset($this->config['devices']['log']) && !empty($this->config['devices']['log'])) {
                $logDevices = $this->config['devices']['log'];
            }
        }

        if (!empty($dataDevices)) {
            $sql .= ' ON ';
            foreach ($dataDevices as $dataDevice) {
                $sql .= $this->getCreateDatabaseDeviceSQL($dataDevice);
            }

            $sql = rtrim($sql, ', ');
        }

        if (!empty($logDevices)) {
            $sql .= ' LOG ON ';
            foreach ($logDevices as $logDevice) {
                $sql .= $this->getCreateDatabaseDeviceSQL($logDevice);
            }

            $sql = rtrim($sql, ', ');
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        return 'BIT';
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        #todo check
        #todo checknow
        return (!empty($columnDef['autoincrement'])) ? ' IDENTITY' : '';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        #todo check
        #todo checknow
        $this->doctrineTypeMapping = array(
            'bigint' => 'bigint',
            'numeric' => 'decimal',
            'bit' => 'boolean',
            'smallint' => 'smallint',
            'decimal' => 'decimal',
            'smallmoney' => 'integer',
            'int' => 'integer',
            'tinyint' => 'smallint',
            'money' => 'integer',
            'float' => 'float',
            'real' => 'float',
            'double' => 'float',
            'double precision' => 'float',
            'smalldatetime' => 'datetime',
            'datetime' => 'datetime',
            'char' => 'string',
            'varchar' => 'string',
            'text' => 'text',
            'nchar' => 'string',
            'nvarchar' => 'string',
            'ntext' => 'text',
            'binary' => 'binary',
            'varbinary' => 'binary',
            'image' => 'blob',
            'uniqueidentifier' => 'guid',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharTypeDeclarationSQL(array $field)
    {
        return parent::getVarcharTypeDeclarationSQL($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryTypeDeclarationSQL(array $field)
    {
        return parent::getBinaryTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getGuidTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        return 'UNIQUEIDENTIFIER';
    }

    /**
     * {@inheritDoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        #todo check
        #todo checknow
        return $fixed ? ($length ? 'NCHAR(' . $length . ')' : 'CHAR('.$this->getVarcharDefaultLength().')') : ($length ? 'NVARCHAR(' . $length . ')' : 'NVARCHAR('.$this->getVarcharDefaultLength().')');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
        #todo check
        #todo checknow
        //return $fixed ? 'BINARY(' . ($length ?: $this->getBinaryDefaultLength()) . ')' : 'VARBINARY(' . ($length ?: $this->getBinaryDefaultLength()) . ')';
        return $this->getVarcharTypeDeclarationSQLSnippet($length, $fixed);
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow
        //return 'TEXT';
        return 'NVARCHAR(' . $this->getVarcharMaxLength() . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        #todo check
        #todo checknow

        return 'NVARCHAR(' . $this->getBinaryMaxLength() . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ase';
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierQuoteCharacter()
    {
        #todo check
        #todo checknow
        return parent::getIdentifierQuoteCharacter();
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharMaxLength()
    {
        return self::LENGTH_LIMIT_VARCHAR;
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharDefaultLength()
    {
        return 255;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryMaxLength()
    {
        return self::LENGTH_LIMIT_VARBINARY;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryDefaultLength()
    {
        return 255;
    }

    /**
     * {@inheritdoc}
     */
    public function getGuidExpression()
    {
        return 'NEWID()';
    }

    /**
     * {@inheritdoc}
     */
    public function getMd5Expression($column)
    {
        return 'HASH(' . $column . ', \'md5\')';
    }

    /**
     * {@inheritdoc}
     */
    public function getLengthExpression($column)
    {
        return 'CHAR_LENGTH(' . $column . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getModExpression($expression1, $expression2)
    {
        return '(' . $expression1 . ' % ' . $expression2 . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getTrimExpression($str, $pos = self::TRIM_UNSPECIFIED, $char = false)
    {
        #todo check
        if ( ! $char) {
            switch ($pos) {
                case self::TRIM_LEADING:
                    $trimFn = 'LTRIM';
                    break;

                case self::TRIM_TRAILING:
                    $trimFn = 'RTRIM';
                    break;

                default:
                    return 'LTRIM(RTRIM(' . $str . '))';
            }

            return $trimFn . '(' . $str . ')';
        }

        /** Original query used to get those expressions
        declare @c varchar(100) = 'xxxBarxxx', @trim_char char(1) = 'x';
        declare @pat varchar(10) = '%[^' + @trim_char + ']%';
        select @c as string
        , @trim_char as trim_char
        , stuff(@c, 1, patindex(@pat, @c) - 1, null) as trim_leading
        , reverse(stuff(reverse(@c), 1, patindex(@pat, reverse(@c)) - 1, null)) as trim_trailing
        , reverse(stuff(reverse(stuff(@c, 1, patindex(@pat, @c) - 1, null)), 1, patindex(@pat, reverse(stuff(@c, 1, patindex(@pat, @c) - 1, null))) - 1, null)) as trim_both;
         */
        $pattern = "'%[^' + $char + ']%'";

        if ($pos == self::TRIM_LEADING) {
            return 'stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)';
        }

        if ($pos == self::TRIM_TRAILING) {
            return 'reverse(stuff(reverse(' . $str . '), 1, patindex(' . $pattern . ', reverse(' . $str . ')) - 1, null))';
        }

        return 'reverse(stuff(reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)), 1, patindex(' . $pattern . ', reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null))) - 1, null))';
    }
    /**
     * {@inheritdoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos == false) {
            return 'CHARINDEX(' . $substr . ', ' . $str . ')';
        }

        return 'CHARINDEX(' . $substr . ', ' . $str . ', ' . $startPos . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getNowExpression()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);

        return 'NOW()';
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstringExpression($value, $from, $length = null)
    {
        #todo check
        if ($length === null) {
            $length = $this->getLengthExpression($value) . ' - ' . $from . ' + 1';
        }

        return 'SUBSTRING(' . $value . ', ' . $from . ', ' . $length . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getConcatExpression()
    {
        $args = func_get_args();

        return '(' . implode(' + ', $args) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getNotExpression($expression)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);

        return 'NOT(' . $expression . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(day, ' . $date2 . ',' . $date1 . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        $factorClause = '';

        if ('-' === $operator) {
            $factorClause = '-1 * ';
        }

        return 'DATEADD(' . $unit . ', ' . $factorClause . $interval . ', ' . $date . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getForUpdateSQL()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function appendLockHint($fromClause, $lockMode)
    {
        #todo check
        #todo checknow
        return parent::appendLockHint($fromClause, $lockMode);

        switch (true) {
            case LockMode::NONE === $lockMode:
                return $fromClause . ' WITH (NOLOCK)';

            case LockMode::PESSIMISTIC_READ === $lockMode:
                return $fromClause . ' WITH (HOLDLOCK, ROWLOCK)';

            case LockMode::PESSIMISTIC_WRITE === $lockMode:
                return $fromClause . ' WITH (UPDLOCK, ROWLOCK)';

            default:
                return $fromClause;
        }
    }

    /**
     * Returns the SQL snippet to drop an existing database.
     *
     * @param string $database The name of the database that should be dropped.
     *
     * @return string
     */
    public function getDropDatabaseSQL($database)
    {
        if (!ctype_alnum($database)) {
            throw new \InvalidArgumentException('Database name only allows alphanumeric characters');
        }
        // In ASE you can only drop databases in the context of the master database
        return $this->wrapByMasterContext('DROP DATABASE ' . $database);
    }

    /**
     * {@inheritdoc}
     */
    public function getDropIndexSQL($index, $table = null)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getDropConstraintSQL($constraint, $table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommentOnColumnSQL($tableName, $columnName, $comment)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        #todo checknow
        $defaultConstraintsSql = array();
        $commentsSql           = array();

        // @todo does other code breaks because of this?
        // force primary keys to be not null
        foreach ($columns as &$column) {
            if (isset($column['primary']) && $column['primary']) {
                $column['notnull'] = true;
            }

            // Build default constraints SQL statements.
            if (isset($column['default'])) {
                $defaultConstraintsSql[] = 'ALTER TABLE ' . $tableName .
                    ' ADD' . $this->getDefaultConstraintDeclarationSQL($tableName, $column);
            }

            /*
            if ( ! empty($column['comment']) || is_numeric($column['comment'])) {
                $commentsSql[] = $this->getCreateColumnCommentSQL($tableName, $column['name'], $column['comment']);
            }
            */
        }

        $columnListSql = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && !empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $name => $definition) {
                $columnListSql .= ', ' . $this->getUniqueConstraintDeclarationSQL($name, $definition);
            }
        }

        if (isset($options['primary']) && !empty($options['primary'])) {
            $flags = '';
            if (isset($options['primary_index']) && $options['primary_index']->hasFlag('nonclustered')) {
                $flags = ' NONCLUSTERED';
            }
            $columnListSql .= ', PRIMARY KEY' . $flags . ' (' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        $query = 'CREATE TABLE ' . $tableName . ' (' . $columnListSql;

        $check = $this->getCheckDeclarationSQL($columns);
        if (!empty($check)) {
            $query .= ', ' . $check;
        }
        $query .= ')';

        $sql[] = $query;

        if (isset($options['indexes']) && !empty($options['indexes'])) {
            foreach ($options['indexes'] as $index) {
                $sql[] = $this->getCreateIndexSQL($index, $tableName);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $tableName);
            }
        }

        return array_merge($sql, $commentsSql, $defaultConstraintsSql);
    }

    /**
     * Returns the SQL snippet for declaring a default constraint.
     *
     * @param string $table  Name of the table to return the default constraint declaration for.
     * @param array  $column Column definition.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultConstraintDeclarationSQL($table, array $column)
    {
        #todo checknow
        if ( ! isset($column['default'])) {
            throw new \InvalidArgumentException("Incomplete column definition. 'default' required.");
        }

        $columnName = new Identifier($column['name']);

        return
            ' CONSTRAINT ' .
            $this->generateDefaultConstraintName($table, $column['name']) .
            $this->getDefaultValueDeclarationSQL($column) .
            ' FOR ' . $columnName->getQuotedName($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTemporaryTableSnippetSQL()
    {
        return "CREATE TABLE";
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateConstraintSQL(Constraint $constraint, $table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateIndexSQLFlags(Index $index)
    {
        #todo check
        #todo checknow
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        }

        if ($index->hasFlag('clustered')) {
            $type .= 'CLUSTERED ';
        } elseif ($index->hasFlag('nonclustered')) {
            $type .= 'NONCLUSTERED ';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateSchemaSQL($schemaName)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSchemaAlterTableAddColumn(Column $column, TableDiff $diff, &$columnSql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSchemaAlterTableRemoveColumn(Column $column, TableDiff $diff, &$columnSql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSchemaAlterTableChangeColumn(ColumnDiff $columnDiff, TableDiff $diff, &$columnSql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSchemaAlterTableRenameColumn($oldColumnName, Column $column, TableDiff $diff, &$columnSql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSchemaAlterTable(TableDiff $diff, &$sql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnDeclarationListSQL(array $fields)
    {
        #todo check
        return parent::getColumnDeclarationListSQL($fields);
    }

    /**
     * {@inheritdoc}
     *
     * Modifies column declaration order as it differs in Microsoft SQL Server.
     */
    public function getColumnDeclarationSQL($name, array $field)
    {
        #todo check
        #todo checknow
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $collation = (isset($field['collation']) && $field['collation']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($field['collation']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : ' NULL ';

            $unique = (isset($field['unique']) && $field['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSqlDeclaration($field, $this);
            $columnDef = $typeDecl . $collation . $notnull . $unique . $check;
        }

        return $name . ' ' . $columnDef;
    }


    /**
     * {@inheritdoc}
     */
    public function getDecimalTypeDeclarationSQL(array $columnDef)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValueDeclarationSQL($field)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckDeclarationSQL(array $definition)
    {
        #todo check
        #todo checknow
        return parent::getCheckDeclarationSQL($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexDeclarationSQL($name, Index $index)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomTypeDeclarationSQL(array $columnDef)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemporaryTableSQL()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getTemporaryTableName($tableName)
    {
        return '#' . $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeyDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        #todo check
        #todo checknow

        return parent::getForeignKeyDeclarationSQL($foreignKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        #todo check
        #todo checknow

        return parent::getAdvancedForeignKeyOptionsSQL($foreignKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeyReferentialActionSQL($action)
    {
        #todo check
        #todo checknow

        return parent::getForeignKeyReferentialActionSQL($action);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeyBaseDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        #todo check
        #todo checknow

        return parent::getForeignKeyBaseDeclarationSQL($foreignKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueFieldDeclarationSQL()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnCharsetDeclarationSQL($charset)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function prefersIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function convertBooleans($item)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function convertFromBoolean($item)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function convertBooleansToDatabaseValue($item)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * @param string $value
     * @return string
     */
    public function fixDateTimeToDatabaseValue($value)
    {
        switch ($this->config['date_format']) {
            case self::CS_DATES_SHORT_ALT:
                $value = preg_replace("/^(.{3})([\\s][0-9][\\s])/", "$1 $2", $value);
                $value = preg_replace("/(.*?[^\\s])([\\s][0-9]\\:[0-9]+[A-Z]{2})$/", "$1 $2", $value);
                break;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function fixDateTimeFromDatabaseValue($value)
    {
        if (is_string($value)) {
            switch ($this->config['date_format']) {
                case self::CS_DATES_SHORT_ALT:
                    $value = preg_replace("/[\\s]([\\s][0-9]\\:[0-9]+[A-Z]{2})$/", "$1$2", $value);
                    $value = preg_replace("/^(.{3}[\\s])[\\s]([0-9])/", "$1$2", $value);
                    break;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDateTimeToDatabaseValue(\DateTime $value = null)
    {
        $value = parent::convertDateTimeToDatabaseValue($value);

        $value = $this->fixDateTimeToDatabaseValue($value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertFromDateTime($value)
    {
        $value = $this->fixDateTimeFromDatabaseValue($value);

        return parent::convertFromDateTime($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertDateToDatabaseValue(\DateTime $value = null)
    {
        $value = parent::convertDateToDatabaseValue($value);

        $value = $this->fixDateTimeToDatabaseValue($value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertFromDate($value)
    {
        $value = $this->fixDateTimeFromDatabaseValue($value);

        return parent::convertFromDate($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertTimeToDatabaseValue(\DateTime $value = null)
    {
        $value = parent::convertTimeToDatabaseValue($value);

        $value = $this->fixDateTimeToDatabaseValue($value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertFromTime($value)
    {
        $value = $this->fixDateTimeFromDatabaseValue($value);

        return parent::convertFromTime($value);
    }

    /**
     * Some platforms need to convert aliases
     *
     * @param string $column
     * @param string $alias
     * @param array  $mapping
     *
     * @return string
     */
    public function selectAliasColumn($column, $alias, array $mapping = array())
    {
        if (isset($mapping['id']) && $mapping['id']) {
            $mapping['id'] = false;

            $dbType = Type::getType($mapping['type'])->getSQLDeclaration($mapping, $this);

            if ($dbType !== null) {
                return 'CONVERT(' . $dbType . ', ' . $column . ') AS ' . $alias;
            }
        }

        return $column . ' AS ' . $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentDateSQL()
    {
        return 'convert(date, getdate())';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTimeSQL()
    {
        return 'convert(time, getdate())';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTimestampSQL()
    {
        return 'GETDATE()';
    }

    /**
     * {@inheritdoc}
     */
    protected function _getTransactionIsolationLevelSQL($level)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getListDatabasesSQL()
    {
        return "SELECT name FROM master.dbo.sysdatabases";
    }

    /**
     * {@inheritdoc}
     */
    public function getListNamespacesSQL()
    {
        #todo check
        #todo checknow

        return "SELECT name FROM master.dbo.sysusers WHERE name NOT IN('guest')";
    }

    /**
     * {@inheritdoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getListTablesSQL()
    {
        return "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
    }

    /**
     * {@inheritdoc}
     */
    public function getListViewsSQL($database)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getListTableIndexesSQL($table, $currentDatabase = null)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getListTableForeignKeysSQL($table)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateViewSQL($name, $sql)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getDropViewSQL($name)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateDatabaseSQL($name)
    {
        if (!ctype_alnum($name)) {
            throw new \InvalidArgumentException('Database name only allows alphanumeric characters');
        }

        // In ASE you can only create databases in the context of the master database
        return $this->wrapByMasterContext(rtrim('CREATE DATABASE ' . $name . ' ' . $this->getCreateDatabaseOnSQL()));
    }

    /**
     * {@inheritdoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATETIME';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    /* supports*() methods */

    /**
     * {@inheritdoc}
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAlterTable()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReleaseSavepoints()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPrimaryConstraints()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsForeignKeyOnUpdate()
    {
        #todo check
        #todo checknow

        return parent::supportsForeignKeyOnUpdate();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSchemas()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSchemaName()
    {
        return 'dbo';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsGettingAffectedRows()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsInlineColumnComments()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCommentOnStatement()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNativeGuidType()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNativeJsonType()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentityColumnNullInsertSQL()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsViews()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsColumnCollation()
    {
        #todo check
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatString()
    {
        switch ($this->config['date_format']) {
            case self::CS_DATES_SHORT_ALT:
                return self::CS_DATES_SHORT_ALT_DATETIME;
            case self::CS_DATES_SHORT:
                return self::CS_DATES_SHORT_DATETIME;
            case self::CS_DATES_LONG:
                return self::CS_DATES_LONG_DATETIME;
            case self::CS_DATES_MDYHMS:
                return self::CS_DATES_MDYHMS_DATETIME;
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatString()
    {
        switch ($this->config['date_format']) {
            case self::CS_DATES_SHORT_ALT:
                return self::CS_DATES_SHORT_ALT_DATE;
            case self::CS_DATES_SHORT:
                return self::CS_DATES_SHORT_DATE;
            case self::CS_DATES_LONG:
                return self::CS_DATES_LONG_DATE;
            case self::CS_DATES_MDYHMS:
                return self::CS_DATES_MDYHMS_DATE;
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatString()
    {
        switch ($this->config['date_format']) {
            case self::CS_DATES_SHORT_ALT:
                return self::CS_DATES_SHORT_ALT_TIME;
            case self::CS_DATES_SHORT:
                return self::CS_DATES_SHORT_TIME;
            case self::CS_DATES_LONG:
                return self::CS_DATES_LONG_TIME;
            case self::CS_DATES_MDYHMS:
                return self::CS_DATES_MDYHMS_TIME;
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit === null) {
            return $query;
        }

        $limit = (int) $limit;

        if ($offset === 0 || $offset === null) {
            $selectPattern = '/^(\s*SELECT\s+(?:DISTINCT\s+)?)(.*)$/i';
            $replacePattern = sprintf('$1%s $2', "TOP $limit");
            $query = preg_replace($selectPattern, $replacePattern, $query);
            return $query;
        }

        $offset = (int) $offset;

        $start   = $offset + 1;
        $end     = $offset + $limit;

        // We'll find a SELECT or SELECT distinct and prepend TOP n to it
        $selectPattern = '/^(\s*SELECT\s+(?:DISTINCT\s+)?)(.*?)(FROM\s+.*)$/i';
        $replacePattern = sprintf('$1%s $2%s $3', "TOP $end ", " , doctrine_rownum=identity(10) INTO #dctrn_cte");
        $query = preg_replace($selectPattern, $replacePattern, $query);

        // Build a new limited query around the original, using a CTE
        return sprintf(
            "%s SELECT * FROM #dctrn_cte WHERE doctrine_rownum BETWEEN %d AND %d DROP TABLE #dctrn_cte",
            $query,
            $start,
            $end
        );
    }

    /**
     * {@inheritdoc}
     *
     * ASE supports a maximum length of 128 bytes for identifiers.
     */
    public function fixSchemaElementName($schemaElementName)
    {
        #todo check
        #todo checknow
        $maxIdentifierLength = $this->getMaxIdentifierLength();

        if (strlen($schemaElementName) > $maxIdentifierLength) {
            return substr($schemaElementName, 0, $maxIdentifierLength);
        }

        return $schemaElementName;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxIdentifierLength()
    {
        return 128;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmptyIdentityInsertSQL($quotedTableName, $identifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' VALUES ()';
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);

        return 'TRUNCATE TABLE ' . $tableIdentifier->getQuotedName($this);
    }


    /**
     * {@inheritDoc}
     */
    public function createSavePoint($savepoint)
    {
        return 'SAVE TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritDoc}
     */
    public function releaseSavePoint($savepoint)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSavePoint($savepoint)
    {
        return 'ROLLBACK TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritdoc}
     */
    protected function getReservedKeywordsClass()
    {
        return 'Doctrine\DBAL\Platforms\Keywords\ASEKeywords';
    }

    /**
     * Returns a unique default constraint name for a table and column.
     *
     * @param string $table  Name of the table to generate the unique default constraint name for.
     * @param string $column Name of the column in the table to generate the unique default constraint name for.
     *
     * @return string
     */
    private function generateDefaultConstraintName($table, $column)
    {
        return 'DF_' . $this->generateIdentifierName($table) . '_' . $this->generateIdentifierName($column);
    }

    /**
     * Returns a hash value for a given identifier.
     *
     * @param string $identifier Identifier to generate a hash value for.
     *
     * @return string
     */
    private function generateIdentifierName($identifier)
    {
        // Always generate name for unquoted identifiers to ensure consistency.
        $identifier = new Identifier($identifier);

        return strtoupper(dechex(crc32($identifier->getName())));
    }

}