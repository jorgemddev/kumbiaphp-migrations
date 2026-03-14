<?php

namespace JorgeMdDev\KumbiaMigrations\Grammar;

use JorgeMdDev\KumbiaMigrations\Blueprint;
use JorgeMdDev\KumbiaMigrations\ColumnDefinition;

abstract class Grammar
{
    abstract public function compileCreate(Blueprint $blueprint);
    abstract public function compileAdd(Blueprint $blueprint);
    abstract public function compileDrop(Blueprint $blueprint);
    abstract public function compileDropColumn(Blueprint $blueprint, $command);
    abstract public function compileRenameColumn(Blueprint $blueprint, $command);
    abstract public function compilePrimary(Blueprint $blueprint, $command);
    abstract public function compileUnique(Blueprint $blueprint, $command);
    abstract public function compileIndex(Blueprint $blueprint, $command);
    abstract public function compileForeign(Blueprint $blueprint, $command);

    abstract protected function typeString(ColumnDefinition $column);
    abstract protected function typeText(ColumnDefinition $column);
    abstract protected function typeInteger(ColumnDefinition $column);
    abstract protected function typeBigInteger(ColumnDefinition $column);

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

    protected function getDefaultValue($value)
    {
        if ($value === null)  return 'NULL';
        if (is_bool($value))  return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string) $value;

        return "'" . addslashes($value) . "'";
    }

    protected function wrap($value)
    {
        if ($value === null || $value === '') return '``';
        if ($value === '*') return $value;

        return '`' . str_replace('`', '``', (string) $value) . '`';
    }

    protected function columnize($values)
    {
        return implode(', ', array_map([$this, 'wrap'], (array) $values));
    }
}
