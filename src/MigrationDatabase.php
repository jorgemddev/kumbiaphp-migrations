<?php

namespace JorgeMdDev\KumbiaMigrations;

class MigrationDatabase
{
    private static $connections = [];
    private static $config = null;

    public static function getConnection($connection = null)
    {
        if (self::$config === null) {
            self::loadConfig();
        }

        if ($connection === null) {
            $connection = self::getDefaultConnection();
        }

        if (!isset(self::$connections[$connection])) {
            self::$connections[$connection] = self::createConnection($connection);
        }

        return self::$connections[$connection];
    }

    private static function createConnection($connection)
    {
        if (!isset(self::$config[$connection])) {
            throw new \RuntimeException("Database connection [{$connection}] not configured.");
        }

        $config   = self::$config[$connection];
        $type     = $config['type']     ?? 'mysql';
        $host     = $config['host']     ?? 'localhost';
        $name     = $config['name']     ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset  = $config['charset']  ?? 'utf8mb4';
        $port     = $config['port']     ?? self::getDefaultPort($type);

        try {
            $dsn = self::buildDsn($type, $host, $name, $port, $charset);

            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            if ($type === 'mysql') {
                $pdo->exec("SET NAMES '{$charset}'");
            }

            return $pdo;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Could not connect to database: " . $e->getMessage());
        }
    }

    private static function buildDsn($type, $host, $name, $port, $charset)
    {
        switch ($type) {
            case 'mysql':
                return "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            case 'pgsql':
            case 'postgresql':
                return "pgsql:host={$host};port={$port};dbname={$name}";
            case 'sqlite':
                return "sqlite:{$name}";
            default:
                throw new \RuntimeException("Unsupported database type: {$type}");
        }
    }

    private static function getDefaultPort($type)
    {
        $ports = [
            'mysql'      => 3306,
            'pgsql'      => 5432,
            'postgresql' => 5432,
        ];

        return $ports[$type] ?? 3306;
    }

    private static function loadConfig()
    {
        $configFile = APP_PATH . 'config/databases.php';

        if (!file_exists($configFile)) {
            throw new \RuntimeException("Database configuration file not found: {$configFile}");
        }

        self::$config = require $configFile;
    }

    private static function getDefaultConnection()
    {
        return (defined('PRODUCTION') && PRODUCTION) ? 'production' : 'development';
    }

    public static function getDriverName($connection = null)
    {
        return self::getConnection($connection)->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public static function disconnect($connection = null)
    {
        $connection = $connection ?? self::getDefaultConnection();

        if (isset(self::$connections[$connection])) {
            self::$connections[$connection] = null;
            unset(self::$connections[$connection]);
        }
    }

    public static function disconnectAll()
    {
        foreach (array_keys(self::$connections) as $connection) {
            self::disconnect($connection);
        }
    }

    public static function hasTable($table, $connection = null)
    {
        $pdo    = self::getConnection($connection);
        $driver = self::getDriverName($connection);

        try {
            switch ($driver) {
                case 'mysql':
                    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                    $stmt = $pdo->query("SHOW TABLES LIKE '{$safeTable}'");
                    return $stmt->fetch() !== false;

                case 'pgsql':
                    $stmt = $pdo->prepare("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename = ?");
                    $stmt->execute([$table]);
                    return $stmt->fetch() !== false;

                case 'sqlite':
                    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                    $stmt->execute([$table]);
                    return $stmt->fetch() !== false;

                default:
                    return false;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }
}
