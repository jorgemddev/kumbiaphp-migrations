<?php
/**
 * KumbiaPHP Migration System
 *
 * Motor de ejecución de migraciones
 *
 * @category Kumbia
 * @package Migration
 */

class Migrator
{
    /**
     * Repositorio de migraciones
     *
     * @var MigrationRepository
     */
    protected $repository;

    /**
     * Directorio de migraciones
     *
     * @var string
     */
    protected $path;

    /**
     * Nombre de la conexión
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Notas de ejecución
     *
     * @var array
     */
    protected $notes = [];

    /**
     * Constructor
     *
     * @param string|null $connection
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection;
        $this->repository = new MigrationRepository($connection);
        $this->path = APP_PATH . 'migrations/';
    }

    /**
     * Ejecutar migraciones pendientes
     *
     * @param array $options
     * @return array Archivos ejecutados
     */
    public function run($options = [])
    {
        $this->notes = [];

        // Crear tabla de migraciones si no existe
        if (!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
            $this->note('<info>Migration table created successfully.</info>');
        }

        // Obtener archivos de migración
        $files = $this->getMigrationFiles();

        // Obtener migraciones ya ejecutadas
        $ran = $this->repository->getRan();

        // Filtrar migraciones pendientes
        $pending = array_diff($files, $ran);

        if (count($pending) === 0) {
            $this->note('<info>Nothing to migrate.</info>');
            return [];
        }

        // Obtener siguiente número de lote
        $batch = $this->repository->getNextBatchNumber();

        // Ejecutar cada migración pendiente
        $migrations = [];
        foreach ($pending as $file) {
            $this->runUp($file, $batch);
            $migrations[] = $file;
        }

        return $migrations;
    }

    /**
     * Ejecutar una migración "up"
     *
     * @param string $file
     * @param int $batch
     * @return void
     */
    protected function runUp($file, $batch)
    {
        $this->note("<comment>Migrating:</comment> {$file}");

        $migration = $this->resolve($file);

        $pdo = MigrationDatabase::getConnection($this->connection);

        try {
            if ($migration->withinTransaction()) {
                $pdo->beginTransaction();
            }

            $migration->up();

            if ($migration->withinTransaction()) {
                $pdo->commit();
            }

            // Registrar migración como ejecutada
            $this->repository->log($file, $batch);

            $this->note("<info>Migrated:</info>  {$file}");
        } catch (Exception $e) {
            if ($migration->withinTransaction() && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->note("<error>Migration failed:</error> {$file}");
            throw $e;
        }
    }

    /**
     * Revertir la última migración
     *
     * @param array $options
     * @return array
     */
    public function rollback($options = [])
    {
        $this->notes = [];

        if (!$this->repository->repositoryExists()) {
            $this->note('<error>Migration table not found.</error>');
            return [];
        }

        $migrations = $this->repository->getLast();

        if (count($migrations) === 0) {
            $this->note('<info>Nothing to rollback.</info>');
            return [];
        }

        foreach ($migrations as $migration) {
            $this->runDown($migration);
        }

        return $migrations;
    }

    /**
     * Revertir todas las migraciones
     *
     * @return array
     */
    public function reset()
    {
        $this->notes = [];

        if (!$this->repository->repositoryExists()) {
            $this->note('<error>Migration table not found.</error>');
            return [];
        }

        $migrations = array_reverse($this->repository->getRan());

        if (count($migrations) === 0) {
            $this->note('<info>Nothing to reset.</info>');
            return [];
        }

        foreach ($migrations as $migration) {
            $this->runDown($migration);
        }

        return $migrations;
    }

    /**
     * Revertir todas las migraciones y volver a ejecutarlas
     *
     * @return array
     */
    public function refresh()
    {
        $this->notes = [];

        $this->reset();
        $resetNotes = $this->notes;

        $result = $this->run();
        $this->notes = array_merge($resetNotes, $this->notes);

        return $result;
    }

    /**
     * Ejecutar una migración "down"
     *
     * @param string $file
     * @return void
     */
    protected function runDown($file)
    {
        $this->note("<comment>Rolling back:</comment> {$file}");

        $migration = $this->resolve($file);

        $pdo = MigrationDatabase::getConnection($this->connection);

        try {
            if ($migration->withinTransaction()) {
                $pdo->beginTransaction();
            }

            $migration->down();

            if ($migration->withinTransaction()) {
                $pdo->commit();
            }

            // Eliminar del registro
            $this->repository->delete($file);

            $this->note("<info>Rolled back:</info> {$file}");
        } catch (Exception $e) {
            if ($migration->withinTransaction() && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->note("<error>Rollback failed:</error> {$file}");
            throw $e;
        }
    }

    /**
     * Obtener estado de las migraciones
     *
     * @return array
     */
    public function status()
    {
        $this->notes = [];

        if (!$this->repository->repositoryExists()) {
            $this->note('<error>Migration table not found. Run migrations first.</error>');
            return [];
        }

        $ran = $this->repository->getMigrations();
        $files = $this->getMigrationFiles();

        $status = [];

        foreach ($files as $file) {
            $migrationRan = array_filter($ran, function ($m) use ($file) {
                return $m['migration'] === $file;
            });

            if (count($migrationRan) > 0) {
                $m = array_values($migrationRan)[0];
                $status[] = [
                    'migration' => $file,
                    'batch' => $m['batch'],
                    'ran' => true,
                ];
            } else {
                $status[] = [
                    'migration' => $file,
                    'batch' => null,
                    'ran' => false,
                ];
            }
        }

        return $status;
    }

    /**
     * Obtener archivos de migración
     *
     * @return array
     */
    public function getMigrationFiles()
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = scandir($this->path);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Resolver una migración desde un archivo
     *
     * @param string $file
     * @return Migration
     */
    protected function resolve($file)
    {
        $class = $this->getMigrationClassName($file);

        require_once $this->path . $file;

        return new $class();
    }

    /**
     * Obtener el nombre de la clase de migración
     *
     * @param string $file
     * @return string
     */
    protected function getMigrationClassName($file)
    {
        // Extraer el nombre de la migración del archivo
        // Formato: 2024_01_01_120000_create_users_table.php
        preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)\.php$/', $file, $matches);

        if (isset($matches[1])) {
            // Convertir snake_case a PascalCase
            $name = str_replace('_', ' ', $matches[1]);
            $name = ucwords($name);
            return str_replace(' ', '', $name);
        }

        throw new RuntimeException("Invalid migration file name: {$file}");
    }

    /**
     * Establecer el directorio de migraciones
     *
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    /**
     * Agregar una nota
     *
     * @param string $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Obtener las notas de ejecución
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Instalar la tabla de migraciones
     *
     * @return void
     */
    public function install()
    {
        $this->repository->createRepository();
        $this->note('<info>Migration table created successfully.</info>');
    }
}
