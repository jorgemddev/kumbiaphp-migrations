<?php
/**
 * KumbiaPHP Migration System
 *
 * Clase base para gramáticas de base de datos
 *
 * @category Kumbia
 * @package Migration
 */

abstract class Grammar
{
    /**
     * Compilar comando CREATE TABLE
     *
     * @param Blueprint $blueprint
     * @return string
     */
    abstract public function compileCreate(Blueprint $blueprint);

    /**
     * Compilar comando ADD COLUMN
     *
     * @param Blueprint $blueprint
     * @return array
     */
    abstract public function compileAdd(Blueprint $blueprint);

    /**
     * Compilar comando DROP TABLE
     *
     * @param Blueprint $blueprint
     * @return string
     */
    abstract public function compileDrop(Blueprint $blueprint);

    /**
     * Compilar comando DROP COLUMN
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compileDropColumn(Blueprint $blueprint, $command);

    /**
     * Compilar comando RENAME COLUMN
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compileRenameColumn(Blueprint $blueprint, $command);

    /**
     * Compilar comando PRIMARY KEY
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compilePrimary(Blueprint $blueprint, $command);

    /**
     * Compilar comando UNIQUE
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compileUnique(Blueprint $blueprint, $command);

    /**
     * Compilar comando INDEX
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compileIndex(Blueprint $blueprint, $command);

    /**
     * Compilar comando FOREIGN KEY
     *
     * @param Blueprint $blueprint
     * @param array $command
     * @return string
     */
    abstract public function compileForeign(Blueprint $blueprint, $command);

    /**
     * Compilar tipo de columna
     *
     * @param ColumnDefinition $column
     * @return string
     */
    abstract protected function typeString(ColumnDefinition $column);
    abstract protected function typeText(ColumnDefinition $column);
    abstract protected function typeInteger(ColumnDefinition $column);
    abstract protected function typeBigInteger(ColumnDefinition $column);

    /**
     * Obtener modificadores de columna
     *
     * @param Blueprint $blueprint
     * @param ColumnDefinition $column
     * @return string
     */
    protected function getColumnModifiers(Blueprint $blueprint, ColumnDefinition $column)
    {
        $modifiers = [];

        foreach ($this->modifiers as $modifier) {
            $method = "modify{$modifier}";
            if (method_exists($this, $method)) {
                $result = $this->$method($blueprint, $column);
                if ($result !== null) {
                    $modifiers[] = $result;
                }
            }
        }

        return implode(' ', $modifiers);
    }

    /**
     * Formatear valor por defecto
     *
     * @param mixed $value
     * @return string
     */
    protected function getDefaultValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes($value) . "'";
    }

    /**
     * Envolver nombre de tabla o columna
     *
     * @param string|null $value
     * @return string
     */
    protected function wrap($value)
    {
        if ($value === null || $value === '') {
            return '``';
        }

        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('`', '``', (string) $value) . '`';
    }

    /**
     * Convertir array o string a string separado por comas
     *
     * @param array|string $values
     * @return string
     */
    protected function columnize($values)
    {
        // Convertir a array si es string
        $values = (array) $values;
        return implode(', ', array_map([$this, 'wrap'], $values));
    }
}
