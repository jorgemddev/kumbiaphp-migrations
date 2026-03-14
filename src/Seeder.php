<?php

namespace JorgeMdDev\KumbiaMigrations;

abstract class Seeder
{
    abstract public function run();

    public function call($class)
    {
        foreach ((array) $class as $seederClass) {
            $seeder = new $seederClass();

            if (!($seeder instanceof Seeder)) {
                throw new \RuntimeException("Class {$seederClass} is not a valid Seeder");
            }

            $this->output("Seeding: {$seederClass}");
            $seeder->run();
        }
    }

    protected function getConnection($connection = null)
    {
        return MigrationDatabase::getConnection($connection);
    }

    protected function insert($table, array $data, $connection = null)
    {
        $pdo = $this->getConnection($connection);

        if (!isset($data[0])) {
            $data = [$data];
        }

        foreach ($data as $row) {
            $columns      = array_keys($row);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->quoteIdentifier($table),
                implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
                implode(', ', $placeholders)
            );

            $pdo->prepare($sql)->execute(array_values($row));
        }
    }

    protected function truncate($table, $connection = null)
    {
        $pdo    = $this->getConnection($connection);
        $driver = MigrationDatabase::getDriverName($connection);

        $sql = $driver === 'sqlite'
            ? "DELETE FROM {$this->quoteIdentifier($table)}"
            : "TRUNCATE TABLE {$this->quoteIdentifier($table)}";

        $pdo->exec($sql);
    }

    protected function query($sql, array $bindings = [], $connection = null)
    {
        $pdo = $this->getConnection($connection);

        if (empty($bindings)) {
            $pdo->exec($sql);
        } else {
            $pdo->prepare($sql)->execute($bindings);
        }
    }

    protected function quoteIdentifier($identifier)
    {
        $driver = $this->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    protected function output($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    }
}
