<?php

namespace JorgeMdDev\KumbiaMigrations\Grammar;

use JorgeMdDev\KumbiaMigrations\Blueprint;
use JorgeMdDev\KumbiaMigrations\ColumnDefinition;

class MySqlGrammar extends Grammar
{
    protected $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'Nullable', 'Default',
        'Increment', 'Comment', 'After', 'First',
    ];

    public function compileCreate(Blueprint $blueprint)
    {
        $columns = implode(', ', $this->getColumns($blueprint));
        $sql     = $blueprint->temporary ? 'CREATE TEMPORARY TABLE ' : 'CREATE TABLE IF NOT EXISTS ';
        $sql    .= $this->wrap($blueprint->getTable()) . " ({$columns})";
        $sql    .= $this->addTableOptions($blueprint);

        return $sql;
    }

    public function compileAdd(Blueprint $blueprint)
    {
        $table = $this->wrap($blueprint->getTable());

        return array_map(function ($column) use ($table) {
            return "ALTER TABLE {$table} ADD {$column}";
        }, $this->getColumns($blueprint));
    }

    public function compileDrop(Blueprint $blueprint)
    {
        return 'DROP TABLE ' . $this->wrap($blueprint->getTable());
    }

    public function compileDropIfExists(Blueprint $blueprint)
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrap($blueprint->getTable());
    }

    public function compileDropColumn(Blueprint $blueprint, $command)
    {
        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP {$this->columnize($command['columns'])}";
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

    public function compilePrimary(Blueprint $blueprint, $command)
    {
        return "ALTER TABLE {$this->wrap($blueprint->getTable())} ADD PRIMARY KEY ({$this->columnize($command['columns'])})";
    }

    public function compileUnique(Blueprint $blueprint, $command)
    {
        $index = $command['index'] ?? $this->createIndexName('unique', $command['columns']);
        return "ALTER TABLE {$this->wrap($blueprint->getTable())} ADD UNIQUE {$index} ({$this->columnize($command['columns'])})";
    }

    public function compileIndex(Blueprint $blueprint, $command)
    {
        $index = $command['index'] ?? $this->createIndexName('index', $command['columns']);
        return "ALTER TABLE {$this->wrap($blueprint->getTable())} ADD INDEX {$index} ({$this->columnize($command['columns'])})";
    }

    public function compileForeign(Blueprint $blueprint, $command)
    {
        if (empty($command['on']) || empty($command['references'])) {
            throw new \RuntimeException(
                "Foreign key definition incomplete. Make sure to call ->references('column')->on('table')"
            );
        }

        $sql  = "ALTER TABLE {$this->wrap($blueprint->getTable())} ADD CONSTRAINT ";
        $sql .= $this->wrap($this->createIndexName('foreign', (array) $command['columns']));
        $sql .= " FOREIGN KEY ({$this->columnize((array) $command['columns'])})";
        $sql .= " REFERENCES {$this->wrap($command['on'])} ({$this->columnize((array) $command['references'])})";

        if (isset($command['onDelete'])) $sql .= ' ON DELETE ' . strtoupper($command['onDelete']);
        if (isset($command['onUpdate'])) $sql .= ' ON UPDATE ' . strtoupper($command['onUpdate']);

        return $sql;
    }

    public function compileDropPrimary(Blueprint $blueprint, $command)
    {
        return 'ALTER TABLE ' . $this->wrap($blueprint->getTable()) . ' DROP PRIMARY KEY';
    }

    public function compileDropUnique(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('unique', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP INDEX {$this->wrap($index)}";
    }

    public function compileDropIndex(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('index', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP INDEX {$this->wrap($index)}";
    }

    public function compileDropForeign(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('foreign', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP FOREIGN KEY {$this->wrap($index)}";
    }

    public function compileRename(Blueprint $blueprint, $command)
    {
        return "RENAME TABLE {$this->wrap($blueprint->getTable())} TO {$this->wrap($command['to'])}";
    }

    protected function getColumns(Blueprint $blueprint)
    {
        return array_map(function ($column) use ($blueprint) {
            $sql       = $this->wrap($column->get('name')) . ' ' . $this->getType($column);
            $modifiers = $this->getColumnModifiers($blueprint, $column);
            return $modifiers ? $sql . ' ' . $modifiers : $sql;
        }, $blueprint->getColumns());
    }

    protected function getType(ColumnDefinition $column)
    {
        $method = 'type' . ucfirst($column->get('type'));

        if (method_exists($this, $method)) {
            return $this->$method($column);
        }

        throw new \RuntimeException("Type [{$column->get('type')}] not supported.");
    }

    protected function typeString(ColumnDefinition $column)  { return 'VARCHAR(' . ($column->get('length') ?? 255) . ')'; }
    protected function typeChar(ColumnDefinition $column)    { return 'CHAR(' . ($column->get('length') ?? 255) . ')'; }
    protected function typeText(ColumnDefinition $column)    { return 'TEXT'; }
    protected function typeMediumText(ColumnDefinition $column) { return 'MEDIUMTEXT'; }
    protected function typeLongText(ColumnDefinition $column)   { return 'LONGTEXT'; }
    protected function typeInteger(ColumnDefinition $column)    { return 'INT'; }
    protected function typeTinyInteger(ColumnDefinition $column)   { return 'TINYINT'; }
    protected function typeSmallInteger(ColumnDefinition $column)  { return 'SMALLINT'; }
    protected function typeMediumInteger(ColumnDefinition $column) { return 'MEDIUMINT'; }
    protected function typeBigInteger(ColumnDefinition $column)    { return 'BIGINT'; }
    protected function typeBoolean(ColumnDefinition $column)   { return 'TINYINT(1)'; }
    protected function typeJson(ColumnDefinition $column)      { return 'JSON'; }
    protected function typeJsonb(ColumnDefinition $column)     { return 'JSON'; }
    protected function typeDate(ColumnDefinition $column)      { return 'DATE'; }
    protected function typeDateTime(ColumnDefinition $column)  { return 'DATETIME'; }
    protected function typeDateTimeTz(ColumnDefinition $column){ return 'DATETIME'; }
    protected function typeTime(ColumnDefinition $column)      { return 'TIME'; }
    protected function typeTimestamp(ColumnDefinition $column) { return 'TIMESTAMP'; }
    protected function typeTimestampTz(ColumnDefinition $column){ return 'TIMESTAMP'; }
    protected function typeBinary(ColumnDefinition $column)    { return 'BLOB'; }
    protected function typeUuid(ColumnDefinition $column)      { return 'CHAR(36)'; }
    protected function typeIpAddress(ColumnDefinition $column) { return 'VARCHAR(45)'; }
    protected function typeMacAddress(ColumnDefinition $column){ return 'VARCHAR(17)'; }

    protected function typeFloat(ColumnDefinition $column)
    {
        return 'FLOAT(' . ($column->get('total') ?? 8) . ', ' . ($column->get('places') ?? 2) . ')';
    }

    protected function typeDouble(ColumnDefinition $column)
    {
        return ($column->get('total') && $column->get('places'))
            ? "DOUBLE({$column->get('total')}, {$column->get('places')})"
            : 'DOUBLE';
    }

    protected function typeDecimal(ColumnDefinition $column)
    {
        return 'DECIMAL(' . ($column->get('total') ?? 8) . ', ' . ($column->get('places') ?? 2) . ')';
    }

    protected function typeEnum(ColumnDefinition $column)
    {
        $allowed = array_map(fn($a) => "'{$a}'", $column->get('allowed'));
        return 'ENUM(' . implode(', ', $allowed) . ')';
    }

    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column)  { return $column->get('unsigned') ? 'UNSIGNED' : null; }
    protected function modifyCharset(Blueprint $blueprint, ColumnDefinition $column)   { return $column->get('charset') ? 'CHARACTER SET ' . $column->get('charset') : null; }
    protected function modifyCollate(Blueprint $blueprint, ColumnDefinition $column)   { return $column->get('collation') ? 'COLLATE ' . $column->get('collation') : null; }
    protected function modifyNullable(Blueprint $blueprint, ColumnDefinition $column)  { return $column->get('nullable') ? 'NULL' : 'NOT NULL'; }
    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column) { return $column->get('autoIncrement') ? 'AUTO_INCREMENT PRIMARY KEY' : null; }
    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)     { return $column->get('after') ? 'AFTER ' . $this->wrap($column->get('after')) : null; }
    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)     { return $column->get('first') ? 'FIRST' : null; }

    protected function modifyDefault(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('useCurrent'))       return 'DEFAULT CURRENT_TIMESTAMP';
        if ($column->get('default') !== null) return 'DEFAULT ' . $this->getDefaultValue($column->get('default'));
        return null;
    }

    protected function modifyComment(Blueprint $blueprint, ColumnDefinition $column)
    {
        return $column->get('comment') ? "COMMENT '" . addslashes($column->get('comment')) . "'" : null;
    }

    protected function addTableOptions(Blueprint $blueprint)
    {
        $options = [];
        if ($blueprint->engine)    $options[] = "ENGINE = {$blueprint->engine}";
        if ($blueprint->charset)   $options[] = "DEFAULT CHARACTER SET = {$blueprint->charset}";
        if ($blueprint->collation) $options[] = "COLLATE = {$blueprint->collation}";

        return $options ? ' ' . implode(' ', $options) : '';
    }

    protected function createIndexName($type, $columns)
    {
        return strtolower(implode('_', (array) $columns)) . "_{$type}";
    }
}
