<?php
/**
 * KumbiaPHP Migration System
 *
 * Gramática para MySQL
 *
 * @category Kumbia
 * @package Migration
 */

require_once __DIR__ . '/Grammar.php';

class MySqlGrammar extends Grammar
{
    /**
     * Modificadores de columna
     *
     * @var array
     */
    protected $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'Nullable', 'Default',
        'Increment', 'Comment', 'After', 'First'
    ];

    /**
     * Compilar CREATE TABLE
     */
    public function compileCreate(Blueprint $blueprint)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = $blueprint->temporary ? 'CREATE TEMPORARY TABLE ' : 'CREATE TABLE ';
        $sql .= $this->wrap($blueprint->getTable()) . " ({$columns})";

        // Agregar opciones de tabla
        $sql .= $this->addTableOptions($blueprint);

        return $sql;
    }

    /**
     * Compilar ADD COLUMN
     */
    public function compileAdd(Blueprint $blueprint)
    {
        $table = $this->wrap($blueprint->getTable());
        $columns = $this->getColumns($blueprint);

        return array_map(function ($column) use ($table) {
            return "ALTER TABLE {$table} ADD {$column}";
        }, $columns);
    }

    /**
     * Compilar DROP TABLE
     */
    public function compileDrop(Blueprint $blueprint)
    {
        return 'DROP TABLE ' . $this->wrap($blueprint->getTable());
    }

    /**
     * Compilar DROP IF EXISTS
     */
    public function compileDropIfExists(Blueprint $blueprint)
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrap($blueprint->getTable());
    }

    /**
     * Compilar DROP COLUMN
     */
    public function compileDropColumn(Blueprint $blueprint, $command)
    {
        $columns = $this->columnize($command['columns']);
        $table = $this->wrap($blueprint->getTable());

        return "ALTER TABLE {$table} DROP {$columns}";
    }

    /**
     * Compilar RENAME COLUMN
     */
    public function compileRenameColumn(Blueprint $blueprint, $command)
    {
        $table = $this->wrap($blueprint->getTable());
        $from = $this->wrap($command['from']);
        $to = $this->wrap($command['to']);

        return "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
    }

    /**
     * Compilar PRIMARY KEY
     */
    public function compilePrimary(Blueprint $blueprint, $command)
    {
        $columns = $this->columnize($command['columns']);
        $table = $this->wrap($blueprint->getTable());

        return "ALTER TABLE {$table} ADD PRIMARY KEY ({$columns})";
    }

    /**
     * Compilar UNIQUE
     */
    public function compileUnique(Blueprint $blueprint, $command)
    {
        $columns = $this->columnize($command['columns']);
        $table = $this->wrap($blueprint->getTable());
        $index = $command['index'] ?? $this->createIndexName('unique', $command['columns']);

        return "ALTER TABLE {$table} ADD UNIQUE {$index} ({$columns})";
    }

    /**
     * Compilar INDEX
     */
    public function compileIndex(Blueprint $blueprint, $command)
    {
        $columns = $this->columnize($command['columns']);
        $table = $this->wrap($blueprint->getTable());
        $index = $command['index'] ?? $this->createIndexName('index', $command['columns']);

        return "ALTER TABLE {$table} ADD INDEX {$index} ({$columns})";
    }

    /**
     * Compilar FOREIGN KEY
     */
    public function compileForeign(Blueprint $blueprint, $command)
    {
        // Validar que existan los campos requeridos
        if (empty($command['on']) || empty($command['references'])) {
            throw new RuntimeException(
                "Foreign key definition incomplete. Make sure to call ->references('column')->on('table')"
            );
        }

        $table = $this->wrap($blueprint->getTable());

        $columns = $this->columnize((array) $command['columns']);
        $foreignTable = $this->wrap($command['on']);
        $foreignColumns = $this->columnize((array) $command['references']);

        $sql = "ALTER TABLE {$table} ADD CONSTRAINT ";
        $sql .= $this->wrap($this->createIndexName('foreign', (array) $command['columns']));
        $sql .= " FOREIGN KEY ({$columns}) REFERENCES {$foreignTable} ({$foreignColumns})";

        if (isset($command['onDelete'])) {
            $sql .= " ON DELETE " . strtoupper($command['onDelete']);
        }

        if (isset($command['onUpdate'])) {
            $sql .= " ON UPDATE " . strtoupper($command['onUpdate']);
        }

        return $sql;
    }

    /**
     * Compilar DROP PRIMARY
     */
    public function compileDropPrimary(Blueprint $blueprint, $command)
    {
        return 'ALTER TABLE ' . $this->wrap($blueprint->getTable()) . ' DROP PRIMARY KEY';
    }

    /**
     * Compilar DROP UNIQUE
     */
    public function compileDropUnique(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('unique', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP INDEX {$this->wrap($index)}";
    }

    /**
     * Compilar DROP INDEX
     */
    public function compileDropIndex(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('index', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP INDEX {$this->wrap($index)}";
    }

    /**
     * Compilar DROP FOREIGN
     */
    public function compileDropForeign(Blueprint $blueprint, $command)
    {
        $index = is_array($command['index'])
            ? $this->createIndexName('foreign', $command['index'])
            : $command['index'];

        return "ALTER TABLE {$this->wrap($blueprint->getTable())} DROP FOREIGN KEY {$this->wrap($index)}";
    }

    /**
     * Compilar RENAME TABLE
     */
    public function compileRename(Blueprint $blueprint, $command)
    {
        $from = $this->wrap($blueprint->getTable());
        $to = $this->wrap($command['to']);

        return "RENAME TABLE {$from} TO {$to}";
    }

    /**
     * Obtener definiciones de columnas
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = [];

        foreach ($blueprint->getColumns() as $column) {
            $sql = $this->wrap($column->get('name')) . ' ' . $this->getType($column);
            $modifiers = $this->getColumnModifiers($blueprint, $column);
            if ($modifiers) {
                $sql .= ' ' . $modifiers;
            }
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Obtener tipo de columna
     */
    protected function getType(ColumnDefinition $column)
    {
        $type = $column->get('type');
        $method = 'type' . ucfirst($type);

        if (method_exists($this, $method)) {
            return $this->$method($column);
        }

        throw new RuntimeException("Type [{$type}] not supported.");
    }

    /**
     * Tipos de columna
     */
    protected function typeString(ColumnDefinition $column)
    {
        return "VARCHAR(" . ($column->get('length') ?? 255) . ")";
    }

    protected function typeChar(ColumnDefinition $column)
    {
        return "CHAR(" . ($column->get('length') ?? 255) . ")";
    }

    protected function typeText(ColumnDefinition $column)
    {
        return 'TEXT';
    }

    protected function typeMediumText(ColumnDefinition $column)
    {
        return 'MEDIUMTEXT';
    }

    protected function typeLongText(ColumnDefinition $column)
    {
        return 'LONGTEXT';
    }

    protected function typeInteger(ColumnDefinition $column)
    {
        return 'INT';
    }

    protected function typeTinyInteger(ColumnDefinition $column)
    {
        return 'TINYINT';
    }

    protected function typeSmallInteger(ColumnDefinition $column)
    {
        return 'SMALLINT';
    }

    protected function typeMediumInteger(ColumnDefinition $column)
    {
        return 'MEDIUMINT';
    }

    protected function typeBigInteger(ColumnDefinition $column)
    {
        return 'BIGINT';
    }

    protected function typeFloat(ColumnDefinition $column)
    {
        $precision = $column->get('total') ?? 8;
        $scale = $column->get('places') ?? 2;
        return "FLOAT({$precision}, {$scale})";
    }

    protected function typeDouble(ColumnDefinition $column)
    {
        if ($column->get('total') && $column->get('places')) {
            return "DOUBLE({$column->get('total')}, {$column->get('places')})";
        }
        return 'DOUBLE';
    }

    protected function typeDecimal(ColumnDefinition $column)
    {
        $precision = $column->get('total') ?? 8;
        $scale = $column->get('places') ?? 2;
        return "DECIMAL({$precision}, {$scale})";
    }

    protected function typeBoolean(ColumnDefinition $column)
    {
        return 'TINYINT(1)';
    }

    protected function typeEnum(ColumnDefinition $column)
    {
        $allowed = array_map(function ($a) {
            return "'{$a}'";
        }, $column->get('allowed'));
        return 'ENUM(' . implode(', ', $allowed) . ')';
    }

    protected function typeJson(ColumnDefinition $column)
    {
        return 'JSON';
    }

    protected function typeJsonb(ColumnDefinition $column)
    {
        return 'JSON';
    }

    protected function typeDate(ColumnDefinition $column)
    {
        return 'DATE';
    }

    protected function typeDateTime(ColumnDefinition $column)
    {
        return 'DATETIME';
    }

    protected function typeDateTimeTz(ColumnDefinition $column)
    {
        return 'DATETIME';
    }

    protected function typeTime(ColumnDefinition $column)
    {
        return 'TIME';
    }

    protected function typeTimestamp(ColumnDefinition $column)
    {
        return 'TIMESTAMP';
    }

    protected function typeTimestampTz(ColumnDefinition $column)
    {
        return 'TIMESTAMP';
    }

    protected function typeBinary(ColumnDefinition $column)
    {
        return 'BLOB';
    }

    protected function typeUuid(ColumnDefinition $column)
    {
        return 'CHAR(36)';
    }

    protected function typeIpAddress(ColumnDefinition $column)
    {
        return 'VARCHAR(45)';
    }

    protected function typeMacAddress(ColumnDefinition $column)
    {
        return 'VARCHAR(17)';
    }

    /**
     * Modificadores
     */
    protected function modifyUnsigned(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('unsigned')) {
            return 'UNSIGNED';
        }
    }

    protected function modifyCharset(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('charset')) {
            return 'CHARACTER SET ' . $column->get('charset');
        }
    }

    protected function modifyCollate(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('collation')) {
            return 'COLLATE ' . $column->get('collation');
        }
    }

    protected function modifyNullable(Blueprint $blueprint, ColumnDefinition $column)
    {
        return $column->get('nullable') ? 'NULL' : 'NOT NULL';
    }

    protected function modifyDefault(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('useCurrent')) {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        if ($column->get('default') !== null) {
            return 'DEFAULT ' . $this->getDefaultValue($column->get('default'));
        }
    }

    protected function modifyIncrement(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('autoIncrement')) {
            return 'AUTO_INCREMENT PRIMARY KEY';
        }
    }

    protected function modifyComment(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('comment')) {
            return "COMMENT '" . addslashes($column->get('comment')) . "'";
        }
    }

    protected function modifyAfter(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('after')) {
            return 'AFTER ' . $this->wrap($column->get('after'));
        }
    }

    protected function modifyFirst(Blueprint $blueprint, ColumnDefinition $column)
    {
        if ($column->get('first')) {
            return 'FIRST';
        }
    }

    /**
     * Agregar opciones de tabla
     */
    protected function addTableOptions(Blueprint $blueprint)
    {
        $options = [];

        if ($blueprint->engine) {
            $options[] = "ENGINE = {$blueprint->engine}";
        }

        if ($blueprint->charset) {
            $options[] = "DEFAULT CHARACTER SET = {$blueprint->charset}";
        }

        if ($blueprint->collation) {
            $options[] = "COLLATE = {$blueprint->collation}";
        }

        return count($options) > 0 ? ' ' . implode(' ', $options) : '';
    }

    /**
     * Crear nombre de índice
     */
    protected function createIndexName($type, $columns)
    {
        $index = strtolower(implode('_', (array) $columns));
        return "{$index}_{$type}";
    }
}
