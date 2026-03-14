<?php

namespace JorgeMdDev\KumbiaMigrations;

use JorgeMdDev\KumbiaMigrations\Grammar\MySqlGrammar;
use JorgeMdDev\KumbiaMigrations\Grammar\PostgresGrammar;
use JorgeMdDev\KumbiaMigrations\Grammar\SQLiteGrammar;

class Schema
{
    protected static $connection = null;
    protected static $grammar    = null;

    public static function connection($connection)
    {
        self::$connection = $connection;
        self::$grammar    = null;
    }

    public static function create($table, callable $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $grammar = self::getGrammar();
        self::execute($grammar->compileCreate($blueprint));
        self::executeCommands($blueprint, $grammar);
    }

    public static function table($table, callable $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $grammar = self::getGrammar();

        if (count($blueprint->getColumns()) > 0) {
            foreach ($grammar->compileAdd($blueprint) as $sql) {
                self::execute($sql);
            }
        }

        self::executeCommands($blueprint, $grammar);
    }

    public static function drop($table)
    {
        $blueprint = new Blueprint($table);
        self::execute(self::getGrammar()->compileDrop($blueprint));
    }

    public static function dropIfExists($table)
    {
        $blueprint = new Blueprint($table);
        self::execute(self::getGrammar()->compileDropIfExists($blueprint));
    }

    public static function rename($from, $to)
    {
        $blueprint = new Blueprint($from);
        $blueprint->rename($to);

        $grammar = self::getGrammar();
        foreach ($blueprint->getCommands() as $command) {
            if ($command['name'] === 'rename') {
                self::execute($grammar->compileRename($blueprint, $command));
            }
        }
    }

    public static function hasTable($table)
    {
        return MigrationDatabase::hasTable($table, self::$connection);
    }

    /**
     * Obtener todas las tablas de la base de datos.
     *
     * @return array
     */
    public static function getTables()
    {
        $pdo    = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                $stmt = $pdo->query('SHOW TABLES');
                return array_column($stmt->fetchAll(\PDO::FETCH_NUM), 0);

            case 'pgsql':
                $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
                return array_column($stmt->fetchAll(), 'tablename');

            case 'sqlite':
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
                return array_column($stmt->fetchAll(), 'name');

            default:
                return [];
        }
    }

    /**
     * Obtener todas las columnas de una tabla con su definición completa.
     *
     * Retorna array de:
     *   - name:     nombre de la columna
     *   - type:     tipo de dato
     *   - nullable: bool
     *   - default:  valor por defecto o null
     *   - key:      PRI | UNI | MUL | null (MySQL), PRIMARY | null (pgsql/sqlite)
     *   - extra:    auto_increment, etc (MySQL)
     *
     * @param  string $table
     * @return array
     */
    public static function getColumns($table)
    {
        $pdo    = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $stmt      = $pdo->query("SHOW FULL COLUMNS FROM `{$safeTable}`");
                return array_map(function ($row) {
                    return [
                        'name'     => $row['Field'],
                        'type'     => $row['Type'],
                        'nullable' => $row['Null'] === 'YES',
                        'default'  => $row['Default'],
                        'key'      => $row['Key'] ?: null,
                        'extra'    => $row['Extra'] ?: null,
                        'comment'  => $row['Comment'] ?: null,
                    ];
                }, $stmt->fetchAll());

            case 'pgsql':
                $stmt = $pdo->prepare(
                    "SELECT c.column_name, c.data_type, c.is_nullable, c.column_default,
                            CASE WHEN pk.column_name IS NOT NULL THEN 'PRIMARY' END AS key
                     FROM information_schema.columns c
                     LEFT JOIN (
                         SELECT ku.column_name
                         FROM information_schema.table_constraints tc
                         JOIN information_schema.key_column_usage ku
                           ON tc.constraint_name = ku.constraint_name
                         WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_name = ?
                     ) pk ON pk.column_name = c.column_name
                     WHERE c.table_name = ?
                     ORDER BY c.ordinal_position"
                );
                $stmt->execute([$table, $table]);
                return array_map(function ($row) {
                    return [
                        'name'     => $row['column_name'],
                        'type'     => $row['data_type'],
                        'nullable' => $row['is_nullable'] === 'YES',
                        'default'  => $row['column_default'],
                        'key'      => $row['key'],
                        'extra'    => null,
                        'comment'  => null,
                    ];
                }, $stmt->fetchAll());

            case 'sqlite':
                $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $stmt      = $pdo->query("PRAGMA table_info(`{$safeTable}`)");
                return array_map(function ($row) {
                    return [
                        'name'     => $row['name'],
                        'type'     => $row['type'],
                        'nullable' => !$row['notnull'],
                        'default'  => $row['dflt_value'],
                        'key'      => $row['pk'] ? 'PRIMARY' : null,
                        'extra'    => null,
                        'comment'  => null,
                    ];
                }, $stmt->fetchAll());

            default:
                return [];
        }
    }

    /**
     * Obtener los índices de una tabla.
     *
     * Retorna array de:
     *   - name:    nombre del índice
     *   - columns: array de columnas
     *   - unique:  bool
     *   - primary: bool
     *
     * @param  string $table
     * @return array
     */
    public static function getIndexes($table)
    {
        $pdo    = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $stmt      = $pdo->query("SHOW INDEX FROM `{$safeTable}`");
                $rows      = $stmt->fetchAll();
                $indexes   = [];

                foreach ($rows as $row) {
                    $name = $row['Key_name'];
                    if (!isset($indexes[$name])) {
                        $indexes[$name] = [
                            'name'    => $name,
                            'columns' => [],
                            'unique'  => !$row['Non_unique'],
                            'primary' => $name === 'PRIMARY',
                        ];
                    }
                    $indexes[$name]['columns'][] = $row['Column_name'];
                }

                return array_values($indexes);

            case 'pgsql':
                $stmt = $pdo->prepare(
                    "SELECT i.relname AS name, ix.indisunique AS unique, ix.indisprimary AS primary,
                            array_agg(a.attname ORDER BY a.attnum) AS columns
                     FROM pg_class t
                     JOIN pg_index ix ON t.oid = ix.indrelid
                     JOIN pg_class i  ON i.oid = ix.indexrelid
                     JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
                     WHERE t.relname = ?
                     GROUP BY i.relname, ix.indisunique, ix.indisprimary"
                );
                $stmt->execute([$table]);
                return array_map(function ($row) {
                    return [
                        'name'    => $row['name'],
                        'columns' => explode(',', trim($row['columns'], '{}')),
                        'unique'  => $row['unique'],
                        'primary' => $row['primary'],
                    ];
                }, $stmt->fetchAll());

            case 'sqlite':
                $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $stmt      = $pdo->query("PRAGMA index_list(`{$safeTable}`)");
                $indexes   = [];

                foreach ($stmt->fetchAll() as $row) {
                    $infoStmt = $pdo->query("PRAGMA index_info(`{$row['name']}`)" );
                    $indexes[] = [
                        'name'    => $row['name'],
                        'columns' => array_column($infoStmt->fetchAll(), 'name'),
                        'unique'  => (bool) $row['unique'],
                        'primary' => $row['origin'] === 'pk',
                    ];
                }

                return $indexes;

            default:
                return [];
        }
    }

    /**
     * Obtener las claves foráneas de una tabla.
     *
     * Retorna array de:
     *   - name:       nombre de la constraint
     *   - columns:    columnas locales
     *   - on_table:   tabla referenciada
     *   - references: columnas referenciadas
     *   - on_delete:  acción ON DELETE
     *   - on_update:  acción ON UPDATE
     *
     * @param  string $table
     * @return array
     */
    public static function getForeignKeys($table)
    {
        $pdo    = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                $stmt = $pdo->prepare(
                    "SELECT kcu.CONSTRAINT_NAME AS name,
                            kcu.COLUMN_NAME AS column_name,
                            kcu.REFERENCED_TABLE_NAME AS on_table,
                            kcu.REFERENCED_COLUMN_NAME AS ref_column,
                            rc.DELETE_RULE AS on_delete,
                            rc.UPDATE_RULE AS on_update
                     FROM information_schema.KEY_COLUMN_USAGE kcu
                     JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                       ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                     WHERE kcu.TABLE_NAME = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                     ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION"
                );
                $stmt->execute([$table]);
                $rows = $stmt->fetchAll();
                $fks  = [];

                foreach ($rows as $row) {
                    $name = $row['name'];
                    if (!isset($fks[$name])) {
                        $fks[$name] = [
                            'name'       => $name,
                            'columns'    => [],
                            'on_table'   => $row['on_table'],
                            'references' => [],
                            'on_delete'  => $row['on_delete'],
                            'on_update'  => $row['on_update'],
                        ];
                    }
                    $fks[$name]['columns'][]    = $row['column_name'];
                    $fks[$name]['references'][] = $row['ref_column'];
                }

                return array_values($fks);

            case 'pgsql':
                $stmt = $pdo->prepare(
                    "SELECT tc.constraint_name AS name,
                            kcu.column_name,
                            ccu.table_name AS on_table,
                            ccu.column_name AS ref_column,
                            rc.delete_rule AS on_delete,
                            rc.update_rule AS on_update
                     FROM information_schema.table_constraints tc
                     JOIN information_schema.key_column_usage kcu
                       ON tc.constraint_name = kcu.constraint_name
                     JOIN information_schema.referential_constraints rc
                       ON tc.constraint_name = rc.constraint_name
                     JOIN information_schema.constraint_column_usage ccu
                       ON rc.unique_constraint_name = ccu.constraint_name
                     WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ?
                     ORDER BY tc.constraint_name, kcu.ordinal_position"
                );
                $stmt->execute([$table]);
                $rows = $stmt->fetchAll();
                $fks  = [];

                foreach ($rows as $row) {
                    $name = $row['name'];
                    if (!isset($fks[$name])) {
                        $fks[$name] = [
                            'name'       => $name,
                            'columns'    => [],
                            'on_table'   => $row['on_table'],
                            'references' => [],
                            'on_delete'  => $row['on_delete'],
                            'on_update'  => $row['on_update'],
                        ];
                    }
                    $fks[$name]['columns'][]    = $row['column_name'];
                    $fks[$name]['references'][] = $row['ref_column'];
                }

                return array_values($fks);

            case 'sqlite':
                $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $stmt      = $pdo->query("PRAGMA foreign_key_list(`{$safeTable}`)");
                $rows      = $stmt->fetchAll();
                $fks       = [];

                foreach ($rows as $row) {
                    $id = $row['id'];
                    if (!isset($fks[$id])) {
                        $fks[$id] = [
                            'name'       => "fk_{$safeTable}_{$row['id']}",
                            'columns'    => [],
                            'on_table'   => $row['table'],
                            'references' => [],
                            'on_delete'  => $row['on_delete'],
                            'on_update'  => $row['on_update'],
                        ];
                    }
                    $fks[$id]['columns'][]    = $row['from'];
                    $fks[$id]['references'][] = $row['to'];
                }

                return array_values($fks);

            default:
                return [];
        }
    }

    public static function hasColumn($table, $column)
    {
        $pdo    = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        try {
            switch ($driver) {
                case 'mysql':
                    $safeTable  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
                    $stmt = $pdo->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
                    return $stmt->fetch() !== false;

                case 'pgsql':
                    $stmt = $pdo->prepare(
                        "SELECT column_name FROM information_schema.columns
                         WHERE table_name = ? AND column_name = ?"
                    );
                    $stmt->execute([$table, $column]);
                    return $stmt->fetch() !== false;

                case 'sqlite':
                    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                    $stmt      = $pdo->query("PRAGMA table_info(`{$safeTable}`)");
                    foreach ($stmt->fetchAll() as $col) {
                        if ($col['name'] === $column) {
                            return true;
                        }
                    }
                    return false;

                default:
                    return false;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function disableForeignKeyConstraints()
    {
        $driver = MigrationDatabase::getDriverName(self::$connection);
        $map    = [
            'mysql'  => 'SET FOREIGN_KEY_CHECKS=0',
            'pgsql'  => 'SET CONSTRAINTS ALL DEFERRED',
            'sqlite' => 'PRAGMA foreign_keys = OFF',
        ];
        if (isset($map[$driver])) {
            self::execute($map[$driver]);
        }
    }

    public static function enableForeignKeyConstraints()
    {
        $driver = MigrationDatabase::getDriverName(self::$connection);
        $map    = [
            'mysql'  => 'SET FOREIGN_KEY_CHECKS=1',
            'pgsql'  => 'SET CONSTRAINTS ALL IMMEDIATE',
            'sqlite' => 'PRAGMA foreign_keys = ON',
        ];
        if (isset($map[$driver])) {
            self::execute($map[$driver]);
        }
    }

    public static function withoutForeignKeyConstraints(callable $callback)
    {
        self::disableForeignKeyConstraints();
        try {
            $callback();
        } finally {
            self::enableForeignKeyConstraints();
        }
    }

    protected static function executeCommands(Blueprint $blueprint, $grammar)
    {
        foreach ($blueprint->getCommands() as $command) {
            $method = 'compile' . ucfirst($command['name']);
            if (method_exists($grammar, $method)) {
                $sql = $grammar->$method($blueprint, $command);
                if ($sql) {
                    self::execute($sql);
                }
            }
        }
    }

    protected static function execute($sql)
    {
        $pdo = MigrationDatabase::getConnection(self::$connection);
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Migration failed: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }

    protected static function getGrammar()
    {
        if (self::$grammar !== null) {
            return self::$grammar;
        }

        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                self::$grammar = new MySqlGrammar();
                break;
            case 'pgsql':
                self::$grammar = new PostgresGrammar();
                break;
            case 'sqlite':
                self::$grammar = new SQLiteGrammar();
                break;
            default:
                throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        return self::$grammar;
    }
}
