# 🎯 INICIO RÁPIDO - Sistema de Migraciones KumbiaPHP

## ✅ ¿Qué tienes aquí?

Un sistema completo de migraciones de base de datos para KumbiaPHP que te permite:

- ✅ Versionar cambios en la base de datos
- ✅ Crear y modificar tablas con código PHP
- ✅ Revertir cambios si algo sale mal
- ✅ Trabajar en equipo sin conflictos
- ✅ Desplegar en producción de forma segura

## 📚 Documentación Disponible

| Archivo | Descripción | Para quién |
|---------|-------------|------------|
| **LEEME.md** | Índice principal y overview | Todos |
| **GUIA_USO.md** | Guía completa en español | Principiantes |
| **EJEMPLOS_INTEGRACION.md** | Ejemplos prácticos completos | Desarrolladores |
| **README.md** | Documentación técnica original | Referencia |
| **docs/** | Documentación detallada | Avanzados |

## 🚀 Instalación en 5 Pasos

### 1️⃣ Copiar archivos

```bash
# Ir a tu proyecto KumbiaPHP
cd /ruta/a/tu-proyecto

# Copiar sistema de migraciones
cp -r /Users/jdev/Developer/Php/kumbiaphp-migrations/* app/libs/migration/

# Copiar comandos CLI
cp app/libs/migration/bin/migrate app/bin/
cp app/libs/migration/bin/seed app/bin/

# Dar permisos de ejecución
chmod +x app/bin/migrate
chmod +x app/bin/seed
```

### 2️⃣ Crear directorios

```bash
mkdir -p app/migrations
mkdir -p app/database/seeds
```

### 3️⃣ Verificar configuración de BD

Edita `app/config/databases.php`:

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
];
```

### 4️⃣ Instalar tabla de migraciones

```bash
php app/bin/migrate --install
```

### 5️⃣ Crear tu primera migración

```bash
php app/bin/migrate make:migration create_users_table --create=users
```

Edita el archivo generado en `app/migrations/`:

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
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

Ejecuta:

```bash
php app/bin/migrate
```

## 🎓 Siguientes Pasos

### Para Aprender

1. **Lee GUIA_USO.md** - Tutorial completo paso a paso
2. **Revisa EJEMPLOS_INTEGRACION.md** - Casos de uso reales
3. **Consulta docs/MIGRATIONS_QUICKSTART.md** - Guía rápida oficial

### Para Usar

```bash
# Crear migraciones
php app/bin/migrate make:migration nombre_descriptivo

# Ejecutar migraciones
php app/bin/migrate

# Ver estado
php app/bin/migrate --status

# Revertir última
php app/bin/migrate --rollback
```

## 📋 Comandos Más Usados

```bash
# CREAR MIGRACIONES
php app/bin/migrate make:migration create_products_table --create=products
php app/bin/migrate make:migration add_price_to_products --table=products

# EJECUTAR
php app/bin/migrate                    # Ejecutar pendientes
php app/bin/migrate --status           # Ver estado
php app/bin/migrate --rollback         # Revertir última
php app/bin/migrate --reset            # Revertir todas
php app/bin/migrate --refresh          # Reset + ejecutar

# SEEDERS
php app/bin/seed                       # Ejecutar DatabaseSeeder
php app/bin/seed --class=UsersSeeder   # Ejecutar seeder específico
```

## 💡 Ejemplo Completo

### 1. Crear migración

```bash
php app/bin/migrate make:migration create_posts_table --create=posts
```

### 2. Editar migración

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

### 3. Ejecutar

```bash
php app/bin/migrate
```

### 4. Crear seeder

Crear `app/database/seeds/PostsSeeder.php`:

```php
<?php

class PostsSeeder extends Seeder
{
    public function run()
    {
        $this->insert('posts', [
            [
                'user_id' => 1,
                'title' => 'Mi primer post',
                'content' => 'Contenido del post',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

### 5. Ejecutar seeder

```bash
php app/bin/seed --class=PostsSeeder
```

## 🔥 Tipos de Columnas Más Usados

```php
// IDs
$table->bigIncrements('id');
$table->unsignedBigInteger('user_id');

// Strings
$table->string('name');
$table->string('email', 100)->unique();
$table->text('description');

// Números
$table->integer('quantity');
$table->decimal('price', 10, 2);
$table->boolean('is_active')->default(true);

// Fechas
$table->timestamps();              // created_at, updated_at
$table->softDeletes();             // deleted_at
$table->dateTime('published_at');

// Especiales
$table->enum('status', ['active', 'inactive']);
$table->json('metadata');
```

## 🎯 Modificadores Comunes

```php
->nullable()                    // Permite NULL
->default(valor)                // Valor por defecto
->unique()                      // Índice único
->index()                       // Índice normal
->unsigned()                    // Sin signo (números)
->after('columna')              // Después de columna
->comment('Descripción')        // Comentario
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
      ->onDelete('cascade');

// Con SET NULL
$table->foreign('author_id')
      ->references('id')
      ->on('users')
      ->onDelete('set null');
```

## ⚠️ Importante

### ✅ Hacer

- Siempre define el método `down()` para rollback
- Testea localmente antes de producción
- Versiona las migraciones en Git
- Haz backup antes de ejecutar en producción

### ❌ Evitar

- NO modifiques migraciones ya ejecutadas en producción
- NO elimines migraciones del historial
- NO ejecutes rollback en producción sin backup
- NO olvides el método `down()`

## 🆘 Ayuda Rápida

### Error: "Migration table not found"

```bash
php app/bin/migrate --install
```

### Error: "Could not connect to database"

Verifica `app/config/databases.php`

### Resetear todo (CUIDADO: borra datos)

```bash
php app/bin/migrate --reset
php app/bin/migrate
php app/bin/seed
```

## 📖 Más Información

- **GUIA_USO.md** - Guía completa con todos los detalles
- **EJEMPLOS_INTEGRACION.md** - Ejemplos de sistemas completos
- **docs/MIGRATIONS.md** - Referencia completa de métodos
- **docs/SAFETY_GUIDE.md** - Guía de seguridad para producción

## 🎉 ¡Listo!

Ya tienes todo lo necesario para usar migraciones en tu proyecto KumbiaPHP.

**Siguiente paso:** Lee **GUIA_USO.md** para aprender más.

---

**Sistema desarrollado para la comunidad KumbiaPHP** 💚

¿Dudas? Consulta la documentación en la carpeta `docs/`
