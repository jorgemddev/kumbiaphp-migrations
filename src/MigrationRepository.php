<?php

namespace JorgeMdDev\KumbiaMigrations;

class MigrationRepository
{
    protected $table = 'migrations';
    protected $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }

    public function getRan($steps = null)
    {
        $pdo = MigrationDatabase::getConnection($this->connection);

        if ($steps !== null) {
            $stmt = $pdo->prepare(
                "SELECT migration FROM {$this->table}
                 WHERE batch >= (SELECT MAX(batch) FROM {$this->table}) - ? + 1
                 ORDER BY batch DESC, migration DESC"
            );
            $stmt->execute([$steps]);
        } else {
            $stmt = $pdo->query("SELECT migration FROM {$this->table} ORDER BY batch ASC, migration ASC");
        }

        return array_column($stmt->fetchAll(), 'migration');
    }

    public function getLast()
    {
        $pdo  = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query(
            "SELECT migration FROM {$this->table}
             WHERE batch = (SELECT MAX(batch) FROM {$this->table})
             ORDER BY migration DESC"
        );

        return array_column($stmt->fetchAll(), 'migration');
    }

    public function getMigrations()
    {
        $pdo  = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->query("SELECT * FROM {$this->table} ORDER BY batch ASC, migration ASC");

        return $stmt->fetchAll();
    }

    public function log($file, $batch)
    {
        $pdo  = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->prepare("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$file, $batch]);
    }

    public function delete($migration)
    {
        $pdo  = MigrationDatabase::getConnection($this->connection);
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    public function getNextBatchNumber()
    {
        $pdo    = MigrationDatabase::getConnection($this->connection);
        $stmt   = $pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();

        return ((int) $result['batch']) + 1;
    }

    public function getLastBatchNumber()
    {
        $pdo    = MigrationDatabase::getConnection($this->connection);
        $stmt   = $pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();

        return (int) $result['batch'];
    }

    public function createRepository()
    {
        $pdo    = MigrationDatabase::getConnection($this->connection);
        $driver = MigrationDatabase::getDriverName($this->connection);
        $pdo->exec($this->getCreateTableSQL($driver));
    }

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
                throw new \RuntimeException("Unsupported database driver: {$driver}");
        }
    }

    public function repositoryExists()
    {
        return MigrationDatabase::hasTable($this->table, $this->connection);
    }

    public function deleteRepository()
    {
        $pdo = MigrationDatabase::getConnection($this->connection);
        $pdo->exec("DROP TABLE IF EXISTS {$this->table}");
    }
}
