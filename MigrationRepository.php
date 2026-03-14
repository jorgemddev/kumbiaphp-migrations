<?php
/**
 * KumbiaPHP Migration System
 *
 * Repositorio para rastrear migraciones ejecutadas
 *
 * @category Kumbia
 * @package Migration
 */

class MigrationRepository
{
    /**
     * Nombre de la tabla de migraciones
     *
     * @var string
     */
    protected $table = 'migrations';

    /**
     * Nombre de la conexión
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param string|null $connection
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Obtener las migraciones ejecutadas
     *
     * @param int|null $steps Número de lotes a obtener
     * @return array
     */
    public function getRan($steps = null)
    {
        $query = "SELECT migration FROM {$this->table} ORDER BY batch ASC, migration ASC";

        if ($steps !== null) {
            $query = "SELECT migration FROM {$this->table}
                      WHERE batch >= (SELECT MAX(batch) FROM {$this->table}) - ? + 1
                      ORDER BY batch DESC, migration DESC";
        }

        $pdo = MigrationDatabase::getConnection($this->connection);

        if ($steps !== null) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$steps]);
        } else {
            $stmt = $pdo->query($query);
        }

        return array_column($stmt->fetchAll(), 'migration');
    }

    /**
     * Obtener las migraciones del último lote
     *
     * @return array
     */
    public function getLast()
    {
        $query = "SELECT migration FROM {$this->table}
                  WHERE batch = (SELECT MAX(batch) FROM {$this->table})
                  ORDER BY migration DESC";

        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query($query);

        return array_column($stmt->fetchAll(), 'migration');
    }

    /**
     * Obtener información completa de las migraciones
     *
     * @return array
     */
    public function getMigrations()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY batch ASC, migration ASC";

        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query($query);

        return $stmt->fetchAll();
    }

    /**
     * Registrar una migración como ejecutada
     *
     * @param string $file
     * @param int $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->prepare("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$file, $batch]);
    }

    /**
     * Eliminar una migración del registro
     *
     * @param string $migration
     * @return void
     */
    public function delete($migration)
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    /**
     * Obtener el siguiente número de lote
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();

        return ((int) $result['batch']) + 1;
    }

    /**
     * Obtener el último número de lote
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();

        return (int) $result['batch'];
    }

    /**
     * Crear la tabla de migraciones
     *
     * @return void
     */
    public function createRepository()
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $driver = MigrationDatabase::getDriverName($this->connection);

        $sql = $this->getCreateTableSQL($driver);
        $pdo->exec($sql);
    }

    /**
     * Obtener el SQL para crear la tabla según el driver
     *
     * @param string $driver
     * @return string
     */
    protected function getCreateTableSQL($driver)
    {
        switch ($driver) {
            case 'mysql':
                return "CREATE TABLE {$this->table} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            case 'pgsql':
                return "CREATE TABLE {$this->table} (
                    id SERIAL PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";

            case 'sqlite':
                return "CREATE TABLE {$this->table} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";

            default:
                throw new RuntimeException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Verificar si el repositorio existe
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return MigrationDatabase::hasTable($this->table, $this->connection);
    }

    /**
     * Eliminar el repositorio de migraciones
     *
     * @return void
     */
    public function deleteRepository()
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $pdo->exec("DROP TABLE IF EXISTS {$this->table}");
    }
}
