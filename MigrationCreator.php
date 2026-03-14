<?php
/**
 * KumbiaPHP Migration System
 *
 * Generador de archivos de migración
 *
 * @category Kumbia
 * @package Migration
 */

class MigrationCreator
{
    /**
     * Directorio de migraciones
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param string|null $path
     */
    public function __construct($path = null)
    {
        $this->path = $path ?? APP_PATH . 'migrations/';
    }

    /**
     * Crear un nuevo archivo de migración
     *
     * @param string $name
     * @param string|null $table
     * @param bool $create
     * @return string Ruta del archivo creado
     */
    public function create($name, $table = null, $create = false)
    {
        // Asegurar que el directorio existe
        $this->ensureMigrationDirectoryExists();

        // Generar nombre de archivo
        $fileName = $this->getFileName($name);
        $path = $this->path . $fileName;

        // Generar contenido
        $stub = $this->getStub($table, $create);
        $content = $this->populateStub($stub, $name, $table, $create);

        // Escribir archivo
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Obtener el stub apropiado
     *
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if ($table === null) {
            return $this->getBlankStub();
        }

        if ($create) {
            return $this->getCreateStub();
        }

        return $this->getUpdateStub();
    }

    /**
     * Poblar el stub con los datos
     *
     * @param string $stub
     * @param string $name
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    protected function populateStub($stub, $name, $table, $create)
    {
        $className = $this->getClassName($name);

        $stub = str_replace('{{class}}', $className, $stub);

        if ($table !== null) {
            $stub = str_replace('{{table}}', $table, $stub);
        }

        return $stub;
    }

    /**
     * Obtener el nombre de la clase de migración
     *
     * @param string $name
     * @return string
     */
    protected function getClassName($name)
    {
        // Convertir snake_case a PascalCase
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    /**
     * Obtener el nombre del archivo de migración
     *
     * @param string $name
     * @return string
     */
    protected function getFileName($name)
    {
        $timestamp = date('Y_m_d_His');
        return "{$timestamp}_{$name}.php";
    }

    /**
     * Asegurar que el directorio de migraciones existe
     *
     * @return void
     */
    protected function ensureMigrationDirectoryExists()
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Stub en blanco
     *
     * @return string
     */
    protected function getBlankStub()
    {
        return <<<'STUB'
<?php

/**
 * Migration: {{class}}
 */
class {{class}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        //
    }
}

STUB;
    }

    /**
     * Stub para crear tabla
     *
     * @return string
     */
    protected function getCreateStub()
    {
        return <<<'STUB'
<?php

/**
 * Migration: {{class}}
 */
class {{class}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('{{table}}', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('{{table}}');
    }
}

STUB;
    }

    /**
     * Stub para modificar tabla
     *
     * @return string
     */
    protected function getUpdateStub()
    {
        return <<<'STUB'
<?php

/**
 * Migration: {{class}}
 */
class {{class}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }
}

STUB;
    }

    /**
     * Establecer el path de migraciones
     *
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/') . '/';
    }
}
