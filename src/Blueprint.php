<?php

namespace JorgeMdDev\KumbiaMigrations;

class Blueprint
{
    protected $table;
    protected $columns  = [];
    protected $commands = [];

    public $engine;
    public $charset    = 'utf8mb4';
    public $collation  = 'utf8mb4_unicode_ci';
    public $temporary  = false;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    public function string($column, $length = 255)
    {
        return $this->addColumn('string', $column, compact('length'));
    }

    public function char($column, $length = 255)
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    public function text($column)
    {
        return $this->addColumn('text', $column);
    }

    public function mediumText($column)
    {
        return $this->addColumn('mediumText', $column);
    }

    public function longText($column)
    {
        return $this->addColumn('longText', $column);
    }

    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    public function tinyInteger($column)
    {
        return $this->addColumn('tinyInteger', $column);
    }

    public function smallInteger($column)
    {
        return $this->addColumn('smallInteger', $column);
    }

    public function mediumInteger($column)
    {
        return $this->addColumn('mediumInteger', $column);
    }

    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    public function float($column, $total = 8, $places = 2)
    {
        return $this->addColumn('float', $column, compact('total', 'places'));
    }

    public function double($column, $total = null, $places = null)
    {
        return $this->addColumn('double', $column, compact('total', 'places'));
    }

    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    public function boolean($column)
    {
        return $this->addColumn('boolean', $column);
    }

    public function enum($column, array $allowed)
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    public function json($column)
    {
        return $this->addColumn('json', $column);
    }

    public function jsonb($column)
    {
        return $this->addColumn('jsonb', $column);
    }

    public function date($column)
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    public function time($column, $precision = 0)
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();
        $this->timestamp('updated_at', $precision)->nullable();
    }

    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->nullable();
        $this->timestampTz('updated_at', $precision)->nullable();
    }

    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    public function binary($column)
    {
        return $this->addColumn('binary', $column);
    }

    public function uuid($column)
    {
        return $this->addColumn('uuid', $column);
    }

    public function ipAddress($column)
    {
        return $this->addColumn('ipAddress', $column);
    }

    public function macAddress($column)
    {
        return $this->addColumn('macAddress', $column);
    }

    public function primary($columns, $index = null)
    {
        $this->addCommand('primary', compact('columns', 'index'));
    }

    public function unique($columns, $index = null)
    {
        $this->addCommand('unique', compact('columns', 'index'));
    }

    public function index($columns, $index = null)
    {
        $this->addCommand('index', compact('columns', 'index'));
    }

    public function foreign($columns)
    {
        $this->addCommand('foreign', compact('columns'));
        return new ForeignKeyDefinition($this->commands[count($this->commands) - 1]);
    }

    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->addCommand('dropColumn', compact('columns'));
    }

    public function renameColumn($from, $to)
    {
        $this->addCommand('renameColumn', compact('from', 'to'));
    }

    public function dropPrimary($index = null)
    {
        $this->addCommand('dropPrimary', compact('index'));
    }

    public function dropUnique($index)
    {
        $this->addCommand('dropUnique', compact('index'));
    }

    public function dropIndex($index)
    {
        $this->addCommand('dropIndex', compact('index'));
    }

    public function dropForeign($index)
    {
        $this->addCommand('dropForeign', compact('index'));
    }

    public function rename($to)
    {
        $this->addCommand('rename', compact('to'));
    }

    protected function addColumn($type, $name, array $parameters = [])
    {
        $column = new ColumnDefinition(array_merge(compact('type', 'name'), $parameters));
        $this->columns[] = $column;
        return $column;
    }

    protected function addCommand($name, array $parameters = [])
    {
        $command = array_merge(compact('name'), $parameters);
        $this->commands[] = $command;
        return $command;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getCommands()
    {
        return $this->commands;
    }
}
