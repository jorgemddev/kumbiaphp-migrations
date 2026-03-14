<?php

/**
 * Script post-install/post-update de Composer.
 * Copia los binarios CLI a app/bin/ del proyecto KumbiaPHP.
 */

$projectRoot = dirname(__DIR__, 3); // vendor/jorgemddev/kumbiaphp-migrations → raíz del proyecto
$appBin      = $projectRoot . '/app/bin';
$packageBin  = __DIR__ . '/bin';

if (!is_dir($appBin)) {
    if (!mkdir($appBin, 0755, true)) {
        echo "[KumbiaMigrations] No se pudo crear el directorio app/bin/\n";
        exit(1);
    }
}

$files = ['migrate', 'seed'];

foreach ($files as $file) {
    $src  = $packageBin . '/' . $file;
    $dest = $appBin . '/' . $file;

    if (!file_exists($src)) {
        echo "[KumbiaMigrations] Archivo fuente no encontrado: {$src}\n";
        continue;
    }

    if (copy($src, $dest)) {
        chmod($dest, 0755);
        echo "[KumbiaMigrations] Publicado: app/bin/{$file}\n";
    } else {
        echo "[KumbiaMigrations] Error al copiar: app/bin/{$file}\n";
    }
}

echo "[KumbiaMigrations] Instalación completada.\n";
echo "[KumbiaMigrations] Ejecuta: php app/bin/migrate --install\n";
