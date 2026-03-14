# Sistema de Migraciones KumbiaPHP

Sistema profesional de migraciones de base de datos para KumbiaPHP, inspirado en Laravel Migrations con todas sus características principales.

## Características

- ✅ Migraciones versionadas con tracking completo
- ✅ Schema Builder con sintaxis fluida (Blueprint)
- ✅ Soporte para MySQL, PostgreSQL y SQLite
- ✅ Comandos CLI completos (migrate, rollback, refresh, reset, status)
- ✅ Generador de migraciones
- ✅ Sistema de Seeders para poblar datos
- ✅ Transacciones automáticas
- ✅ Claves foráneas con acciones ON DELETE/UPDATE
- ✅ Índices, claves únicas y primarias
- ✅ Compatible con todos los entornos (local, VPS, hosting compartido, Caprover)

## Instalación

El sistema ya está instalado en tu proyecto. Solo necesitas crear la tabla de migraciones:

```bash
php app/bin/migrate --install
```

## Uso Básico

### Crear una Nueva Migración

```bash
# Migración en blanco
php app/bin/migrate make:migration create_users_table

# Migración para crear tabla
php app/bin/migrate make:migration create_posts_table --create=posts

# Migración para modificar tabla existente
php app/bin/migrate make:migration add_email_to_users --table=users
```

### Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones pendientes
php app/bin/migrate

# Ver estado de las migraciones
php app/bin/migrate --status

# Revertir la última migración
php app/bin/migrate --rollback

# Revertir todas las migraciones
php app/bin/migrate --reset

# Revertir y re-ejecutar todas las migraciones
php app/bin/migrate --refresh
```

## Ejemplos de Migraciones

### Crear una Tabla

```php
<?php

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

### Modificar una Tabla

```php
<?php

class AddPhoneToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->index('phone');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
}
```

### Tabla con Claves Foráneas

```php
<?php

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            // Clave foránea
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Índices
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
```

### Tabla con ENUM y JSON

```php
<?php

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'inactive', 'pending']);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
```

## Tipos de Columnas Disponibles

### Numéricos
- `increments('id')` - Auto-incremento INT UNSIGNED
- `bigIncrements('id')` - Auto-incremento BIGINT UNSIGNED
- `integer('votes')` - INT
- `bigInteger('amount')` - BIGINT
- `tinyInteger('flag')` - TINYINT
- `smallInteger('count')` - SMALLINT
- `mediumInteger('count')` - MEDIUMINT
- `unsignedInteger('votes')` - INT UNSIGNED
- `unsignedBigInteger('user_id')` - BIGINT UNSIGNED
- `decimal('amount', 8, 2)` - DECIMAL con precisión
- `float('amount', 8, 2)` - FLOAT
- `double('amount')` - DOUBLE

### Cadenas
- `string('name', 100)` - VARCHAR
- `char('code', 10)` - CHAR
- `text('description')` - TEXT
- `mediumText('content')` - MEDIUMTEXT
- `longText('content')` - LONGTEXT

### Fechas y Tiempo
- `date('birth_date')` - DATE
- `dateTime('created_at')` - DATETIME
- `time('alarm')` - TIME
- `timestamp('added_on')` - TIMESTAMP
- `timestamps()` - Crea created_at y updated_at
- `softDeletes()` - Añade deleted_at para soft deletes

### Otros
- `boolean('is_active')` - BOOLEAN
- `json('options')` - JSON
- `jsonb('data')` - JSONB (PostgreSQL)
- `enum('status', ['pending', 'active'])` - ENUM
- `uuid('id')` - UUID
- `ipAddress('ip')` - IP Address
- `macAddress('mac')` - MAC Address
- `binary('data')` - BLOB

## Modificadores de Columnas

```php
$table->string('email')->nullable();
$table->integer('votes')->default(0);
$table->string('name')->unique();
$table->integer('user_id')->unsigned();
$table->string('description')->comment('User description');
$table->timestamp('created_at')->useCurrent();
$table->string('phone')->after('email');
$table->string('name')->first();
```

## Índices y Claves

```php
// Clave primaria
$table->primary('id');
$table->primary(['id', 'parent_id']);

// Índice único
$table->unique('email');
$table->unique(['email', 'deleted_at']);

// Índice normal
$table->index('user_id');
$table->index(['user_id', 'created_at'], 'user_created_index');

// Clave foránea
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade');

// Eliminar claves e índices
$table->dropPrimary('users_id_primary');
$table->dropUnique('users_email_unique');
$table->dropIndex('users_user_id_index');
$table->dropForeign('posts_user_id_foreign');
```

## Modificar Columnas

```php
// Renombrar columna
Schema::table('users', function (Blueprint $table) {
    $table->renameColumn('name', 'full_name');
});

// Eliminar columnas
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('votes');
    $table->dropColumn(['votes', 'avatar']);
});
```

## Operaciones con Tablas

```php
// Verificar si existe una tabla
if (Schema::hasTable('users')) {
    // ...
}

// Verificar si existe una columna
if (Schema::hasColumn('users', 'email')) {
    // ...
}

// Renombrar tabla
Schema::rename('old_table', 'new_table');

// Eliminar tabla
Schema::drop('users');
Schema::dropIfExists('users');
```

## Sistema de Seeders

### Crear un Seeder

Crea un archivo en `app/database/seeds/`:

```php
<?php
// app/database/seeds/UsersTableSeeder.php

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Limpiar tabla
        $this->truncate('users');

        // Insertar datos
        $this->insert('users', [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

### Registrar Seeders

Edita `app/database/seeds/DatabaseSeeder.php`:

```php
<?php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            PostsTableSeeder::class,
        ]);
    }
}
```

### Ejecutar Seeders

```bash
# Ejecutar DatabaseSeeder
php app/bin/seed

# Ejecutar un seeder específico
php app/bin/seed --class=UsersTableSeeder
```

## Desactivar Foreign Keys

Para operaciones que requieren deshabilitar las foreign keys:

```php
Schema::withoutForeignKeyConstraints(function () {
    // Tus operaciones aquí
    Schema::drop('users');
});

// O manualmente
Schema::disableForeignKeyConstraints();
// ... operaciones ...
Schema::enableForeignKeyConstraints();
```

## Configuración Multi-Entorno

El sistema se adapta automáticamente al entorno (development/production) según la configuración de KumbiaPHP y usa la conexión definida en `app/config/databases.php`.

### Ejemplo de Configuración

```php
// app/config/databases.php
return [
    'development' => [
        'host'     => getenv('BD_HOST'),
        'username' => getenv('BD_USERNAME'),
        'password' => getenv('BD_PASSWORD'),
        'name'     => getenv('BD_NAME'),
        'type'     => 'mysql',
        'charset'  => 'utf8mb4',
    ],

    'production' => [
        'host'     => getenv('BD_HOST'),
        'username' => getenv('BD_USERNAME'),
        'password' => getenv('BD_PASSWORD'),
        'name'     => getenv('BD_NAME'),
        'type'     => 'mysql',
        'charset'  => 'utf8mb4',
    ],
];
```

## Despliegue en Diferentes Entornos

### Hosting Compartido
```bash
# SSH al servidor
cd public_html/mi-proyecto
php app/bin/migrate
php app/bin/seed
```

### VPS / Servidor Dedicado
```bash
cd /var/www/mi-proyecto
php app/bin/migrate --refresh
php app/bin/seed
```

### Caprover
Agrega a tu `captain-definition`:
```json
{
  "schemaVersion": 2,
  "dockerfileLines": [
    "...",
    "RUN php app/bin/migrate"
  ]
}
```

### Docker
Agrega al Dockerfile:
```dockerfile
RUN php app/bin/migrate
```

## Workflow Recomendado

1. **Desarrollo Local**
```bash
# Crear migración
php app/bin/migrate make:migration create_feature_table --create=features

# Editar migración en app/migrations/

# Ejecutar migración
php app/bin/migrate

# Si hay errores, revertir
php app/bin/migrate --rollback

# Crear seeders si es necesario
php app/bin/seed
```

2. **Control de Versiones**
```bash
git add app/migrations/
git commit -m "Add features table migration"
git push
```

3. **Producción**
```bash
git pull
php app/bin/migrate
```

## Buenas Prácticas

1. **Nunca modifiques migraciones ya ejecutadas en producción**
2. **Usa soft deletes para datos críticos**
3. **Siempre define el método `down()` para poder revertir**
4. **Usa transacciones para operaciones complejas**
5. **Mantén las migraciones pequeñas y enfocadas**
6. **Documenta cambios complejos con comentarios**
7. **Testea las migraciones localmente antes de producción**

## Troubleshooting

### Error: "Migration table not found"
```bash
php app/bin/migrate --install
```

### Error: "Could not connect to database"
Verifica tu archivo `.env` o configuración en `app/config/databases.php`

### Error: Foreign key constraint fails
```bash
# Usar sin foreign keys
Schema::withoutForeignKeyConstraints(function () {
    Schema::dropIfExists('tabla');
});
```

### Resetear todo
```bash
php app/bin/migrate --reset
php app/bin/migrate
php app/bin/seed
```

## Integración con KumbiaPHP

El sistema está completamente integrado con KumbiaPHP y respeta:
- Configuración de bases de datos de KumbiaPHP
- Estructura de directorios de KumbiaPHP
- Convenciones de nomenclatura
- Sistema de autoload

## Soporte y Contribuciones

Este sistema es mantenido como parte del proyecto KumbiaPHP y sigue la filosofía del framework: simple, profesional y sin dependencias externas.

## Licencia

Mismo que KumbiaPHP - BSD License
