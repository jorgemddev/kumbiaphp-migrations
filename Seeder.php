<?php
/**
 * KumbiaPHP Migration System
 *
 * Clase base para seeders de base de datos
 *
 * @category Kumbia
 * @package Migration
 */

abstract class Seeder
{
    /**
     * Ejecutar el seeder
     *
     * @return void
     */
    abstract public function run();

    /**
     * Llamar a otro seeder
     *
     * @param string|array $class
     * @return void
     */
    public function call($class)
    {
        $classes = is_array($class) ? $class : [$class];

        foreach ($classes as $seederClass) {
            $seeder = new $seederClass();

            if (!($seeder instanceof Seeder)) {
                throw new RuntimeException("Class {$seederClass} is not a valid Seeder");
            }

            $this->output("Seeding: {$seederClass}");
            $seeder->run();
        }
    }

    /**
     * Obtener conexión PDO
     *
     * @param string|null $connection
     * @return \PDO
     */
    protected function getConnection($connection = null)
    {
        return MigrationDatabase::getConnection($connection);
    }

    /**
     * Insertar datos en una tabla
     *
     * @param string $table
     * @param array $data
     * @param string|null $connection
     * @return void
     */
    protected function insert($table, array $data, $connection = null)
    {
        $pdo = $this->getConnection($connection);

        // Si es un array asociativo simple, convertir a array de arrays
        if (!isset($data[0])) {
            $data = [$data];
        }

        foreach ($data as $row) {
            $columns = array_keys($row);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->quoteIdentifier($table),
                implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
                implode(', ', $placeholders)
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($row));
        }
    }

    /**
     * Truncar una tabla
     *
     * @param string $table
     * @param string|null $connection
     * @return void
     */
    protected function truncate($table, $connection = null)
    {
        $pdo = $this->getConnection($connection);
        $driver = MigrationDatabase::getDriverName($connection);

        if ($driver === 'sqlite') {
            $pdo->exec("DELETE FROM {$this->quoteIdentifier($table)}");
        } else {
            $pdo->exec("TRUNCATE TABLE {$this->quoteIdentifier($table)}");
        }
    }

    /**
     * Ejecutar SQL raw
     *
     * @param string $sql
     * @param array $bindings
     * @param string|null $connection
     * @return void
     */
    protected function query($sql, array $bindings = [], $connection = null)
    {
        $pdo = $this->getConnection($connection);

        if (empty($bindings)) {
            $pdo->exec($sql);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
        }
    }

    /**
     * Entrecomillar identificador según el driver
     *
     * @param string $identifier
     * @return string
     */
    protected function quoteIdentifier($identifier)
    {
        $pdo = $this->getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Imprimir mensaje
     *
     * @param string $message
     * @return void
     */
    protected function output($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    }
}
