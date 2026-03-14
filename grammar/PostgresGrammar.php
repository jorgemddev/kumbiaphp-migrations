<?php
/**
 * KumbiaPHP Migration System
 *
 * Gramática para PostgreSQL
 *
 * @category Kumbia
 * @package Migration
 */

require_once __DIR__ . '/MySqlGrammar.php';

class PostgresGrammar extends MySqlGrammar
{
    /**
     * Wrap con comillas dobles (PostgreSQL)
     */
    protected function wrap($value)
    {
        if ($value === '*') {
            return $value;
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Tipos específicos de PostgreSQL
     */
    protected function typeString(ColumnDefinition $column)
    {
        return "VARCHAR(" . ($column->get('length') ?? 255) . ")";
    }

    protected function typeInteger(ColumnDefinition $column)
    {
        return $column->get('autoIncrement') ? 'SERIAL' : 'INTEGER';
    }

    protected function typeBigInteger(ColumnDefinition $column)
    {
        return $column->get('autoIncrement') ? 'BIGSERIAL' : 'BIGINT';
    }

    protected function typeBoolean(ColumnDefinition $column)
    {
        return 'BOOLEAN';
    }

    protected function typeJsonb(ColumnDefinition $column)
    {
        return 'JSONB';
    }

    protected function typeUuid(ColumnDefinition $column)
    {
        return 'UUID';
    }

    protected function typeIpAddress(ColumnDefinition $column)
    {
        return 'INET';
    }

    protected function typeMacAddress(ColumnDefinition $column)
    {
        return 'MACADDR';
    }

    /**
     * Modificador de auto-increment para PostgreSQL
     */
    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('autoIncrement') && !in_array($column->get('type'), ['integer', 'bigInteger'])) {
            return 'PRIMARY KEY';
        }
    }

    /**
     * PostgreSQL no soporta UNSIGNED
     */
    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * PostgreSQL no soporta AFTER
     */
    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * PostgreSQL no soporta FIRST
     */
    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * Compilar RENAME COLUMN para PostgreSQL
     */
    public function compileRenameColumn(Blueprint $blueprint, $command)
    {
        return sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->wrap($blueprint->getTable()),
            $this->wrap($command['from']),
            $this->wrap($command['to'])
        );
    }

    /**
     * No hay opciones de tabla en PostgreSQL
     */
    protected function addTableOptions(Blueprint $blueprint)
    {
        return '';
    }
}
