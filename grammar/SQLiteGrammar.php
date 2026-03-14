<?php
/**
 * KumbiaPHP Migration System
 *
 * Gramática para SQLite
 *
 * @category Kumbia
 * @package Migration
 */

require_once __DIR__ . '/MySqlGrammar.php';

class SQLiteGrammar extends MySqlGrammar
{
    /**
     * Modificadores para SQLite
     */
    protected $modifiers = ['Nullable', 'Default', 'Increment'];

    /**
     * Tipos específicos de SQLite
     */
    protected function typeInteger(ColumnDefinition $column)
    {
        return 'INTEGER';
    }

    protected function typeBigInteger(ColumnDefinition $column)
    {
        return 'INTEGER';
    }

    protected function typeFloat(ColumnDefinition $column)
    {
        return 'REAL';
    }

    protected function typeDouble(ColumnDefinition $column)
    {
        return 'REAL';
    }

    protected function typeDecimal(ColumnDefinition $column)
    {
        return 'NUMERIC';
    }

    protected function typeBoolean(ColumnDefinition $column)
    {
        return 'INTEGER';
    }

    protected function typeJson(ColumnDefinition $column)
    {
        return 'TEXT';
    }

    protected function typeJsonb(ColumnDefinition $column)
    {
        return 'TEXT';
    }

    protected function typeDateTime(ColumnDefinition $column)
    {
        return 'DATETIME';
    }

    protected function typeTimestamp(ColumnDefinition $column)
    {
        return 'DATETIME';
    }

    protected function typeBinary(ColumnDefinition $column)
    {
        return 'BLOB';
    }

    /**
     * Modificador AUTO_INCREMENT para SQLite
     */
    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('autoIncrement')) {
            return 'PRIMARY KEY AUTOINCREMENT';
        }
    }

    /**
     * SQLite no soporta UNSIGNED
     */
    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite no soporta CHARSET
     */
    protected function modifyCharset(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite no soporta COLLATE de la misma forma
     */
    protected function modifyCollate(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite no soporta COMMENT
     */
    protected function modifyComment(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite no soporta AFTER
     */
    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite no soporta FIRST
     */
    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)
    {
        return null;
    }

    /**
     * SQLite tiene limitaciones con ALTER TABLE
     */
    public function compileAdd(Blueprint $blueprint)
    {
        $table = $this->wrap($blueprint->getTable());
        $columns = $this->getColumns($blueprint);

        return array_map(function ($column) use ($table) {
            return "ALTER TABLE {$table} ADD COLUMN {$column}";
        }, $columns);
    }

    /**
     * SQLite no soporta DROP COLUMN directamente (requiere recrear tabla)
     */
    public function compileDropColumn(Blueprint $blueprint, $command)
    {
        // SQLite requiere recrear la tabla para eliminar columnas
        // Este es un proceso complejo que requeriría código adicional
        throw new RuntimeException('SQLite does not support dropping columns. You need to recreate the table.');
    }

    /**
     * SQLite no soporta RENAME COLUMN en versiones antiguas
     */
    public function compileRenameColumn(Blueprint $blueprint, $command)
    {
        $table = $this->wrap($blueprint->getTable());
        $from = $this->wrap($command['from']);
        $to = $this->wrap($command['to']);

        return "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
    }

    /**
     * No hay opciones de tabla en SQLite
     */
    protected function addTableOptions(Blueprint $blueprint)
    {
        return '';
    }
}
