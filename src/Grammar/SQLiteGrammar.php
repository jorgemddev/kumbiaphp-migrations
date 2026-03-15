<?php

namespace JorgeMdDev\KumbiaMigrations\Grammar;

use JorgeMdDev\KumbiaMigrations\Blueprint;
use JorgeMdDev\KumbiaMigrations\ColumnDefinition;

class SQLiteGrammar extends MySqlGrammar
{
    protected $modifiers = ['Nullable', 'Default', 'Increment'];

    protected function typeInteger(ColumnDefinition $column)    { return 'INTEGER'; }
    protected function typeBigInteger(ColumnDefinition $column) { return 'INTEGER'; }
    protected function typeFloat(ColumnDefinition $column)      { return 'REAL'; }
    protected function typeDouble(ColumnDefinition $column)     { return 'REAL'; }
    protected function typeDecimal(ColumnDefinition $column)    { return 'NUMERIC'; }
    protected function typeBoolean(ColumnDefinition $column)    { return 'INTEGER'; }
    protected function typeJson(ColumnDefinition $column)       { return 'TEXT'; }
    protected function typeJsonb(ColumnDefinition $column)      { return 'TEXT'; }
    protected function typeDateTime(ColumnDefinition $column)   { return 'DATETIME'; }
    protected function typeTimestamp(ColumnDefinition $column)  { return 'DATETIME'; }
    protected function typeBinary(ColumnDefinition $column)     { return 'BLOB'; }

    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column)
    {
        return $column->get('autoIncrement') ? 'PRIMARY KEY AUTOINCREMENT' : null;
    }

    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column) { return null; }
    protected function modifyCharset(Blueprint $blueprint, ColumnDefinition $column)  { return null; }
    protected function modifyCollate(Blueprint $blueprint, ColumnDefinition $column)  { return null; }
    protected function modifyComment(Blueprint $blueprint, ColumnDefinition $column)  { return null; }
    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)    { return null; }
    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)    { return null; }

    public function compileAdd(Blueprint $blueprint)
    {
        $table = $this->wrap($blueprint->getTable());

        return array_map(function ($column) use ($table) {
            // SQLite doesn't support modifying columns directly
            if ($column->get('change')) {
                throw new \RuntimeException('SQLite does not support modifying columns. You need to recreate the table.');
            }
            return "ALTER TABLE {$table} ADD COLUMN {$column}";
        }, $this->getColumns($blueprint));
    }

    public function compileDropColumn(Blueprint $blueprint, $command)
    {
        throw new \RuntimeException('SQLite does not support dropping columns. You need to recreate the table.');
    }

    public function compileRenameColumn(Blueprint $blueprint, $command)
    {
        return sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->wrap($blueprint->getTable()),
            $this->wrap($command['from']),
            $this->wrap($command['to'])
        );
    }

    protected function addTableOptions(Blueprint $blueprint) { return ''; }
}
