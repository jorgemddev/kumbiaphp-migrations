# Migrar desde Base de Datos Existente

Guía para proyectos que **ya tienen una BD en producción** sin sistema de migraciones.

## 🎯 Objetivo

Tienes una BD en producción funcionando, y quieres:
1. **Guardar la estructura actual** como punto de partida
2. **Desplegar en servidores nuevos** (con estructura + admin, sin clientes)
3. **Usar migraciones** para cambios futuros

## 📋 Workflow Completo

### FASE 1: Exportar BD Actual (UNA SOLA VEZ)

####  Usar el script helper 

```bash
# Solo estructura (CREATE TABLE, índices, etc)
php app/bin/db-schema export

# Estructura + datos de admin/catálogos (ignorando clientes)
php app/bin/db-schema export --with-data
```

Cuando uses `--with-data`, el script te preguntará qué tablas ignorar:
```
Tablas a IGNORAR (sin exportar datos):
Ingresa los nombres separados por coma:
> clientes,ordenes,facturas,pagos,tickets
```


### FASE 2: Versionar el Schema

```bash
# Agregar a Git
git add app/database/schema/
git commit -m "Add initial database schema"
git push
```

**Importante:** Este archivo SQL es tu **"migración cero"**, el punto de partida.

### FASE 3: Desplegar en Servidor Nuevo

#### En el servidor nuevo:

```bash
# 1. Clonar código
git clone [repo]
cd proyecto

# 2. Configurar BD
nano .env
# BD_HOST=localhost
# BD_USERNAME=usuario
# BD_PASSWORD=password
# BD_NAME=nombre_bd

# 3. Importar schema inicial
php app/bin/db-schema import --with-data

# O manualmente:
mysql -u usuario -p nombre_bd < app/database/schema/schema_with_seed.sql

# 4. Instalar sistema de migraciones para cambios futuros
php app/bin/migrate --install

# ¡Listo! BD completa con admin, sin clientes
```

### FASE 4: Cambios Futuros con Migraciones

A partir de ahora, **TODOS los cambios** se hacen con migraciones:

```bash
# Necesitas agregar columna "telefono" a tabla users
php app/bin/migrate make:migration add_telefono_to_users --table=users

# Editar app/migrations/YYYY_MM_DD_HHMMSS_add_telefono_to_users.php
# Ejecutar
php app/bin/migrate
```

## 🔄 Diagrama del Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│ PRODUCCIÓN ACTUAL (sin migraciones)                             │
│ - BD funcionando con datos                                      │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ php app/bin/db-schema export --with-data
              │ (ignorando tablas de clientes)
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ app/database/schema/schema_with_seed.sql                        │
│ - Estructura completa                                           │
│ - Datos de admin/catálogos                                      │
│ - SIN datos de clientes                                         │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ git commit & push
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ SERVIDOR NUEVO                                                   │
│ 1. git clone                                                     │
│ 2. Configurar .env                                               │
│ 3. php app/bin/db-schema import --with-data                      │
│ 4. php app/bin/migrate --install                                 │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ Cambios futuros
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ DESARROLLO                                                       │
│ - php app/bin/migrate make:migration ...                         │
│ - Editar migración                                               │
│ - php app/bin/migrate                                            │
│ - git commit & push                                              │
└─────────────┬───────────────────────────────────────────────────┘
              │
              │ git pull & migrate
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ PRODUCCIÓN / OTROS SERVIDORES                                    │
│ - git pull                                                       │
│ - php app/bin/migrate (ejecuta nuevos cambios)                   │
└─────────────────────────────────────────────────────────────────┘
```

## 📁 Estructura de Archivos

```
app/
├── database/
│   ├── schema/
│   │   ├── .gitkeep
│   │   ├── schema.sql              ← Solo estructura
│   │   └── schema_with_seed.sql    ← Estructura + admin (ESTE USAR)
│   └── seeds/
│       └── DatabaseSeeder.php
├── migrations/                      ← Cambios FUTUROS (desde hoy)
│   └── (vacío inicialmente)
└── bin/
    ├── db-schema                    ← Helper export/import
    └── migrate                      ← Para cambios futuros
```

## 🎯 Ejemplo Completo Real

### Situación:
- BD en producción con 50 tablas
- 10,000 clientes
- Quieres servidor de staging sin clientes

### Paso 1: Exportar (en producción)

```bash
php app/bin/db-schema export --with-data

# Te pregunta qué ignorar:
> clientes,vehiculos,ordenes,pagos,facturas,tickets
```

**Resultado:** `app/database/schema/schema_with_seed.sql` con:
- ✅ 50 tablas (estructura completa)
- ✅ Usuarios admin
- ✅ Catálogos (roles, permisos, configuraciones)
- ❌ SIN clientes ni sus datos relacionados

### Paso 2: Commit

```bash
git add app/database/schema/schema_with_seed.sql
git commit -m "Add initial database schema with seed data"
git push
```

### Paso 3: Servidor Staging (nuevo)

```bash
# Servidor staging
git clone https://github.com/tu-repo/proyecto.git
cd proyecto

# Configurar
cp .env.example .env.staging
nano .env.staging
# BD_NAME=proyecto_staging

# Importar
php app/bin/db-schema import --with-data
# Confirmas: yes

# Instalar migraciones
php app/bin/migrate --install
```

**Resultado:** BD completa, lista para trabajar, SIN clientes.

### Paso 4: Desarrollo (agregar nueva funcionalidad)

```bash
# Necesitas agregar campo "descuento_maximo" a usuarios
php app/bin/migrate make:migration add_descuento_maximo_to_usuarios --table=usuarios

# Editar migración
nano app/migrations/2025_12_31_HHMMSS_add_descuento_maximo_to_usuarios.php
```

```php
public function up()
{
    Schema::table('usuarios', function (Blueprint $table) {
        $table->decimal('descuento_maximo', 5, 2)->default(10.00)->after('email');
    });
}

public function down()
{
    Schema::table('usuarios', function (Blueprint $table) {
        $table->dropColumn('descuento_maximo');
    });
}
```

```bash
# Ejecutar en desarrollo
php app/bin/migrate

# Commit
git add app/migrations/
git commit -m "Add descuento_maximo field to usuarios"
git push
```

### Paso 5: Aplicar en Producción

```bash
# En servidor producción
git pull
php app/bin/migrate  # Solo ejecuta la nueva migración

# En servidor staging
git pull
php app/bin/migrate  # También se aplica
```

## 🔧 Comandos Útiles

```bash
# EXPORTAR
php app/bin/db-schema export              # Solo estructura
php app/bin/db-schema export --with-data  # Con datos (pregunta qué ignorar)

# IMPORTAR
php app/bin/db-schema import              # Importar solo estructura
php app/bin/db-schema import --with-data  # Importar con datos

# MIGRACIONES (cambios futuros)
php app/bin/migrate --install             # Primera vez en servidor nuevo
php app/bin/migrate                       # Ejecutar cambios
php app/bin/migrate --status              # Ver estado
```

## ⚠️ Consideraciones Importantes

### 1. El Schema Inicial NO Cambia

Una vez exportado `schema_with_seed.sql`, **NO lo modifiques**. Es tu punto de partida histórico.

Cambios futuros van en migraciones.

### 2. Tablas a Ignorar al Exportar con Datos

Identifica tablas con **datos de clientes/transaccionales**:
- ✅ Ignorar: clientes, ordenes, pagos, facturas, tickets, logs
- ❌ NO ignorar: usuarios_admin, roles, permisos, configuraciones, catálogos

### 3. Versionamiento

```bash
# SÍ versionar
git add app/database/schema/schema_with_seed.sql
git add app/migrations/

# NO versionar (agregar a .gitignore)
.env
*.log
```

### 4. Actualizar Schema Inicial (Rara vez)

Si necesitas actualizar el schema base (ej: después de 1 año con 100 migraciones):

```bash
# Exportar nuevo baseline
php app/bin/db-schema export --with-data

# Renombrar
mv app/database/schema/schema_with_seed.sql \
   app/database/schema/schema_with_seed_v2.sql

# Actualizar docs para usar v2
```

## 🎓 Preguntas Frecuentes

**P: ¿Debo ejecutar el schema Y las migraciones?**

R: Depende:
- **Servidor nuevo desde cero:** Solo schema (`php app/bin/db-schema import`)
- **Servidor nuevo con código actualizado:** Schema + migraciones posteriores
- **Servidor existente:** Solo migraciones nuevas (`php app/bin/migrate`)

**P: ¿Qué pasa si tengo cambios en producción sin migraciones?**

R: Tienes 2 opciones:
1. Exportar nuevo schema (recomendado cada 6-12 meses)
2. Crear migraciones "catch-up" para igualar

**P: ¿Cómo sincronizo dev, staging y producción?**

R:
```bash
# Todos usan mismo schema inicial
# + mismas migraciones desde Git
# = misma estructura
```

**P: ¿Puedo combinar ambos enfoques?**

R: ¡Sí! Exactamente este es el enfoque:
- Schema inicial: Base sólida
- Migraciones: Cambios incrementales

## ✅ Checklist

- [ ] Exporté schema de producción
- [ ] Ignoré tablas de clientes al exportar
- [ ] Guardé en `app/database/schema/`
- [ ] Commiteé a Git
- [ ] Probé import en servidor de prueba
- [ ] Instalé sistema de migraciones
- [ ] Documenté qué tablas ignorar
- [ ] Equipo sabe usar migraciones para cambios

---

**Siguiente:** [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Cómo desplegar en diferentes entornos
