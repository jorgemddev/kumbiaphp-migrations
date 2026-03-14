# Guía de Despliegue en Servidor Nuevo

Esta guía explica cómo desplegar tu aplicación en un servidor nuevo donde solo tienes una base de datos vacía creada con sus credenciales.

## 📋 Requisitos Previos

- Servidor con PHP 7.0+ y PDO
- Base de datos creada (vacía) con:
  - Nombre de la base de datos
  - Usuario con permisos
  - Contraseña

## 🚀 Proceso de Despliegue Completo

### Paso 1: Configurar Conexión a Base de Datos

Edita el archivo `.env` o configura las variables de entorno:

```bash
BD_HOST=localhost          # o la IP del servidor de BD
BD_USERNAME=tu_usuario
BD_PASSWORD=tu_contraseña
BD_NAME=nombre_bd
```

O edita directamente `app/config/databases.php`:

```php
return [
    'production' => [
        'host'     => 'localhost',
        'username' => 'tu_usuario',
        'password' => 'tu_contraseña',
        'name'     => 'nombre_bd',
        'type'     => 'mysql',
        'charset'  => 'utf8mb4',
    ],
];
```

### Paso 2: Instalar Tabla de Migraciones

```bash
php app/bin/migrate --install
```

Esto crea la tabla `migrations` que rastrea qué migraciones se han ejecutado.

### Paso 3: Ejecutar Todas las Migraciones

```bash
php app/bin/migrate
```

Este comando:
- ✅ Lee todos los archivos en `app/migrations/`
- ✅ Los ordena por fecha (nombre del archivo)
- ✅ Ejecuta cada migración que no se haya ejecutado antes
- ✅ Crea TODAS tus tablas con su estructura completa

**Resultado:** Tendrás toda la estructura de base de datos lista.

### Paso 4: Poblar Datos Iniciales (Administrador)

Opción A - Usar Seeders (Recomendado):

```bash
php app/bin/seed
```

Opción B - Ejecutar solo el seeder de administrador:

```bash
php app/bin/seed --class=AdminSeeder
```

## 📝 Ejemplo Completo Paso a Paso

### En el Servidor Nuevo:

```bash
# 1. Clonar o subir el código
cd /var/www/mi-aplicacion

# 2. Configurar base de datos
nano .env
# Editar BD_HOST, BD_USERNAME, BD_PASSWORD, BD_NAME

# 3. Instalar sistema de migraciones
php app/bin/migrate --install

# 4. Crear todas las tablas
php app/bin/migrate

# 5. Cargar datos iniciales (admin)
php app/bin/seed

# ¡Listo! La aplicación está operativa
```

### Salida Esperada:

```
$ php app/bin/migrate --install
Installing migration table...
Migration table created successfully.

$ php app/bin/migrate
Running migrations...

Migrating: 2024_01_01_100000_create_users_table.php
Migrated:  2024_01_01_100000_create_users_table.php

Migrating: 2024_01_01_110000_create_roles_table.php
Migrated:  2024_01_01_110000_create_roles_table.php

Migrating: 2024_01_01_120000_create_products_table.php
Migrated:  2024_01_01_120000_create_products_table.php

... (todas tus migraciones)

Migrations completed successfully!

$ php app/bin/seed
Seeding database...
Running: DatabaseSeeder

Seeding: AdminSeeder
✓ Admin user created successfully!

Database seeding completed!
```

## 🔧 Crear Seeder para Administrador

Si aún no tienes un seeder para el administrador, créalo:

### 1. Crear el archivo `app/database/seeds/AdminSeeder.php`:

```php
<?php

class AdminSeeder extends Seeder
{
    public function run()
    {
        $this->output('Seeding admin user...');

        // Verificar si ya existe
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['admin@tudominio.com']);

        if ($stmt->rowCount() > 0) {
            $this->output('Admin user already exists, skipping...');
            return;
        }

        // Crear usuario administrador
        $this->insert('users', [
            [
                'name' => 'Administrador',
                'email' => 'admin@tudominio.com',
                'password' => password_hash('TuPasswordSeguro123!', PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $this->output('✓ Admin user created successfully!');
        $this->output('  Email: admin@tudominio.com');
        $this->output('  Password: TuPasswordSeguro123!');
        $this->output('  IMPORTANTE: Cambia la contraseña después del primer login!');
    }
}
```

### 2. Registrar en `app/database/seeds/DatabaseSeeder.php`:

```php
<?php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            // Agrega otros seeders según necesites
        ]);

        $this->output('Database seeding completed!');
    }
}
```

### 3. Ejecutar:

```bash
php app/bin/seed
```

## 🔄 Escenarios Comunes

### Servidor de Producción Inicial (Primera vez)

```bash
# 1. Subir código al servidor
git clone https://github.com/tu-repo/proyecto.git
cd proyecto

# 2. Configurar
cp .env.example .env
nano .env  # Editar credenciales

# 3. Desplegar BD
php app/bin/migrate --install
php app/bin/migrate
php app/bin/seed

# 4. Configurar permisos si es necesario
chmod -R 755 storage
```

### Actualizar Servidor Existente (Nueva migración)

```bash
# 1. Actualizar código
git pull

# 2. Ejecutar nuevas migraciones
php app/bin/migrate

# No ejecutar seed en producción existente
# (ya tiene datos)
```

### Servidor de Staging/Testing

```bash
git pull
php app/bin/migrate --refresh  # Reset + migrate
php app/bin/seed               # Datos de prueba
```

### Desarrollo Local (Reiniciar todo)

```bash
php app/bin/migrate --reset    # Eliminar todo
php app/bin/migrate            # Crear todo
php app/bin/seed               # Datos de prueba
```

## 🐳 Docker / Caprover

### En Dockerfile:

```dockerfile
FROM php:8.1-apache

# ... tu configuración ...

# Copiar código
COPY . /var/www/html

# Ejecutar migraciones al construir
RUN php app/bin/migrate --install
RUN php app/bin/migrate
RUN php app/bin/seed
```

### En captain-definition (Caprover):

```json
{
  "schemaVersion": 2,
  "dockerfileLines": [
    "FROM php:8.1-apache",
    "COPY . /var/www/html",
    "RUN php app/bin/migrate --install || true",
    "RUN php app/bin/migrate || true"
  ]
}
```

O usar script de deploy:

```json
{
  "schemaVersion": 2,
  "dockerfileLines": [
    "FROM php:8.1-apache",
    "COPY . /var/www/html",
    "COPY deploy.sh /deploy.sh",
    "RUN chmod +x /deploy.sh"
  ],
  "postDeployCommand": "/deploy.sh"
}
```

**deploy.sh**:
```bash
#!/bin/bash
php app/bin/migrate --install
php app/bin/migrate
php app/bin/seed
```

## 📊 Verificar Estado

### Ver qué migraciones se han ejecutado:

```bash
php app/bin/migrate --status
```

Salida:
```
Migration Status:

Migration                                         Batch      Status
--------------------------------------------------------------------------------
2024_01_01_100000_create_users_table.php         1          Ran
2024_01_01_110000_create_roles_table.php         1          Ran
2024_01_02_100000_create_products_table.php      Pending
```

### Verificar que las tablas existen:

```bash
# MySQL
mysql -u usuario -p nombre_bd -e "SHOW TABLES;"

# O desde PHP
php -r "
require 'app/libs/migration/MigrationDatabase.php';
\$pdo = MigrationDatabase::getConnection();
\$tables = \$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r(\$tables);
"
```

## ⚠️ Troubleshooting

### Error: "Could not connect to database"

**Causa:** Credenciales incorrectas o servidor de BD no accesible.

**Solución:**
```bash
# Verificar conexión
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=nombre_bd', 'usuario', 'password');
echo 'Conexión exitosa!';
"
```

### Error: "Migration table not found"

**Causa:** No se instaló la tabla de migraciones.

**Solución:**
```bash
php app/bin/migrate --install
```

### Error: "Table already exists"

**Causa:** Intentando crear una tabla que ya existe.

**Solución:**
```bash
# Ver qué migraciones se ejecutaron
php app/bin/migrate --status

# Si necesitas reiniciar todo
php app/bin/migrate --reset
php app/bin/migrate
```

### Error: Foreign key constraint fails

**Causa:** Intentando crear tablas en orden incorrecto.

**Solución:** Renombra tus migraciones para que se ejecuten en orden:
```
2024_01_01_100000_create_users_table.php      ← Primero
2024_01_01_110000_create_posts_table.php      ← Después (depende de users)
```

## 🔐 Buenas Prácticas de Seguridad

1. **Nunca versiones credenciales:**
   ```bash
   # .gitignore
   .env
   app/config/databases.php  # Si tiene credenciales hardcodeadas
   ```

2. **Usa variables de entorno en producción:**
   ```php
   'password' => getenv('BD_PASSWORD')  // ✅ Bien
   'password' => 'mi_password'          // ❌ Mal
   ```

3. **Cambia credenciales de admin después del primer deploy:**
   ```bash
   # Después de php app/bin/seed
   # Cambia la contraseña del admin desde la aplicación
   ```

4. **Diferentes credenciales por entorno:**
   - Desarrollo: usuario con permisos completos
   - Producción: usuario con permisos mínimos necesarios

## 📝 Checklist de Despliegue

- [ ] Base de datos creada
- [ ] Credenciales configuradas en `.env`
- [ ] `php app/bin/migrate --install` ejecutado
- [ ] `php app/bin/migrate` ejecutado sin errores
- [ ] `php app/bin/seed` ejecutado (datos iniciales)
- [ ] Verificado con `php app/bin/migrate --status`
- [ ] Login de administrador funciona
- [ ] Contraseña de admin cambiada
- [ ] Permisos de archivos configurados
- [ ] Variables de entorno seguras

## 🎯 Resumen

Para un servidor nuevo con BD vacía:

```bash
# 1 comando para instalar
php app/bin/migrate --install

# 1 comando para crear toda la estructura
php app/bin/migrate

# 1 comando para datos iniciales
php app/bin/seed
```

**¡Eso es todo!** Tu aplicación está lista para funcionar.

---

**Siguiente paso:** Lee [MIGRATIONS.md](MIGRATIONS.md) para aprender a crear tus propias migraciones.
