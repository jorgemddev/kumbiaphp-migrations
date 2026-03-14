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
