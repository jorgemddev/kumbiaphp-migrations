# KumbiaPHP Migrations

## Propósito del Proyecto

**KumbiaPHP Migrations** nace de la necesidad de proporcionar un sistema robusto de versionado y gestión de cambios en esquemas de base de datos para [KumbiaPHP](https://www.kumbiaphp.com/), un framework que actualmente carece de esta funcionalidad.

El framework KumbiaPHP es poderoso pero no contaba con un sistema nativo de migraciones de base de datos. Este proyecto fue desarrollado como solución integral, **inspirado en Laravel Migrations** — uno de los sistemas más maduros y confiables de la comunidad PHP — adaptado específicamente para las características y estructura de KumbiaPHP.

> **Nota sobre el desarrollo:** Este proyecto fue desarrollado con la asistencia de IA, asegurando código de calidad, moderno y mantenible.

---

## ¿Qué es?

Un sistema de migraciones que permite:
- **Versionar** cambios en el esquema de tu base de datos
- **Reproducir** la estructura de la BD desde cero en cualquier ambiente
- **Revertir** cambios de forma segura
- **Colaborar** en equipo sin conflictos de esquema
- **Automatizar** despliegues y sincronización de BD

## Requisitos

- PHP 7.0 o superior
- Extensión PDO habilitada
- KumbiaPHP (cualquier versión con `app/config/databases.php`)
- MySQL/MariaDB, PostgreSQL o SQLite

## Instalación

### Vía Composer (recomendado)

```bash
composer require jorgemddev/kumbiaphp-migrations
```

El plugin copia automáticamente los binarios a `app/bin/` al instalar. Luego crea la tabla de control:

```bash
php app/bin/migrate --install
```

### Manual

Copia la carpeta `src/` dentro de `app/libs/` de tu proyecto KumbiaPHP:

```
app/
├── libs/
│   └── KumbiaMigrations/    ← aquí
├── migrations/              ← se crea automáticamente
├── database/
│   └── seeds/               ← seeders aquí
└── config/
    └── databases.php
```

Luego crea la tabla de control en tu base de datos:

```bash
php app/bin/migrate --install
```

## Configuración

El sistema lee la configuración desde `app/config/databases.php`. Debe retornar un array con las conexiones:

```php
<?php
return [
    'development' => [
        'type'     => 'mysql',       // mysql | pgsql | sqlite
        'host'     => 'localhost',
        'name'     => 'mi_base',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',     // opcional, por defecto utf8mb4
        'port'     => 3306,          // opcional
    ],
    'production' => [
        'type'     => 'mysql',
        'host'     => 'db.servidor.com',
        'name'     => 'mi_base_prod',
        'username' => 'usuario',
        'password' => 'secreto',
    ],
];
```

El entorno se detecta automáticamente: si la constante `PRODUCTION` está definida y es `true`, se usa la conexión `production`; de lo contrario, `development`.

## Comandos

Todos los comandos se ejecutan desde la raíz del proyecto:

```bash
# Ejecutar migraciones pendientes
php app/bin/migrate

# Ver estado de todas las migraciones
php app/bin/migrate --status

# Revertir el último lote de migraciones
php app/bin/migrate --rollback

# Revertir todas las migraciones
php app/bin/migrate --reset

# Revertir todo y volver a ejecutar
php app/bin/migrate --refresh

# Crear la tabla de control (solo la primera vez)
php app/bin/migrate --install

# Ayuda
php app/bin/migrate --help
```

### Crear migraciones

```bash
# Migración en blanco
php app/bin/migrate make:migration nombre_descriptivo

# Para crear una tabla nueva
php app/bin/migrate make:migration create_products_table --create=products

# Para modificar una tabla existente
php app/bin/migrate make:migration add_price_to_products --table=products
```

Esto genera un archivo en `app/migrations/` con el formato `YYYY_MM_DD_HHMMSS_nombre.php`.

## Escribir migraciones

Cada migración es una clase PHP con dos métodos: `up()` para aplicar el cambio y `down()` para revertirlo.

### Crear una tabla

```php
<?php

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('sku', 100)->unique();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
```

### Modificar una tabla existente

```php
<?php

class AddDescriptionToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('image')->nullable();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'image']);
        });
    }
}
```

### Modificar columna existente

```php
<?php

class FixScheduleExemptDefault extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('schedule_exempt')->default(false)->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('schedule_exempt')->change();
        });
    }
}
```

### Tabla con clave foránea

```php
<?php

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->decimal('total', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
```

## Tipos de columnas disponibles

| Método | Tipo SQL |
|--------|----------|
| `bigIncrements('id')` | BIGINT UNSIGNED AUTO_INCREMENT PK |
| `increments('id')` | INT UNSIGNED AUTO_INCREMENT PK |
| `string('col', 255)` | VARCHAR |
| `char('col', 10)` | CHAR |
| `text('col')` | TEXT |
| `mediumText('col')` | MEDIUMTEXT |
| `longText('col')` | LONGTEXT |
| `integer('col')` | INT |
| `tinyInteger('col')` | TINYINT |
| `smallInteger('col')` | SMALLINT |
| `bigInteger('col')` | BIGINT |
| `unsignedInteger('col')` | INT UNSIGNED |
| `unsignedBigInteger('col')` | BIGINT UNSIGNED |
| `float('col', 8, 2)` | FLOAT |
| `double('col')` | DOUBLE |
| `decimal('col', 10, 2)` | DECIMAL |
| `boolean('col')` | TINYINT(1) |
| `enum('col', ['a','b'])` | ENUM |
| `json('col')` | JSON |
| `date('col')` | DATE |
| `dateTime('col')` | DATETIME |
| `timestamp('col')` | TIMESTAMP |
| `timestamps()` | created_at + updated_at |
| `softDeletes()` | deleted_at nullable |
| `binary('col')` | BLOB |
| `uuid('col')` | CHAR(36) |
| `ipAddress('col')` | VARCHAR(45) |
| `macAddress('col')` | VARCHAR(17) |

## Modificadores de columna

Se encadenan sobre cualquier definición de columna:

```php
$table->string('email')->unique()->nullable(false);
$table->integer('views')->default(0)->unsigned();
$table->string('bio')->nullable()->after('email');
$table->timestamp('verified_at')->nullable()->useCurrent();
$table->string('slug')->index();
$table->boolean('active')->change();
```

| Modificador | Descripción |
|-------------|-------------|
| `->nullable()` | Permite NULL |
| `->default($valor)` | Valor por defecto |
| `->unsigned()` | Sin signo (MySQL) |
| `->unique()` | Índice único |
| `->index()` | Índice normal |
| `->after('columna')` | Posición (MySQL) |
| `->first()` | Primera posición (MySQL) |
| `->comment('texto')` | Comentario (MySQL) |
| `->useCurrent()` | DEFAULT CURRENT_TIMESTAMP |
| `->change()` | Modificar columna existente |

## Claves foráneas

```php
// Definición completa
$table->foreign('category_id')
      ->references('id')
      ->on('categories')
      ->onDelete('cascade')   // CASCADE | RESTRICT | SET NULL | NO ACTION
      ->onUpdate('restrict');

// Métodos abreviados
$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
$table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
$table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();

// Eliminar clave foránea
$table->dropForeign('user_id_foreign');
```

## Índices

```php
// En la definición de columna
$table->string('email')->unique();
$table->string('slug')->index();

// Como comando independiente
$table->unique(['email', 'tenant_id'], 'unique_email_per_tenant');
$table->index(['last_name', 'first_name']);
$table->primary(['id', 'type']);

// Eliminar índices
$table->dropUnique('unique_email_per_tenant');
$table->dropIndex('products_name_index');
$table->dropPrimary();
```

## Operaciones de esquema

```php
// Verificar existencia
if (!Schema::hasTable('products')) { ... }
if (!Schema::hasColumn('products', 'price')) { ... }

// Renombrar tabla
Schema::rename('old_name', 'new_name');

// Eliminar tabla
Schema::drop('products');
Schema::dropIfExists('products');

// Deshabilitar claves foráneas temporalmente
Schema::withoutForeignKeyConstraints(function () {
    Schema::drop('users');
});
```

## Introspección de base de datos

Permite inspeccionar la estructura actual de la base de datos. Compatible con MySQL, PostgreSQL y SQLite.

### Listar tablas

```php
$tables = Schema::getTables();
// ['categories', 'migrations', 'orders', 'products', 'users']
```

### Columnas de una tabla

```php
$columns = Schema::getColumns('products');
```

Cada elemento retorna:

| Campo | Descripción |
|-------|-------------|
| `name` | Nombre de la columna |
| `type` | Tipo de dato (`varchar(255)`, `int`, etc.) |
| `nullable` | `true` si acepta NULL |
| `default` | Valor por defecto o `null` |
| `key` | `PRI`, `UNI`, `MUL` (MySQL) / `PRIMARY` (otros) / `null` |
| `extra` | `auto_increment`, etc. (MySQL) |
| `comment` | Comentario de la columna (MySQL) |

```php
// Ejemplo de resultado
[
    ['name' => 'id',    'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'key' => 'PRI', 'extra' => 'auto_increment', 'comment' => null],
    ['name' => 'name',  'type' => 'varchar(255)',    'nullable' => false, 'default' => null, 'key' => null,  'extra' => null,             'comment' => null],
    ['name' => 'price', 'type' => 'decimal(10,2)',   'nullable' => false, 'default' => null, 'key' => null,  'extra' => null,             'comment' => null],
]
```

### Índices de una tabla

```php
$indexes = Schema::getIndexes('products');
```

Cada elemento retorna:

| Campo | Descripción |
|-------|-------------|
| `name` | Nombre del índice |
| `columns` | Array de columnas que lo componen |
| `unique` | `true` si es único |
| `primary` | `true` si es la clave primaria |

```php
// Ejemplo de resultado
[
    ['name' => 'PRIMARY',           'columns' => ['id'],  'unique' => true,  'primary' => true],
    ['name' => 'products_sku_unique', 'columns' => ['sku'], 'unique' => true,  'primary' => false],
    ['name' => 'products_name_index', 'columns' => ['name'],'unique' => false, 'primary' => false],
]
```

### Claves foráneas de una tabla

```php
$fks = Schema::getForeignKeys('orders');
```

Cada elemento retorna:

| Campo | Descripción |
|-------|-------------|
| `name` | Nombre de la constraint |
| `columns` | Columnas locales |
| `on_table` | Tabla referenciada |
| `references` | Columnas referenciadas |
| `on_delete` | Acción ON DELETE (`CASCADE`, `RESTRICT`, `SET NULL`, `NO ACTION`) |
| `on_update` | Acción ON UPDATE |

```php
// Ejemplo de resultado
[
    [
        'name'       => 'orders_user_id_foreign',
        'columns'    => ['user_id'],
        'on_table'   => 'users',
        'references' => ['id'],
        'on_delete'  => 'CASCADE',
        'on_update'  => 'RESTRICT',
    ],
]
```

### Uso con conexión específica

Todos los métodos de introspección respetan la conexión activa:

```php
Schema::connection('production');

$tables = Schema::getTables();
$cols   = Schema::getColumns('users');
$fks    = Schema::getForeignKeys('orders');
```

## Seeders

Los seeders poblan la base de datos con datos iniciales o de prueba. Se ubican en `app/database/seeds/`.

### Crear un seeder

```php
<?php
// app/database/seeds/UsersSeeder.php

class UsersSeeder extends Seeder
{
    public function run()
    {
        $this->insert('users', [
            [
                'name'       => 'Administrador',
                'email'      => 'admin@example.com',
                'password'   => password_hash('secret', PASSWORD_DEFAULT),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

### DatabaseSeeder principal

```php
<?php
// app/database/seeds/DatabaseSeeder.php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            CategoriesSeeder::class,
            ProductsSeeder::class,
        ]);
    }
}
```

### Ejecutar seeders

```bash
# Ejecutar DatabaseSeeder
php app/bin/seed

# Ejecutar un seeder específico
php app/bin/seed --class=UsersSeeder
```

### Métodos disponibles en Seeder

```php
// Insertar filas
$this->insert('tabla', $filas);

// Vaciar tabla
$this->truncate('tabla');

// SQL personalizado
$this->query('UPDATE config SET value = ? WHERE key = ?', ['activo', 'status']);

// Llamar a otro seeder
$this->call(OtroSeeder::class);
$this->call([SeederA::class, SeederB::class]);
```

## Flujo de trabajo recomendado

```bash
# 1. Crear la migración
php app/bin/migrate make:migration create_orders_table --create=orders

# 2. Editar app/migrations/YYYY_MM_DD_HHMMSS_create_orders_table.php

# 3. Aplicar
php app/bin/migrate

# 4. Si hay un error, revertir, corregir y volver a aplicar
php app/bin/migrate --rollback
php app/bin/migrate

# 5. Versionar
git add app/migrations/
git commit -m "feat: add orders table"
```

## Despliegue en producción

```bash
git pull origin main
php app/bin/migrate
```

> **Advertencia:** No ejecutes `--reset` ni `--refresh` en producción. Solo `migrate` para aplicar los cambios pendientes.

## Estructura de archivos

```
src/
├── Grammar/
│   ├── Grammar.php
│   ├── MySqlGrammar.php
│   ├── PostgresGrammar.php
│   └── SQLiteGrammar.php
├── Migration.php
├── MigrationCreator.php
├── MigrationDatabase.php
├── MigrationRepository.php
├── Migrator.php
├── Blueprint.php
├── ColumnDefinition.php
├── ForeignKeyDefinition.php
├── Schema.php
└── Seeder.php
```

## Licencia

Este proyecto está bajo licencia MIT. Siéntete libre de usarlo, modificarlo y distribuirlo.

---

**Desarrollado con IA para la comunidad de KumbiaPHP** 🚀
