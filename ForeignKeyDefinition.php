<?php
/**
 * KumbiaPHP Migration System
 *
 * Definición fluida de claves foráneas
 *
 * @category Kumbia
 * @package Migration
 */

class ForeignKeyDefinition
{
    /**
     * Comando asociado
     *
     * @var array
     */
    protected $command;

    /**
     * Constructor
     *
     * @param array $command
     */
    public function __construct(array &$command)
    {
        $this->command = &$command;
    }

    /**
     * Definir las columnas referenciadas
     *
     * @param string|array $columns
     * @return $this
     */
    public function references($columns)
    {
        $this->command['references'] = $columns;
        return $this;
    }

    /**
     * Definir la tabla referenciada
     *
     * @param string $table
     * @return $this
     */
    public function on($table)
    {
        $this->command['on'] = $table;
        return $this;
    }

    /**
     * Definir acción ON DELETE
     *
     * @param string $action
     * @return $this
     */
    public function onDelete($action)
    {
        $this->command['onDelete'] = $action;
        return $this;
    }

    /**
     * Definir acción ON UPDATE
     *
     * @param string $action
     * @return $this
     */
    public function onUpdate($action)
    {
        $this->command['onUpdate'] = $action;
        return $this;
    }

    /**
     * Definir ON DELETE CASCADE
     *
     * @return $this
     */
    public function cascadeOnDelete()
    {
        return $this->onDelete('cascade');
    }

    /**
     * Definir ON UPDATE CASCADE
     *
     * @return $this
     */
    public function cascadeOnUpdate()
    {
        return $this->onUpdate('cascade');
    }

    /**
     * Definir ON DELETE RESTRICT
     *
     * @return $this
     */
    public function restrictOnDelete()
    {
        return $this->onDelete('restrict');
    }

    /**
     * Definir ON UPDATE RESTRICT
     *
     * @return $this
     */
    public function restrictOnUpdate()
    {
        return $this->onUpdate('restrict');
    }

    /**
     * Definir ON DELETE SET NULL
     *
     * @return $this
     */
    public function nullOnDelete()
    {
        return $this->onDelete('set null');
    }
}
