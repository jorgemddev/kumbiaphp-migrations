<?php

namespace JorgeMdDev\KumbiaMigrations;

abstract class Migration
{
    abstract public function up();

    abstract public function down();

    protected function getConnection($connection = null)
    {
        return MigrationDatabase::getConnection($connection);
    }

    public function withinTransaction()
    {
        return true;
    }
}
