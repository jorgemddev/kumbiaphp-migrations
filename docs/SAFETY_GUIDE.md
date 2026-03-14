# Guía de Seguridad - Migraciones y Schema

Esta guía explica cómo usar el sistema de migraciones **SIN AFECTAR DATOS DE PRODUCCIÓN**.

## 🛡️ Principios de Seguridad

### ✅ Operaciones SEGURAS (Solo lectura)

```bash
# VER estado - NO modifica nada
php app/bin/migrate --status

# EXPORTAR - Solo LEE la BD y crea archivo SQL
php app/bin/db-schema export
php app/bin/db-schema export --with-data

# Ver ayuda
php app/bin/migrate --help
php app/bin/db-schema help
```

### ⚠️ Operaciones que MODIFICAN la BD

```bash
# IMPORTAR - Escribe en la BD
php app/bin/db-schema import

# EJECUTAR migraciones - Crea/modifica tablas
php app/bin/migrate

# REVERTIR - Elimina cambios
php app/bin/migrate --rollback

# RESET - ELIMINA TODO
php app/bin/migrate --reset

# SEEDERS - Inserta datos
php app/bin/seed
```

## 🎯 Protecciones Implementadas

### 1. El EXPORT nunca modifica datos

```bash
# SEGURO - Solo lee y crea archivo
php app/bin/db-schema export --with-data
```

**¿Qué hace?**
- ✅ Conecta a BD en modo LECTURA
- ✅ Lee estructura de tablas
- ✅ Lee datos (si usas --with-data)
- ✅ Escribe archivo SQL en `app/database/schema/`
- ❌ NUNCA modifica la BD
- ❌ NUNCA elimina datos

### 2. El IMPORT pide confirmación

Cuando ejecutas:
```bash
php app/bin/db-schema import --with-data
```

**El sistema pregunta:**
```
⚠️  ADVERTENCIA: Esto importará el schema a la base de datos actual.
Base de datos: produccion_db
Archivo: schema_with_seed.sql

¿Continuar? (yes/no): _
```

Si escribes **cualquier cosa que no sea "yes"**, se cancela.

### 3. Las migraciones NO tocan datos existentes

```php
// Agregar columna - NO afecta datos
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();  // ← NULLABLE = seguro
});

// Datos existentes quedan intactos:
// - name: "Juan"
// - email: "juan@email.com"
// - phone: NULL  ← nuevo campo, valor NULL
```

## 📋 Escenarios de Uso SEGUROS

### Escenario 1: Exportar BD de Producción

**Objetivo:** Guardar estructura actual

```bash
# En servidor de PRODUCCIÓN
php app/bin/db-schema export --with-data

# Te pregunta qué tablas ignorar:
> clientes,ordenes,pagos

# Archivo creado: app/database/schema/schema_with_seed.sql
```

**¿Afecta datos?** ❌ NO
**¿Puede fallar?** Solo si no hay conexión a BD

### Escenario 2: Importar en Servidor Nuevo (BD Vacía)

**Objetivo:** Crear estructura en servidor staging/nuevo

```bash
# En servidor NUEVO (BD vacía)
php app/bin/db-schema import --with-data

# Confirmación:
> yes
```

**¿Afecta datos?** ❌ NO (BD está vacía)
**Resultado:** BD nueva con estructura + admin

### Escenario 3: Crear Nueva Columna

**Objetivo:** Agregar campo "telefono" a usuarios

```bash
# En DESARROLLO
php app/bin/migrate make:migration add_telefono_to_users --table=users

# Editar migración:
$table->string('telefono', 20)->nullable();

# Ejecutar en DESARROLLO primero
php app/bin/migrate

# Probar, si funciona → commit
git add app/migrations/
git commit -m "Add telefono field"
git push

# En PRODUCCIÓN (después de probar)
git pull
php app/bin/migrate  # Solo agrega columna, datos intactos
```

**¿Afecta datos existentes?** ❌ NO
**Resultado:** Columna nueva con NULL en registros existentes

## ⚠️ Escenarios PELIGROSOS (y cómo evitarlos)

### ❌ PELIGRO 1: Importar en BD con Datos

```bash
# NUNCA hagas esto en producción con datos
php app/bin/db-schema import  # ← PUEDE CREAR CONFLICTOS
```

**¿Por qué es peligroso?**
- Si la tabla existe, puede fallar
- Podría duplicar datos
- Podría generar conflictos de claves

**Solución:** Solo usar import en BD VACÍA

### ❌ PELIGRO 2: Ejecutar migrate --reset en Producción

```bash
# ¡NUNCA EN PRODUCCIÓN!
php app/bin/migrate --reset  # ← ELIMINA TODO
```

**¿Qué hace?**
- Ejecuta todos los `down()` en orden inverso
- Elimina TODAS las tablas
- ¡PIERDES TODOS LOS DATOS!

**Solución:** Solo usar en desarrollo/local

### ❌ PELIGRO 3: Migración que Elimina Columna con Datos

```php
// PELIGROSO si la columna tiene datos importantes
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('email');  // ← ¡Se pierden los emails!
    });
}
```

**Solución:**
- Nunca eliminar columnas en producción sin backup
- Primero deprecar, después eliminar
- O migrar datos a otra columna primero

### ❌ PELIGRO 4: Migración sin down()

```php
public function down()
{
    // Vacío - no puedes revertir
}
```

**Problema:** Si algo sale mal, no puedes deshacer

**Solución:** Siempre implementar `down()`

## 🔐 Checklist de Seguridad

### Antes de Exportar (Producción)

- [ ] Tengo acceso de solo lectura? (Exportar no modifica)
- [ ] Identifiqué qué tablas ignorar (clientes, transacciones)
- [ ] Tengo espacio en disco para el archivo SQL

### Antes de Importar (Servidor Nuevo)

- [ ] ¿La BD está VACÍA? (crítico)
- [ ] ¿Configuré credenciales correctas?
- [ ] ¿Es el ambiente correcto? (dev/staging, NO producción)
- [ ] ¿Tengo backup de la BD? (por si acaso)

### Antes de Migrar (Cualquier servidor)

- [ ] ¿Probé la migración en desarrollo?
- [ ] ¿La migración tiene `down()` implementado?
- [ ] ¿Uso `nullable()` para nuevas columnas?
- [ ] ¿Tengo backup de la BD?
- [ ] ¿Revisé el código de la migración?

### Antes de Rollback

- [ ] ¿Sé qué cambia el `down()`?
- [ ] ¿Tengo backup?
- [ ] ¿Es necesario o puedo crear nueva migración?

## 🛡️ Protecciones Adicionales Recomendadas

### 1. Backup Automático

Crea script de backup antes de migrar:

```bash
#!/bin/bash
# backup-before-migrate.sh

echo "Creando backup..."
mysqldump -u user -p db_name > backup_$(date +%Y%m%d_%H%M%S).sql
echo "Backup creado"

echo "Ejecutando migraciones..."
php app/bin/migrate
```

### 2. Dry-run para Migraciones (Simulación)

Ver SQL sin ejecutar:

```bash
# Ver qué SQL se ejecutaría (característica futura)
php app/bin/migrate --pretend
```

### 3. Separar Ambientes

```bash
# .env.development
BD_NAME=proyecto_dev

# .env.staging
BD_NAME=proyecto_staging

# .env.production (NUNCA en Git)
BD_NAME=proyecto_prod
```

### 4. Permisos de BD Restringidos

Producción:
- Usuario app: SELECT, INSERT, UPDATE (NO DROP, NO TRUNCATE)
- Usuario migrations: Full (solo desde servidor seguro)

## 📊 Matriz de Riesgo

| Comando | BD Vacía | BD con Datos Dev | BD Producción |
|---------|----------|------------------|---------------|
| `export` | ✅ Seguro | ✅ Seguro | ✅ Seguro |
| `export --with-data` | ✅ Seguro | ✅ Seguro | ✅ Seguro |
| `import` | ✅ Seguro | ⚠️ Cuidado* | ❌ Peligroso |
| `import --with-data` | ✅ Seguro | ⚠️ Cuidado* | ❌ Peligroso |
| `migrate` | ✅ Seguro | ✅ Seguro | ⚠️ Con backup |
| `migrate --rollback` | ✅ Seguro | ⚠️ Cuidado | ⚠️ Con backup |
| `migrate --reset` | ✅ Seguro | ❌ Borra todo | ❌ NUNCA |
| `migrate --refresh` | ✅ Seguro | ❌ Borra todo | ❌ NUNCA |
| `seed` | ✅ Seguro | ⚠️ Duplicados? | ❌ Peligroso |

\* Puede causar conflictos si las tablas existen

## 🎓 Preguntas Frecuentes de Seguridad

**P: ¿Puedo perder datos al exportar?**

R: ❌ NO. Export solo LEE la BD y crea un archivo.

**P: ¿El import puede sobrescribir datos?**

R: Sí, si la tabla existe y tiene datos. Por eso SOLO usar en BD vacía.

**P: ¿Las migraciones eliminan datos?**

R: Solo si explícitamente usas `dropColumn()` o `drop()`. Las operaciones normales (agregar columnas, índices) NO eliminan datos.

**P: ¿Qué pasa si ejecuto migrate dos veces?**

R: Nada. El sistema detecta qué migraciones ya se ejecutaron y las salta.

**P: ¿Puedo deshacer una migración en producción?**

R: Técnicamente sí con `--rollback`, pero NO recomendado. Mejor crear nueva migración que corrija.

**P: ¿Cómo protejo producción?**

R:
1. ✅ Siempre hacer backup antes
2. ✅ Probar en staging primero
3. ✅ Usar usuario BD con permisos limitados
4. ✅ Nunca ejecutar --reset o --refresh
5. ✅ Revisar código de migraciones antes

## 🚨 Comandos PROHIBIDOS en Producción

```bash
# NUNCA ejecutar en producción:
php app/bin/migrate --reset     # Elimina TODO
php app/bin/migrate --refresh   # Elimina TODO y recrea
php app/bin/db-schema import    # BD ya tiene datos
```

## ✅ Workflow Seguro Completo

### Desarrollo Local

```bash
# 1. Crear migración
php app/bin/migrate make:migration add_new_field

# 2. Probar
php app/bin/migrate

# 3. Si falla
php app/bin/migrate --rollback
# Corregir y repetir

# 4. Cuando funciona
git commit
```

### Staging (Pruebas)

```bash
# 1. Actualizar código
git pull

# 2. BACKUP
mysqldump -u user -p db > backup.sql

# 3. Migrar
php app/bin/migrate

# 4. Probar app

# 5. Si falla, restaurar:
mysql -u user -p db < backup.sql
```

### Producción

```bash
# 1. BACKUP (CRÍTICO)
mysqldump -u user -p db_prod > backup_$(date +%Y%m%d).sql

# 2. Actualizar código
git pull

# 3. Ver qué se ejecutará
php app/bin/migrate --status

# 4. Ejecutar en horario de bajo tráfico
php app/bin/migrate

# 5. Verificar app funciona

# 6. Si hay problema, restaurar:
mysql -u user -p db_prod < backup_YYYYMMDD.sql
```

## 📝 Resumen

**El sistema es SEGURO si:**
1. ✅ Usas `export` solo para leer
2. ✅ Usas `import` solo en BD vacía
3. ✅ Pruebas migraciones en dev antes de prod
4. ✅ Haces backup antes de cambios en prod
5. ✅ Nunca ejecutas `--reset` o `--refresh` en prod

**Operaciones sin riesgo:**
- Exportar desde cualquier ambiente
- Ver status (`--status`)
- Crear archivos de migración (`make:migration`)

**Operaciones con riesgo controlado:**
- Migrar en producción (con backup)
- Rollback (con backup)

**Operaciones prohibidas en producción:**
- Import (BD no vacía)
- Reset/Refresh
- Seed (puede duplicar)

---

**Regla de oro:** Si tienes duda, haz backup primero.
