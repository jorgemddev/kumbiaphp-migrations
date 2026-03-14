# Sistema de Migraciones para KumbiaPHP

Sistema profesional de migraciones de base de datos para KumbiaPHP, inspirado en Laravel Migrations.

## 📋 ¿Qué es este sistema?

Este es un sistema completo de migraciones de base de datos que permite:

- **Versionar cambios en la base de datos** como si fueran código
- **Crear y modificar tablas** con una sintaxis elegante y fluida
- **Revertir cambios** si algo sale mal
- **Trabajar en equipo** sin conflictos en la estructura de la BD
- **Desplegar en producción** de forma segura y controlada

## 🎯 ¿Para qué sirve?

### Problemas que resuelve:

1. **Sin migraciones:**
   - "¿Qué cambios hice en la BD?"
   - "¿Cómo sincronizo la BD entre desarrollo y producción?"
   - "¿Qué columnas agregué la semana pasada?"
   - Ejecutar scripts SQL manualmente en cada servidor

2. **Con migraciones:**
   - Todos los cambios están versionados en archivos PHP
   - Un solo comando sincroniza cualquier base de datos
   - Historial completo de cambios
   - Rollback automático si algo falla

## 📦 Estructura del Sistema

```
kumbiaphp-migrations/
├── bin/
│   ├── migrate              # Comando para ejecutar migraciones
│   └── seed                 # Comando para poblar datos
├── docs/                    # Documentación completa
│   ├── MIGRATIONS_QUICKSTART.md
│   ├── MIGRATIONS.md
│   ├── MIGRATE_FROM_EXISTING_DB.md
│   ├── DEPLOYMENT_GUIDE.md
│   └── SAFETY_GUIDE.md
├── grammar/                 # Adaptadores para diferentes BD
│   ├── Grammar.php
│   ├── MySqlGrammar.php
│   ├── PostgresGrammar.php
│   └── SQLiteGrammar.php
├── Blueprint.php            # Constructor de esquemas
├── ColumnDefinition.php     # Definición de columnas
├── ForeignKeyDefinition.php # Claves foráneas
├── Migration.php            # Clase base para migraciones
├── MigrationCreator.php     # Generador de archivos
├── MigrationDatabase.php    # Gestor de conexiones
├── MigrationRepository.php  # Tracking de migraciones
├── Migrator.php             # Motor de ejecución
├── Schema.php               # Fachada principal
├── Seeder.php               # Base para seeders
├── README.md                # Documentación principal
└── GUIA_USO.md             # Este archivo
```

## 🚀 Instalación en tu Proyecto KumbiaPHP

### 1. Copiar archivos al proyecto

```bash
# Copiar el sistema de migraciones
cp -r kumbiaphp-migrations/* tu-proyecto/app/libs/migration/

# Copiar comandos CLI
cp kumbiaphp-migrations/bin/migrate tu-proyecto/app/bin/
cp kumbiaphp-migrations/bin/seed tu-proyecto/app/bin/

# Dar permisos de ejecución
chmod +x tu-proyecto/app/bin/migrate
chmod +x tu-proyecto/app/bin/seed
```

### 2. Crear directorios necesarios

```bash
mkdir -p tu-proyecto/app/migrations
mkdir -p tu-proyecto/app/database/seeds
```

### 3. Configurar base de datos

Asegúrate de que `app/config/databases.php` esté configurado:

```php
<?php
return [
    'development' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'name' => 'mi_base_datos',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'port' => 3306,
    ],
    'production' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'name' => 'mi_base_datos_prod',
        'username' => 'usuario_prod',
        'password' => 'password_seguro',
        'charset' => 'utf8mb4',
        'port' => 3306,
    ],
];
```

### 4. Instalar tabla de migraciones

```bash
cd tu-proyecto
php app/bin/migrate --install
```

## 📖 Uso Básico

### Crear una migración

```bash
# Migración en blanco
php app/bin/migrate make:migration nombre_descriptivo

# Para crear una tabla
php app/bin/migrate make:migration create_users_table --create=users

# Para modificar una tabla
php app/bin/migrate make:migration add_email_to_users --table=users
```

### Ejemplo: Crear tabla de usuarios

```bash
php app/bin/migrate make:migration create_users_table --create=users
```

Esto crea: `app/migrations/2025_01_20_120000_create_users_table.php`

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
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

### Ejecutar migraciones

```bash
# Ejecutar todas las migraciones pendientes
php app/bin/migrate

# Ver estado de migraciones
php app/bin/migrate --status

# Revertir última migración
php app/bin/migrate --rollback

# Revertir todas las migraciones
php app/bin/migrate --reset

# Reset + ejecutar todo de nuevo
php app/bin/migrate --refresh
```

## 💡 Ejemplos Prácticos

### 1. Tabla con relaciones

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
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            // Clave foránea
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
```

### 2. Agregar columnas a tabla existente

```php
<?php

class AddPhoneToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('address')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address']);
        });
    }
}
```

### 3. Modificar columnas

```php
<?php

class ModifyUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar índice
            $table->index('email');
            
            // Agregar columna
            $table->timestamp('last_login')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('last_login');
        });
    }
}
```

## 🌱 Seeders (Datos de Prueba)

### Crear un seeder

Crear archivo: `app/database/seeds/UsersSeeder.php`

```php
<?php

class UsersSeeder extends Seeder
{
    public function run()
    {
        $this->insert('users', [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Usuario Test',
                'email' => 'test@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

### DatabaseSeeder principal

Crear archivo: `app/database/seeds/DatabaseSeeder.php`

```php
<?php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            PostsSeeder::class,
            // Agregar más seeders aquí
        ]);
    }
}
```

### Ejecutar seeders

```bash
# Ejecutar DatabaseSeeder
php app/bin/seed

# Ejecutar seeder específico
php app/bin/seed --class=UsersSeeder
```

## 🔧 Tipos de Columnas Disponibles

```php
// Auto-incremento
$table->increments('id');
$table->bigIncrements('id');

// Strings
$table->string('name');
$table->string('email', 100);
$table->char('code', 10);
$table->text('description');
$table->mediumText('content');
$table->longText('data');

// Números
$table->integer('quantity');
$table->bigInteger('big_number');
$table->tinyInteger('small_number');
$table->smallInteger('medium_number');
$table->unsignedInteger('positive_number');
$table->unsignedBigInteger('big_positive');
$table->decimal('price', 10, 2);
$table->float('rating', 8, 2);
$table->double('precise_value');

// Booleanos
$table->boolean('is_active');

// Fechas
$table->date('birth_date');
$table->dateTime('published_at');
$table->time('start_time');
$table->timestamp('created_at');
$table->timestamps(); // created_at + updated_at
$table->softDeletes(); // deleted_at

// Especiales
$table->enum('status', ['active', 'inactive']);
$table->json('metadata');
$table->uuid('identifier');
$table->ipAddress('ip');
$table->macAddress('mac');
$table->binary('data');
```

## 🎨 Modificadores de Columnas

```php
// Nullable
$table->string('email')->nullable();

// Valor por defecto
$table->boolean('is_active')->default(true);
$table->integer('views')->default(0);

// Único
$table->string('email')->unique();

// Índice
$table->string('slug')->index();

// Unsigned
$table->integer('quantity')->unsigned();

// Comentario
$table->string('name')->comment('Nombre del usuario');

// Posición
$table->string('phone')->after('email');
$table->string('code')->first();

// Timestamp actual
$table->timestamp('created_at')->useCurrent();
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
```

## 🔗 Claves Foráneas

```php
// Básica
$table->foreign('user_id')
      ->references('id')
      ->on('users');

// Con CASCADE
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade')
      ->onUpdate('cascade');

// Con RESTRICT
$table->foreign('category_id')
      ->references('id')
      ->on('categories')
      ->onDelete('restrict');

// Con SET NULL
$table->foreign('author_id')
      ->references('id')
      ->on('users')
      ->onDelete('set null');

// Eliminar clave foránea
$table->dropForeign(['user_id']);
// o
$table->dropForeign('posts_user_id_foreign');
```

## 📊 Índices

```php
// Índice simple
$table->index('email');

// Índice compuesto
$table->index(['user_id', 'post_id']);

// Índice único
$table->unique('email');

// Clave primaria
$table->primary('id');
// o compuesta
$table->primary(['user_id', 'role_id']);

// Eliminar índices
$table->dropIndex(['email']);
$table->dropUnique(['email']);
$table->dropPrimary();
```

## 🔄 Workflow Recomendado

### Desarrollo Local

```bash
# 1. Crear migración
php app/bin/migrate make:migration create_products_table --create=products

# 2. Editar el archivo generado en app/migrations/

# 3. Ejecutar migración
php app/bin/migrate

# 4. Si hay errores, revertir y corregir
php app/bin/migrate --rollback
# Editar migración
php app/bin/migrate

# 5. Crear seeder si es necesario
# Editar app/database/seeds/ProductsSeeder.php
php app/bin/seed

# 6. Commit a Git
git add app/migrations/
git commit -m "Add products table migration"
```

### Despliegue en Producción

```bash
# En el servidor
git pull origin main

# Ejecutar migraciones
php app/bin/migrate

# Opcional: ejecutar seeders (solo si es necesario)
php app/bin/seed
```

## 🛡️ Buenas Prácticas

### ✅ Hacer

1. **Siempre define el método down()** para poder revertir
2. **Usa nombres descriptivos** para las migraciones
3. **Una migración = un cambio lógico** (no mezclar muchas cosas)
4. **Testea localmente** antes de producción
5. **Usa soft deletes** para datos importantes
6. **Versiona las migraciones** en Git
7. **Documenta cambios complejos** con comentarios

### ❌ Evitar

1. **NO modifiques migraciones ya ejecutadas en producción**
2. **NO elimines migraciones del historial**
3. **NO uses SQL directo** (usa el Schema Builder)
4. **NO olvides el método down()**
5. **NO ejecutes rollback en producción** sin backup

## 🚨 Solución de Problemas

### Error: "Migration table not found"

```bash
php app/bin/migrate --install
```

### Error: "Could not connect to database"

Verifica `app/config/databases.php` y las credenciales.

### Resetear todo (CUIDADO: borra datos)

```bash
php app/bin/migrate --reset
php app/bin/migrate
php app/bin/seed
```

### Ver qué migraciones están pendientes

```bash
php app/bin/migrate --status
```

## 🌍 Compatibilidad

- **PHP:** 7.0 o superior
- **Bases de datos:** MySQL, MariaDB, PostgreSQL, SQLite
- **Entornos:** XAMPP, MAMP, WAMP, VPS, Docker, Caprover, Hosting compartido

## 📚 Documentación Adicional

- **README.md** - Documentación principal del sistema
- **docs/MIGRATIONS_QUICKSTART.md** - Guía rápida de inicio
- **docs/MIGRATIONS.md** - Referencia completa de todos los métodos
- **docs/MIGRATE_FROM_EXISTING_DB.md** - Migrar desde BD existente
- **docs/DEPLOYMENT_GUIDE.md** - Guía de despliegue
- **docs/SAFETY_GUIDE.md** - Guía de seguridad

## 💼 Casos de Uso Reales

### Caso 1: Agregar campo a tabla existente

```bash
php app/bin/migrate make:migration add_status_to_orders --table=orders
```

```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->enum('status', ['pending', 'paid', 'shipped', 'delivered'])
              ->default('pending')
              ->after('total');
    });
}

public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
```

### Caso 2: Crear tabla con relaciones múltiples

```bash
php app/bin/migrate make:migration create_order_items_table --create=order_items
```

```php
public function up()
{
    Schema::create('order_items', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('order_id');
        $table->unsignedBigInteger('product_id');
        $table->integer('quantity');
        $table->decimal('price', 10, 2);
        $table->decimal('subtotal', 10, 2);
        $table->timestamps();

        $table->foreign('order_id')
              ->references('id')
              ->on('orders')
              ->onDelete('cascade');

        $table->foreign('product_id')
              ->references('id')
              ->on('products')
              ->onDelete('restrict');
    });
}
```

### Caso 3: Renombrar tabla

```bash
php app/bin/migrate make:migration rename_old_users_to_customers
```

```php
public function up()
{
    Schema::rename('old_users', 'customers');
}

public function down()
{
    Schema::rename('customers', 'old_users');
}
```

## 🎓 Recursos de Aprendizaje

1. **Empieza aquí:** Lee `docs/MIGRATIONS_QUICKSTART.md`
2. **Referencia completa:** Consulta `docs/MIGRATIONS.md`
3. **Ejemplos reales:** Revisa las migraciones en `app/migrations/`
4. **Seeders:** Mira ejemplos en `app/database/seeds/`

## 🤝 Soporte

Este sistema está basado en Laravel Migrations y adaptado para KumbiaPHP. Es completamente independiente y no requiere dependencias externas.

---

**Sistema desarrollado siguiendo la filosofía KumbiaPHP: Simple, profesional y sin dependencias.**

¡Listo para usar! 🚀
