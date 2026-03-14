<?php
/**
 * KumbiaPHP Migration System
 *
 * Fachada para operaciones de esquema de base de datos
 *
 * @category Kumbia
 * @package Migration
 */

class Schema
{
    /**
     * Conexión de base de datos a usar
     *
     * @var string|null
     */
    protected static $connection = null;

    /**
     * Gramática a usar
     *
     * @var Grammar|null
     */
    protected static $grammar = null;

    /**
     * Establecer la conexión a usar
     *
     * @param string|null $connection
     * @return void
     */
    public static function connection($connection)
    {
        self::$connection = $connection;
        self::$grammar = null;
    }

    /**
     * Crear una nueva tabla
     *
     * @param string $table
     * @param callable $callback
     * @return void
     */
    public static function create($table, callable $callback)
    {
        $blueprint = new Blueprint($table);

        // Ejecutar el callback para definir la estructura
        $callback($blueprint);

        // Obtener el grammar según el driver
        $grammar = self::getGrammar();

        // Compilar y ejecutar
        $sql = $grammar->compileCreate($blueprint);
        self::execute($sql);

        // Ejecutar comandos adicionales (índices, foreign keys, etc)
        self::executeCommands($blueprint, $grammar);
    }

    /**
     * Modificar una tabla existente
     *
     * @param string $table
     * @param callable $callback
     * @return void
     */
    public static function table($table, callable $callback)
    {
        $blueprint = new Blueprint($table);

        // Ejecutar el callback para definir cambios
        $callback($blueprint);

        // Obtener el grammar según el driver
        $grammar = self::getGrammar();

        // Si hay columnas nuevas, agregarlas
        if (count($blueprint->getColumns()) > 0) {
            $statements = $grammar->compileAdd($blueprint);
            foreach ($statements as $sql) {
                self::execute($sql);
            }
        }

        // Ejecutar comandos (índices, foreign keys, drops, etc)
        self::executeCommands($blueprint, $grammar);
    }

    /**
     * Eliminar una tabla
     *
     * @param string $table
     * @return void
     */
    public static function drop($table)
    {
        $blueprint = new Blueprint($table);
        $grammar = self::getGrammar();
        $sql = $grammar->compileDrop($blueprint);
        self::execute($sql);
    }

    /**
     * Eliminar una tabla si existe
     *
     * @param string $table
     * @return void
     */
    public static function dropIfExists($table)
    {
        $blueprint = new Blueprint($table);
        $grammar = self::getGrammar();
        $sql = $grammar->compileDropIfExists($blueprint);
        self::execute($sql);
    }

    /**
     * Renombrar una tabla
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public static function rename($from, $to)
    {
        $blueprint = new Blueprint($from);
        $blueprint->rename($to);

        $grammar = self::getGrammar();
        $commands = $blueprint->getCommands();

        foreach ($commands as $command) {
            if ($command['name'] === 'rename') {
                $sql = $grammar->compileRename($blueprint, $command);
                self::execute($sql);
            }
        }
    }

    /**
     * Verificar si una tabla existe
     *
     * @param string $table
     * @return bool
     */
    public static function hasTable($table)
    {
        return MigrationDatabase::hasTable($table, self::$connection);
    }

    /**
     * Verificar si una columna existe en una tabla
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function hasColumn($table, $column)
    {
        $pdo = MigrationDatabase::getConnection(self::$connection);
        $driver = MigrationDatabase::getDriverName(self::$connection);

        try {
            switch ($driver) {
                case 'mysql':
                    // Sanitizar nombres para evitar SQL injection
                    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
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
                    $stmt = $pdo->query("PRAGMA table_info(`{$safeTable}`)");
                    $columns = $stmt->fetchAll();
                    foreach ($columns as $col) {
                        if ($col['name'] === $column) {
                            return true;
                        }
                    }
                    return false;

                default:
                    return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ejecutar comandos del blueprint
     *
     * @param Blueprint $blueprint
     * @param Grammar $grammar
     * @return void
     */
    protected static function executeCommands(Blueprint $blueprint, Grammar $grammar)
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

    /**
     * Ejecutar una sentencia SQL
     *
     * @param string $sql
     * @return void
     */
    protected static function execute($sql)
    {
        $pdo = MigrationDatabase::getConnection(self::$connection);

        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Migration failed: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }

    /**
     * Obtener la gramática según el driver
     *
     * @return Grammar
     */
    protected static function getGrammar()
    {
        if (self::$grammar !== null) {
            return self::$grammar;
        }

        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                require_once __DIR__ . '/grammar/MySqlGrammar.php';
                self::$grammar = new MySqlGrammar();
                break;

            case 'pgsql':
                require_once __DIR__ . '/grammar/PostgresGrammar.php';
                self::$grammar = new PostgresGrammar();
                break;

            case 'sqlite':
                require_once __DIR__ . '/grammar/SQLiteGrammar.php';
                self::$grammar = new SQLiteGrammar();
                break;

            default:
                throw new RuntimeException("Unsupported database driver: {$driver}");
        }

        return self::$grammar;
    }

    /**
     * Desactivar las verificaciones de claves foráneas
     *
     * @return void
     */
    public static function disableForeignKeyConstraints()
    {
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                self::execute('SET FOREIGN_KEY_CHECKS=0');
                break;
            case 'pgsql':
                self::execute('SET CONSTRAINTS ALL DEFERRED');
                break;
            case 'sqlite':
                self::execute('PRAGMA foreign_keys = OFF');
                break;
        }
    }

    /**
     * Activar las verificaciones de claves foráneas
     *
     * @return void
     */
    public static function enableForeignKeyConstraints()
    {
        $driver = MigrationDatabase::getDriverName(self::$connection);

        switch ($driver) {
            case 'mysql':
                self::execute('SET FOREIGN_KEY_CHECKS=1');
                break;
            case 'pgsql':
                self::execute('SET CONSTRAINTS ALL IMMEDIATE');
                break;
            case 'sqlite':
                self::execute('PRAGMA foreign_keys = ON');
                break;
        }
    }

    /**
     * Ejecutar callback sin verificaciones de claves foráneas
     *
     * @param callable $callback
     * @return void
     */
    public static function withoutForeignKeyConstraints(callable $callback)
    {
        self::disableForeignKeyConstraints();

        try {
            $callback();
        } finally {
            self::enableForeignKeyConstraints();
        }
    }
}
