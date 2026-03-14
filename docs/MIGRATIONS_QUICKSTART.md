# Quick Start - Sistema de Migraciones KumbiaPHP

## Instalación Rápida

```bash
# 1. Crear tabla de migraciones
php app/bin/migrate --install

# 2. Ver comandos disponibles
php app/bin/migrate --help
```

## Uso Básico en 5 Pasos

### 1. Crear una Migración

```bash
# Para crear una nueva tabla
php app/bin/migrate make:migration create_products_table --create=products

# Para modificar una tabla existente
php app/bin/migrate make:migration add_price_to_products --table=products
```

### 2. Editar la Migración

El archivo se crea en `app/migrations/`. Edítalo:

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
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
```

### 3. Ejecutar las Migraciones

```bash
php app/bin/migrate
```

### 4. Ver Estado

```bash
php app/bin/migrate --status
```

### 5. Revertir si es Necesario

```bash
# Revertir última migración
php app/bin/migrate --rollback

# Revertir todo y volver a ejecutar
php app/bin/migrate --refresh
```

## Seeders (Datos de Prueba)

### Crear un Seeder

1. Crea `app/database/seeds/ProductsSeeder.php`:

```php
<?php

class ProductsSeeder extends Seeder
{
    public function run()
    {
        $this->insert('products', [
            [
                'name' => 'Producto 1',
                'price' => 99.99,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Producto 2',
                'price' => 149.99,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

2. Registra en `app/database/seeds/DatabaseSeeder.php`:

```php
public function run()
{
    $this->call([
        ProductsSeeder::class,
    ]);
}
```

3. Ejecuta:

```bash
php app/bin/seed
```

## Ejemplos de Columnas Comunes

```php
// Auto-incremento
$table->bigIncrements('id');

// Strings
$table->string('name');           // VARCHAR(255)
$table->string('email', 100);     // VARCHAR(100)
$table->text('description');      // TEXT

// Números
$table->integer('quantity');
$table->decimal('price', 10, 2);  // DECIMAL(10,2)
$table->boolean('is_active');

// Fechas
$table->date('birth_date');
$table->dateTime('published_at');
$table->timestamps();              // created_at, updated_at
$table->softDeletes();             // deleted_at

// Especiales
$table->enum('status', ['active', 'inactive']);
$table->json('metadata');

// Modificadores
$table->string('email')->nullable();
$table->integer('views')->default(0);
$table->string('slug')->unique();
```

## Claves Foráneas

```php
Schema::create('posts', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('user_id');
    $table->string('title');

    // Definir foreign key
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');
});
```

## Comandos Completos

```bash
# Migraciones
php app/bin/migrate                          # Ejecutar pendientes
php app/bin/migrate --status                 # Ver estado
php app/bin/migrate --rollback               # Revertir última
php app/bin/migrate --reset                  # Revertir todas
php app/bin/migrate --refresh                # Reset + migrate
php app/bin/migrate --install                # Crear tabla migrations

# Crear migraciones
php app/bin/migrate make:migration nombre
php app/bin/migrate make:migration nombre --create=tabla
php app/bin/migrate make:migration nombre --table=tabla

# Seeders
php app/bin/seed                             # Ejecutar DatabaseSeeder
php app/bin/seed --class=ProductsSeeder      # Ejecutar seeder específico
```

## Workflow Recomendado para Desarrollo

```bash
# 1. Crear migración
php app/bin/migrate make:migration create_orders_table --create=orders

# 2. Editar app/migrations/YYYY_MM_DD_HHMMSS_create_orders_table.php

# 3. Ejecutar
php app/bin/migrate

# 4. Si hay error, corregir y:
php app/bin/migrate --rollback
php app/bin/migrate

# 5. Crear seeder (opcional)
# Crear app/database/seeds/OrdersSeeder.php

# 6. Ejecutar seeder
php app/bin/seed --class=OrdersSeeder

# 7. Commit a git
git add app/migrations/
git commit -m "Add orders table migration"
```

## Migraciones de Ejemplo

El sistema incluye dos migraciones de ejemplo en `app/migrations/`:
- `create_example_users_table.php` - Tabla con columnas variadas
- `create_example_posts_table.php` - Tabla con foreign keys

Y un seeder de ejemplo:
- `app/database/seeds/ExampleUsersSeeder.php`

**IMPORTANTE:** Estas son solo ejemplos. Puedes eliminarlas cuando no las necesites.

## Soporte Multi-Base de Datos

El sistema soporta automáticamente:
- ✅ MySQL / MariaDB
- ✅ PostgreSQL
- ✅ SQLite

Solo configura tu conexión en `app/config/databases.php`.

## Documentación Completa

Ver [MIGRATIONS.md](./MIGRATIONS.md) para documentación detallada con todos los tipos de columnas, modificadores y ejemplos avanzados.

## Troubleshooting

**Error: "Migration table not found"**
```bash
php app/bin/migrate --install
```

**Resetear todo el sistema**
```bash
php app/bin/migrate --reset
php app/bin/migrate
php app/bin/seed
```

**Ver qué migraciones están ejecutadas**
```bash
php app/bin/migrate --status
```
