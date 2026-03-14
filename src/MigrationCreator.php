<?php

namespace JorgeMdDev\KumbiaMigrations;

class MigrationCreator
{
    protected $path;

    public function __construct($path = null)
    {
        $this->path = $path ?? APP_PATH . 'migrations/';
    }

    public function create($name, $table = null, $create = false)
    {
        $this->ensureMigrationDirectoryExists();

        $path    = $this->path . $this->getFileName($name);
        $content = $this->populateStub($this->getStub($table, $create), $name, $table);

        file_put_contents($path, $content);

        return $path;
    }

    protected function getStub($table, $create)
    {
        if ($table === null) {
            return $this->getBlankStub();
        }

        return $create ? $this->getCreateStub() : $this->getUpdateStub();
    }

    protected function populateStub($stub, $name, $table)
    {
        $stub = str_replace('{{class}}', $this->getClassName($name), $stub);

        if ($table !== null) {
            $stub = str_replace('{{table}}', $table, $stub);
        }

        return $stub;
    }

    protected function getClassName($name)
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }

    protected function getFileName($name)
    {
        return date('Y_m_d_His') . "_{$name}.php";
    }

    protected function ensureMigrationDirectoryExists()
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    protected function getBlankStub()
    {
        return <<<'STUB'
<?php

class {{class}} extends \JorgeMdDev\KumbiaMigrations\Migration
{
    public function up()
    {
        //
    }

    public function down()
    {
        //
    }
}
STUB;
    }

    protected function getCreateStub()
    {
        return <<<'STUB'
<?php

use JorgeMdDev\KumbiaMigrations\Migration;
use JorgeMdDev\KumbiaMigrations\Schema;
use JorgeMdDev\KumbiaMigrations\Blueprint;

class {{class}} extends Migration
{
    public function up()
    {
        Schema::create('{{table}}', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{{table}}');
    }
}
STUB;
    }

    protected function getUpdateStub()
    {
        return <<<'STUB'
<?php

use JorgeMdDev\KumbiaMigrations\Migration;
use JorgeMdDev\KumbiaMigrations\Schema;
use JorgeMdDev\KumbiaMigrations\Blueprint;

class {{class}} extends Migration
{
    public function up()
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }

    public function down()
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }
}
STUB;
    }

    public function setPath($path)
    {
        $this->path = rtrim($path, '/') . '/';
    }
}
