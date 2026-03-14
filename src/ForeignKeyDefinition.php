<?php

namespace JorgeMdDev\KumbiaMigrations;

class ForeignKeyDefinition
{
    protected $command;

    public function __construct(array &$command)
    {
        $this->command = &$command;
    }

    public function references($columns)
    {
        $this->command['references'] = $columns;
        return $this;
    }

    public function on($table)
    {
        $this->command['on'] = $table;
        return $this;
    }

    public function onDelete($action)
    {
        $this->command['onDelete'] = $action;
        return $this;
    }

    public function onUpdate($action)
    {
        $this->command['onUpdate'] = $action;
        return $this;
    }

    public function cascadeOnDelete()
    {
        return $this->onDelete('cascade');
    }

    public function cascadeOnUpdate()
    {
        return $this->onUpdate('cascade');
    }

    public function restrictOnDelete()
    {
        return $this->onDelete('restrict');
    }

    public function restrictOnUpdate()
    {
        return $this->onUpdate('restrict');
    }

    public function nullOnDelete()
    {
        return $this->onDelete('set null');
    }
}
