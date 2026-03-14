<?php
/**
 * KumbiaPHP Migration System
 *
 * Blueprint para construcción de esquemas de base de datos
 * Fluent interface para definir tablas y columnas
 *
 * @category Kumbia
 * @package Migration
 */

class Blueprint
{
    /**
     * Nombre de la tabla
     *
     * @var string
     */
    protected $table;

    /**
     * Columnas a agregar
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Comandos a ejecutar
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Motor de almacenamiento (MySQL)
     *
     * @var string
     */
    public $engine;

    /**
     * Charset (MySQL)
     *
     * @var string
     */
    public $charset = 'utf8mb4';

    /**
     * Collation (MySQL)
     *
     * @var string
     */
    public $collation = 'utf8mb4_unicode_ci';

    /**
     * Si es una tabla temporal
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Constructor
     *
     * @param string $table
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Crear una nueva columna auto-incremental
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Crear una nueva columna auto-incremental grande
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Crear una nueva columna de tipo string
     *
     * @param string $column
     * @param int $length
     * @return ColumnDefinition
     */
    public function string($column, $length = 255)
    {
        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Crear una nueva columna de tipo char
     *
     * @param string $column
     * @param int $length
     * @return ColumnDefinition
     */
    public function char($column, $length = 255)
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Crear una nueva columna de tipo text
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function text($column)
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Crear una nueva columna de tipo mediumText
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function mediumText($column)
    {
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Crear una nueva columna de tipo longText
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function longText($column)
    {
        return $this->addColumn('longText', $column);
    }

    /**
     * Crear una nueva columna de tipo integer
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Crear una nueva columna de tipo tinyInteger
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function tinyInteger($column)
    {
        return $this->addColumn('tinyInteger', $column);
    }

    /**
     * Crear una nueva columna de tipo smallInteger
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function smallInteger($column)
    {
        return $this->addColumn('smallInteger', $column);
    }

    /**
     * Crear una nueva columna de tipo mediumInteger
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function mediumInteger($column)
    {
        return $this->addColumn('mediumInteger', $column);
    }

    /**
     * Crear una nueva columna de tipo bigInteger
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Crear una nueva columna de tipo unsigned integer
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Crear una nueva columna de tipo unsigned bigInteger
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Crear una nueva columna de tipo float
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function float($column, $total = 8, $places = 2)
    {
        return $this->addColumn('float', $column, compact('total', 'places'));
    }

    /**
     * Crear una nueva columna de tipo double
     *
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @return ColumnDefinition
     */
    public function double($column, $total = null, $places = null)
    {
        return $this->addColumn('double', $column, compact('total', 'places'));
    }

    /**
     * Crear una nueva columna de tipo decimal
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    /**
     * Crear una nueva columna de tipo boolean
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function boolean($column)
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Crear una nueva columna de tipo enum
     *
     * @param string $column
     * @param array $allowed
     * @return ColumnDefinition
     */
    public function enum($column, array $allowed)
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Crear una nueva columna de tipo json
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function json($column)
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Crear una nueva columna de tipo jsonb (PostgreSQL)
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function jsonb($column)
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Crear una nueva columna de tipo date
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function date($column)
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Crear una nueva columna de tipo dateTime
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    /**
     * Crear una nueva columna de tipo dateTimeTz
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    /**
     * Crear una nueva columna de tipo time
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function time($column, $precision = 0)
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    /**
     * Crear una nueva columna de tipo timestamp
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Agregar columnas timestamps (created_at, updated_at)
     *
     * @param int $precision
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();
        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * Agregar columnas timestamps que se actualizan automáticamente
     *
     * @param int $precision
     * @return void
     */
    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->nullable();
        $this->timestampTz('updated_at', $precision)->nullable();
    }

    /**
     * Crear una nueva columna de tipo timestampTz
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    /**
     * Agregar columna deleted_at para soft deletes
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Crear una nueva columna de tipo binary
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function binary($column)
    {
        return $this->addColumn('binary', $column);
    }

    /**
     * Crear una nueva columna de tipo uuid
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function uuid($column)
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Crear una nueva columna de tipo ipAddress
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function ipAddress($column)
    {
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Crear una nueva columna de tipo macAddress
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function macAddress($column)
    {
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Definir clave primaria
     *
     * @param string|array $columns
     * @param string|null $index
     * @return void
     */
    public function primary($columns, $index = null)
    {
        $this->addCommand('primary', compact('columns', 'index'));
    }

    /**
     * Definir clave única
     *
     * @param string|array $columns
     * @param string|null $index
     * @return void
     */
    public function unique($columns, $index = null)
    {
        $this->addCommand('unique', compact('columns', 'index'));
    }

    /**
     * Definir índice
     *
     * @param string|array $columns
     * @param string|null $index
     * @return void
     */
    public function index($columns, $index = null)
    {
        $this->addCommand('index', compact('columns', 'index'));
    }

    /**
     * Definir clave foránea
     *
     * @param string|array $columns
     * @return ForeignKeyDefinition
     */
    public function foreign($columns)
    {
        $this->addCommand('foreign', compact('columns'));
        // Retornar referencia al último comando agregado
        return new ForeignKeyDefinition($this->commands[count($this->commands) - 1]);
    }

    /**
     * Eliminar columna
     *
     * @param string|array $columns
     * @return void
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Renombrar columna
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function renameColumn($from, $to)
    {
        $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Eliminar clave primaria
     *
     * @param string|array|null $index
     * @return void
     */
    public function dropPrimary($index = null)
    {
        $this->addCommand('dropPrimary', compact('index'));
    }

    /**
     * Eliminar índice único
     *
     * @param string|array $index
     * @return void
     */
    public function dropUnique($index)
    {
        $this->addCommand('dropUnique', compact('index'));
    }

    /**
     * Eliminar índice
     *
     * @param string|array $index
     * @return void
     */
    public function dropIndex($index)
    {
        $this->addCommand('dropIndex', compact('index'));
    }

    /**
     * Eliminar clave foránea
     *
     * @param string|array $index
     * @return void
     */
    public function dropForeign($index)
    {
        $this->addCommand('dropForeign', compact('index'));
    }

    /**
     * Renombrar tabla
     *
     * @param string $to
     * @return void
     */
    public function rename($to)
    {
        $this->addCommand('rename', compact('to'));
    }

    /**
     * Agregar una nueva columna
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnDefinition
     */
    protected function addColumn($type, $name, array $parameters = [])
    {
        $column = new ColumnDefinition(array_merge(compact('type', 'name'), $parameters));
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Agregar un nuevo comando
     *
     * @param string $name
     * @param array $parameters
     * @return array
     */
    protected function addCommand($name, array $parameters = [])
    {
        $command = array_merge(compact('name'), $parameters);
        $this->commands[] = $command;
        return $command;
    }

    /**
     * Obtener la tabla
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Obtener las columnas
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Obtener los comandos
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
