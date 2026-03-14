<?php
/**
 * KumbiaPHP Migration System
 *
 * Manejador de conexiones de base de datos para migraciones
 *
 * @category Kumbia
 * @package Migration
 */

class MigrationDatabase
{
    /**
     * Conexiones PDO almacenadas
     *
     * @var array
     */
    private static $connections = [];

    /**
     * Configuración de bases de datos
     *
     * @var array
     */
    private static $config = null;

    /**
     * Obtener una conexión PDO
     *
     * @param string|null $connection Nombre de la conexión (development, production, etc)
     * @return \PDO
     * @throws RuntimeException
     */
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

    /**
     * Crear una nueva conexión PDO
     *
     * @param string $connection
     * @return \PDO
     * @throws RuntimeException
     */
    private static function createConnection($connection)
    {
        if (!isset(self::$config[$connection])) {
            throw new RuntimeException("Database connection [{$connection}] not configured.");
        }

        $config = self::$config[$connection];
        $type = $config['type'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $name = $config['name'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $port = $config['port'] ?? self::getDefaultPort($type);

        try {
            $dsn = self::buildDsn($type, $host, $name, $port, $charset);

            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Configuraciones específicas por tipo de BD
            if ($type === 'mysql') {
                $pdo->exec("SET NAMES '{$charset}'");
            }

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException("Could not connect to database: " . $e->getMessage());
        }
    }

    /**
     * Construir DSN según el tipo de base de datos
     *
     * @param string $type
     * @param string $host
     * @param string $name
     * @param int $port
     * @param string $charset
     * @return string
     */
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
                throw new RuntimeException("Unsupported database type: {$type}");
        }
    }

    /**
     * Obtener puerto por defecto según el tipo de BD
     *
     * @param string $type
     * @return int
     */
    private static function getDefaultPort($type)
    {
        $ports = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'postgresql' => 5432,
        ];

        return $ports[$type] ?? 3306;
    }

    /**
     * Cargar configuración de bases de datos
     *
     * @return void
     */
    private static function loadConfig()
    {
        $configFile = APP_PATH . 'config/databases.php';

        if (!file_exists($configFile)) {
            throw new RuntimeException("Database configuration file not found.");
        }

        self::$config = require $configFile;
    }

    /**
     * Obtener la conexión por defecto según el entorno
     *
     * @return string
     */
    private static function getDefaultConnection()
    {
        // Determinar entorno (production o development)
        if (defined('PRODUCTION') && PRODUCTION) {
            return 'production';
        }

        return 'development';
    }

    /**
     * Obtener el tipo de base de datos de una conexión
     *
     * @param string|null $connection
     * @return string
     */
    public static function getDriverName($connection = null)
    {
        $pdo = self::getConnection($connection);
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Cerrar una conexión
     *
     * @param string|null $connection
     * @return void
     */
    public static function disconnect($connection = null)
    {
        if ($connection === null) {
            $connection = self::getDefaultConnection();
        }

        if (isset(self::$connections[$connection])) {
            self::$connections[$connection] = null;
            unset(self::$connections[$connection]);
        }
    }

    /**
     * Cerrar todas las conexiones
     *
     * @return void
     */
    public static function disconnectAll()
    {
        foreach (array_keys(self::$connections) as $connection) {
            self::disconnect($connection);
        }
    }

    /**
     * Verificar si una tabla existe
     *
     * @param string $table
     * @param string|null $connection
     * @return bool
     */
    public static function hasTable($table, $connection = null)
    {
        $pdo = self::getConnection($connection);
        $driver = self::getDriverName($connection);

        try {
            switch ($driver) {
                case 'mysql':
                    // No usar prepared statements con SHOW TABLES (MariaDB no lo soporta)
                    // Sanitizamos el nombre de la tabla para evitar SQL injection
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
        } catch (PDOException $e) {
            return false;
        }
    }
}
