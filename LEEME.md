# 🚀 Sistema de Migraciones para KumbiaPHP

Sistema profesional de migraciones de base de datos para KumbiaPHP, inspirado en Laravel Migrations.

## 📖 Documentación

Este paquete contiene el sistema completo de migraciones que puedes integrar en cualquier proyecto KumbiaPHP.

### 📚 Guías Disponibles

1. **[GUIA_USO.md](GUIA_USO.md)** - 📘 Guía completa de uso en español
   - Instalación paso a paso
   - Comandos disponibles
   - Ejemplos básicos y avanzados
   - Tipos de columnas
   - Buenas prácticas

2. **[EJEMPLOS_INTEGRACION.md](EJEMPLOS_INTEGRACION.md)** - 💡 Ejemplos prácticos
   - Configuración de base de datos
   - Sistemas completos (usuarios, blog, e-commerce)
   - Seeders avanzados
   - Scripts de automatización

3. **[README.md](README.md)** - 📄 Documentación técnica original
   - Características del sistema
   - Referencia rápida
   - Comandos CLI

4. **[docs/](docs/)** - 📁 Documentación detallada
   - `MIGRATIONS_QUICKSTART.md` - Inicio rápido
   - `MIGRATIONS.md` - Referencia completa
   - `MIGRATE_FROM_EXISTING_DB.md` - Migrar BD existente
   - `DEPLOYMENT_GUIDE.md` - Guía de despliegue
   - `SAFETY_GUIDE.md` - Guía de seguridad

## ⚡ Inicio Rápido

### 1. Copiar al proyecto

```bash
# Copiar sistema de migraciones
cp -r kumbiaphp-migrations/* tu-proyecto/app/libs/migration/

# Copiar comandos CLI
cp kumbiaphp-migrations/bin/migrate tu-proyecto/app/bin/
cp kumbiaphp-migrations/bin/seed tu-proyecto/app/bin/

# Dar permisos
chmod +x tu-proyecto/app/bin/migrate
chmod +x tu-proyecto/app/bin/seed
```

### 2. Crear directorios

```bash
mkdir -p tu-proyecto/app/migrations
mkdir -p tu-proyecto/app/database/seeds
```

### 3. Instalar

```bash
cd tu-proyecto
php app/bin/migrate --install
```

### 4. Crear primera migración

```bash
php app/bin/migrate make:migration create_users_table --create=users
```

### 5. Ejecutar

```bash
php app/bin/migrate
```

## 🎯 ¿Qué incluye?

### Core del Sistema

- ✅ **Migration.php** - Clase base para migraciones
- ✅ **Migrator.php** - Motor de ejecución
- ✅ **Schema.php** - Fachada para operaciones de esquema
- ✅ **Blueprint.php** - Constructor fluido de tablas
- ✅ **MigrationDatabase.php** - Gestor de conexiones
- ✅ **MigrationRepository.php** - Tracking de migraciones
- ✅ **MigrationCreator.php** - Generador de archivos

### Definiciones

- ✅ **ColumnDefinition.php** - Definición fluida de columnas
- ✅ **ForeignKeyDefinition.php** - Claves foráneas

### Gramáticas (Adaptadores de BD)

- ✅ **Grammar.php** - Clase base
- ✅ **MySqlGrammar.php** - MySQL/MariaDB
- ✅ **PostgresGrammar.php** - PostgreSQL
- ✅ **SQLiteGrammar.php** - SQLite

### Seeders

- ✅ **Seeder.php** - Clase base para seeders

### Comandos CLI

- ✅ **bin/migrate** - Comando de migraciones
- ✅ **bin/seed** - Comando de seeders

### Documentación

- ✅ Guías en español
- ✅ Ejemplos completos
- ✅ Casos de uso reales
- ✅ Buenas prácticas

## 🌟 Características

- **Sintaxis Fluida** - Inspirada en Laravel
- **Multi-Base de Datos** - MySQL, PostgreSQL, SQLite
- **Transacciones Automáticas** - Seguridad garantizada
- **Rollback Completo** - Revierte cambios fácilmente
- **Claves Foráneas** - Con CASCADE, RESTRICT, SET NULL
- **Soft Deletes** - Eliminación lógica
- **Seeders** - Pobla datos de prueba
- **Sin Dependencias** - Solo PHP + PDO
- **Versionado** - Control total de cambios

## 📦 Compatibilidad

- **PHP:** 7.0 o superior
- **Bases de datos:** MySQL, MariaDB, PostgreSQL, SQLite
- **KumbiaPHP:** Todas las versiones
- **Entornos:** XAMPP, MAMP, WAMP, VPS, Docker, Caprover

## 🎓 Aprende Más

### Para Principiantes

1. Lee **[GUIA_USO.md](GUIA_USO.md)** - Empieza aquí
2. Revisa **[EJEMPLOS_INTEGRACION.md](EJEMPLOS_INTEGRACION.md)** - Casos prácticos
3. Consulta **[docs/MIGRATIONS_QUICKSTART.md](docs/MIGRATIONS_QUICKSTART.md)** - Guía rápida

### Para Usuarios Avanzados

1. **[docs/MIGRATIONS.md](docs/MIGRATIONS.md)** - Referencia completa
2. **[docs/MIGRATE_FROM_EXISTING_DB.md](docs/MIGRATE_FROM_EXISTING_DB.md)** - Migrar BD existente
3. **[docs/SAFETY_GUIDE.md](docs/SAFETY_GUIDE.md)** - Seguridad en producción

## 💡 Ejemplo Rápido

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

## 🔧 Comandos Principales

```bash
# Crear migración
php app/bin/migrate make:migration create_products_table --create=products

# Ejecutar migraciones
php app/bin/migrate

# Ver estado
php app/bin/migrate --status

# Revertir última
php app/bin/migrate --rollback

# Revertir todas
php app/bin/migrate --reset

# Reset + ejecutar
php app/bin/migrate --refresh

# Ejecutar seeders
php app/bin/seed
```

## 📂 Estructura del Paquete

```
kumbiaphp-migrations/
├── bin/                     # Comandos CLI
│   ├── migrate
│   └── seed
├── docs/                    # Documentación detallada
│   ├── MIGRATIONS_QUICKSTART.md
│   ├── MIGRATIONS.md
│   ├── MIGRATE_FROM_EXISTING_DB.md
│   ├── DEPLOYMENT_GUIDE.md
│   └── SAFETY_GUIDE.md
├── grammar/                 # Adaptadores de BD
│   ├── Grammar.php
│   ├── MySqlGrammar.php
│   ├── PostgresGrammar.php
│   └── SQLiteGrammar.php
├── Blueprint.php
├── ColumnDefinition.php
├── ForeignKeyDefinition.php
├── Migration.php
├── MigrationCreator.php
├── MigrationDatabase.php
├── MigrationRepository.php
├── Migrator.php
├── Schema.php
├── Seeder.php
├── README.md                # Este archivo
├── GUIA_USO.md             # Guía completa en español
└── EJEMPLOS_INTEGRACION.md # Ejemplos prácticos
```

## 🤝 Contribuciones

Este sistema está basado en Laravel Migrations y adaptado para KumbiaPHP. Es de código abierto y puede ser usado libremente en cualquier proyecto.

## 📄 Licencia

Sistema desarrollado siguiendo la filosofía KumbiaPHP: Simple, profesional y sin dependencias.

---

## 🚀 ¡Empieza Ahora!

1. **Lee** [GUIA_USO.md](GUIA_USO.md) para instalación completa
2. **Revisa** [EJEMPLOS_INTEGRACION.md](EJEMPLOS_INTEGRACION.md) para casos de uso
3. **Consulta** [docs/](docs/) para documentación avanzada

**¿Preguntas?** Revisa la documentación en la carpeta `docs/`

---

**Desarrollado para la comunidad KumbiaPHP** 💚
