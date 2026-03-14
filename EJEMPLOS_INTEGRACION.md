# Ejemplos de Integración - Sistema de Migraciones KumbiaPHP

## 📁 Estructura de Archivos en tu Proyecto

Después de instalar el sistema, tu proyecto debe tener esta estructura:

```
tu-proyecto/
├── app/
│   ├── bin/
│   │   ├── migrate              ← Comando de migraciones
│   │   └── seed                 ← Comando de seeders
│   ├── config/
│   │   └── databases.php        ← Configuración de BD
│   ├── database/
│   │   └── seeds/               ← Seeders aquí
│   │       ├── DatabaseSeeder.php
│   │       └── UsersSeeder.php
│   ├── libs/
│   │   └── migration/           ← Sistema de migraciones
│   │       ├── docs/
│   │       ├── grammar/
│   │       ├── Blueprint.php
│   │       ├── Migration.php
│   │       └── ...
│   └── migrations/              ← Tus migraciones aquí
│       ├── 2025_01_20_120000_create_users_table.php
│       └── 2025_01_20_130000_create_posts_table.php
```

## ⚙️ Configuración de Base de Datos

### Ejemplo completo de `app/config/databases.php`

```php
<?php
/**
 * Configuración de bases de datos
 * El sistema detecta automáticamente el entorno (development/production)
 */

return [
    // Entorno de desarrollo (local)
    'development' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'name' => 'mi_proyecto_dev',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'port' => 3306,
    ],

    // Entorno de producción
    'production' => [
        'type' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'mi_proyecto_prod',
        'username' => getenv('DB_USER') ?: 'usuario',
        'password' => getenv('DB_PASS') ?: 'password',
        'charset' => 'utf8mb4',
        'port' => getenv('DB_PORT') ?: 3306,
    ],

    // Entorno de testing (opcional)
    'testing' => [
        'type' => 'sqlite',
        'name' => APP_PATH . 'temp/testing.db',
    ],
];
```

### Usando variables de entorno (.env)

Crear archivo `.env` en la raíz del proyecto:

```env
DB_HOST=localhost
DB_NAME=mi_base_datos
DB_USER=root
DB_PASS=mi_password
DB_PORT=3306
```

## 🎯 Ejemplos Completos de Migraciones

### 1. Sistema de Usuarios Completo

```bash
php app/bin/migrate make:migration create_users_system --create=users
```

```php
<?php

class CreateUsersSystem extends Migration
{
    public function up()
    {
        // Tabla de usuarios
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->text('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('email');
            $table->index('username');
            $table->index('is_active');
        });

        // Tabla de roles
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Tabla pivote usuario-rol
        Schema::create('user_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('cascade');

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
}
```

### 2. Sistema de Blog

```php
<?php

class CreateBlogSystem extends Migration
{
    public function up()
    {
        // Categorías
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');
        });

        // Posts
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');

            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
        });

        // Comentarios
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('author_name', 100)->nullable();
            $table->string('author_email', 100)->nullable();
            $table->text('content');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('post_id')
                  ->references('id')
                  ->on('posts')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('comments')
                  ->onDelete('cascade');
        });

        // Tags
        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->timestamps();
        });

        // Tabla pivote post-tag
        Schema::create('post_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('post_id')
                  ->references('id')
                  ->on('posts')
                  ->onDelete('cascade');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tags')
                  ->onDelete('cascade');

            $table->primary(['post_id', 'tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
    }
}
```

### 3. Sistema de E-commerce

```php
<?php

class CreateEcommerceSystem extends Migration
{
    public function up()
    {
        // Productos
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('specifications')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('slug');
            $table->index('is_active');
        });

        // Órdenes
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
                  ->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_method', 50);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                  ->default('pending');
            $table->text('shipping_address');
            $table->text('billing_address');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
        });

        // Items de orden
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('product_sku', 50);
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

        // Carrito de compras
        Schema::create('cart', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
}
```

## 🌱 Ejemplos de Seeders

### DatabaseSeeder.php

```php
<?php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Desactivar verificaciones de claves foráneas
        Schema::disableForeignKeyConstraints();

        // Ejecutar seeders en orden
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            CategoriesSeeder::class,
            ProductsSeeder::class,
        ]);

        // Reactivar verificaciones
        Schema::enableForeignKeyConstraints();
    }
}
```

### RolesSeeder.php

```php
<?php

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Acceso total al sistema',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Puede crear y editar contenido',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user',
                'display_name' => 'Usuario',
                'description' => 'Usuario regular del sistema',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->insert('roles', $roles);
    }
}
```

### UsersSeeder.php

```php
<?php

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Crear usuario admin
        $adminId = $this->createUser([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'first_name' => 'Admin',
            'last_name' => 'Sistema',
            'is_active' => 1,
            'email_verified' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        // Asignar rol admin
        $this->assignRole($adminId, 1); // ID del rol admin

        // Crear usuarios de prueba
        for ($i = 1; $i <= 10; $i++) {
            $userId = $this->createUser([
                'username' => "user{$i}",
                'email' => "user{$i}@example.com",
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'first_name' => "Usuario",
                'last_name' => "Test {$i}",
                'is_active' => 1,
            ]);

            // Asignar rol user
            $this->assignRole($userId, 3); // ID del rol user
        }
    }

    private function createUser($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->insert('users', [$data]);

        // Obtener el ID del último usuario insertado
        $pdo = $this->getConnection();
        return $pdo->lastInsertId();
    }

    private function assignRole($userId, $roleId)
    {
        $this->insert('user_roles', [
            [
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }
}
```

### ProductsSeeder.php

```php
<?php

class ProductsSeeder extends Seeder
{
    public function run()
    {
        $products = [];

        for ($i = 1; $i <= 50; $i++) {
            $price = rand(1000, 50000) / 100;
            $products[] = [
                'sku' => 'PROD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'name' => "Producto de Ejemplo {$i}",
                'slug' => "producto-ejemplo-{$i}",
                'description' => "Descripción del producto {$i}",
                'price' => $price,
                'sale_price' => rand(0, 1) ? $price * 0.9 : null,
                'stock' => rand(0, 100),
                'min_stock' => 5,
                'is_active' => 1,
                'is_featured' => rand(0, 1),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->insert('products', $products);
    }
}
```

## 🔄 Scripts de Automatización

### Script de despliegue (deploy.sh)

```bash
#!/bin/bash

echo "🚀 Iniciando despliegue..."

# Actualizar código
git pull origin main

# Ejecutar migraciones
php app/bin/migrate

# Limpiar caché (si aplica)
rm -rf app/temp/cache/*

echo "✅ Despliegue completado!"
```

### Script de reset local (reset-local.sh)

```bash
#!/bin/bash

echo "⚠️  ADVERTENCIA: Esto borrará todos los datos!"
read -p "¿Estás seguro? (yes/no): " confirm

if [ "$confirm" = "yes" ]; then
    echo "🔄 Reseteando base de datos..."
    
    php app/bin/migrate --reset
    php app/bin/migrate
    php app/bin/seed
    
    echo "✅ Base de datos reseteada!"
else
    echo "❌ Operación cancelada"
fi
```

## 📝 Notas Importantes

1. **Backup antes de producción**: Siempre haz backup de la BD antes de ejecutar migraciones en producción
2. **Testea localmente**: Prueba todas las migraciones en desarrollo antes de desplegar
3. **Versionado**: Commitea las migraciones a Git junto con el código
4. **Documentación**: Documenta cambios complejos en las migraciones
5. **Rollback**: Ten un plan de rollback para cada migración importante

---

¡Sistema listo para usar en producción! 🎉
