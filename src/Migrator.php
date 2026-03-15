<?php

namespace JorgeMdDev\KumbiaMigrations;

class Migrator
{
    protected $repository;
    protected $path;
    protected $connection;
    protected $notes = [];

    public function __construct($connection = null)
    {
        $this->connection = $connection;
        $this->repository = new MigrationRepository($connection);
        $this->path       = APP_PATH . 'migrations/';
    }

    public function run()
    {
        $this->notes = [];

        if (!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
            $this->note('<info>Migration table created successfully.</info>');
        }

        $pending = array_diff($this->getMigrationFiles(), $this->repository->getRan());

        if (count($pending) === 0) {
            $this->note('<info>Nothing to migrate.</info>');
            return [];
        }

        $batch      = $this->repository->getNextBatchNumber();
        $migrations = [];

        foreach ($pending as $file) {
            $this->runUp($file, $batch);
            $migrations[] = $file;
        }

        return $migrations;
    }

    protected function runUp($file, $batch)
    {
        $this->note("<comment>Migrating:</comment> {$file}");

        $migration = $this->resolve($file);
        $pdo       = MigrationDatabase::getConnection($this->connection);

        try {
            if ($migration->withinTransaction()) {
                $pdo->beginTransaction();
            }

            $migration->up();

            if ($migration->withinTransaction()) {
                $pdo->commit();
            }

            $this->repository->log($file, $batch);
            $this->note("<info>Migrated:</info>  {$file}");
        } catch (\Exception $e) {
            if ($migration->withinTransaction() && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->note("<error>Migration failed:</error> {$file}");
            throw $e;
        }
    }

    public function rollback()
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

    public function refresh()
    {
        $this->notes = [];

        $this->reset();
        $resetNotes = $this->notes;

        $result      = $this->run();
        $this->notes = array_merge($resetNotes, $this->notes);

        return $result;
    }

    protected function runDown($file)
    {
        $this->note("<comment>Rolling back:</comment> {$file}");

        $migration = $this->resolve($file);
        $pdo       = MigrationDatabase::getConnection($this->connection);

        try {
            if ($migration->withinTransaction()) {
                $pdo->beginTransaction();
            }

            $migration->down();

            if ($migration->withinTransaction()) {
                $pdo->commit();
            }

            $this->repository->delete($file);
            $this->note("<info>Rolled back:</info> {$file}");
        } catch (\Exception $e) {
            if ($migration->withinTransaction() && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->note("<error>Rollback failed:</error> {$file}");
            throw $e;
        }
    }

    public function status()
    {
        $this->notes = [];

        if (!$this->repository->repositoryExists()) {
            $this->note('<error>Migration table not found. Run migrations first.</error>');
            return [];
        }

        $ran    = $this->repository->getMigrations();
        $files  = $this->getMigrationFiles();
        $status = [];

        foreach ($files as $file) {
            $migrationRan = array_filter($ran, function ($m) use ($file) {
                return $m['migration'] === $file;
            });

            if (count($migrationRan) > 0) {
                $m        = array_values($migrationRan)[0];
                $status[] = ['migration' => $file, 'batch' => $m['batch'], 'ran' => true];
            } else {
                $status[] = ['migration' => $file, 'batch' => null, 'ran' => false];
            }
        }

        return $status;
    }

    public function getMigrationFiles()
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $migrations = array_filter(scandir($this->path), function ($file) {
            return preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $file);
        });

        sort($migrations);
        return $migrations;
    }

    protected function resolve($file)
    {
        $class = $this->getMigrationClassName($file);

        // Alias para que las migraciones puedan usar `extends Migration`
        // sin necesidad de declarar el namespace o use statement
        if (!class_exists('Migration', false)) {
            class_alias(Migration::class, 'Migration');
        }
        if (!class_exists('Blueprint', false)) {
            class_alias(Blueprint::class, 'Blueprint');
        }
        if (!class_exists('Schema', false)) {
            class_alias(Schema::class, 'Schema');
        }

        require_once $this->path . $file;
        return new $class();
    }

    protected function getMigrationClassName($file)
    {
        preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)\.php$/', $file, $matches);

        if (isset($matches[1])) {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $matches[1])));
        }

        throw new \RuntimeException("Invalid migration file name: {$file}");
    }

    public function setPath($path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    public function install()
    {
        $this->repository->createRepository();
        $this->note('<info>Migration table created successfully.</info>');
    }

    protected function note($message)
    {
        $this->notes[] = $message;
    }

    public function getNotes()
    {
        return $this->notes;
    }
}
