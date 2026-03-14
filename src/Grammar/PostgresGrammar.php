<?php

namespace JorgeMdDev\KumbiaMigrations\Grammar;

use JorgeMdDev\KumbiaMigrations\Blueprint;
use JorgeMdDev\KumbiaMigrations\ColumnDefinition;

class PostgresGrammar extends MySqlGrammar
{
    protected function wrap($value)
    {
        if ($value === '*') return $value;
        return '"' . str_replace('"', '""', $value) . '"';
    }

    protected function typeInteger(ColumnDefinition $column)    { return $column->get('autoIncrement') ? 'SERIAL' : 'INTEGER'; }
    protected function typeBigInteger(ColumnDefinition $column) { return $column->get('autoIncrement') ? 'BIGSERIAL' : 'BIGINT'; }
    protected function typeBoolean(ColumnDefinition $column)    { return 'BOOLEAN'; }
    protected function typeJsonb(ColumnDefinition $column)      { return 'JSONB'; }
    protected function typeUuid(ColumnDefinition $column)       { return 'UUID'; }
    protected function typeIpAddress(ColumnDefinition $column)  { return 'INET'; }
    protected function typeMacAddress(ColumnDefinition $column) { return 'MACADDR'; }

    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('autoIncrement') && !in_array($column->get('type'), ['integer', 'bigInteger'])) {
            return 'PRIMARY KEY';
        }
        return null;
    }

    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column) { return null; }
    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)    { return null; }
    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)    { return null; }

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
