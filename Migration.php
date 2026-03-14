<?php
/**
 * KumbiaPHP Migration System
 *
 * Clase base para migraciones de base de datos
 * Inspirado en Laravel Migrations con adaptaciones para KumbiaPHP
 *
 * @category Kumbia
 * @package Migration
 */

abstract class Migration
{
    /**
     * Ejecutar las migraciones
     *
     * @return void
     */
    abstract public function up();

    /**
     * Revertir las migraciones
     *
     * @return void
     */
    abstract public function down();

    /**
     * Obtener la conexión de base de datos
     *
     * @param string|null $connection Nombre de la conexión
     * @return \PDO
     */
    protected function getConnection($connection = null)
    {
        return MigrationDatabase::getConnection($connection);
    }

    /**
     * Indica si esta migración debe ejecutarse dentro de una transacción
     *
     * @return bool
     */
    public function withinTransaction()
    {
        return true;
    }
}
