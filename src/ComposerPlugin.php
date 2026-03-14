<?php

namespace JorgeMdDev\KumbiaMigrations;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io) {}
    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'publishBinaries',
            ScriptEvents::POST_UPDATE_CMD  => 'publishBinaries',
        ];
    }

    public static function publishBinaries(Event $event)
    {
        $io          = $event->getIO();
        $vendorDir   = $event->getComposer()->getConfig()->get('vendor-dir');
        $projectRoot = dirname($vendorDir);
        $appBin      = $projectRoot . '/app/bin';
        $packageBin  = $vendorDir . '/jorgemddev/kumbiaphp-migrations/bin';

        if (!is_dir($packageBin)) {
            return;
        }

        if (!is_dir($appBin) && !mkdir($appBin, 0755, true)) {
            $io->writeError('<error>[KumbiaMigrations] No se pudo crear app/bin/</error>');
            return;
        }

        foreach (['migrate', 'seed'] as $file) {
            $src  = $packageBin . '/' . $file;
            $dest = $appBin . '/' . $file;

            if (!file_exists($src)) {
                continue;
            }

            if (copy($src, $dest)) {
                chmod($dest, 0755);
                $io->write("<info>[KumbiaMigrations] Publicado: app/bin/{$file}</info>");
            } else {
                $io->writeError("<error>[KumbiaMigrations] Error al copiar: app/bin/{$file}</error>");
            }
        }

        $io->write('<info>[KumbiaMigrations] Listo. Ejecuta: php app/bin/migrate --install</info>');
    }
}
