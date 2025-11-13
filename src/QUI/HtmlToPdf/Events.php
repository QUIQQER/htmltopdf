<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\Package\Package;
use Smarty;
use SmartyException;

/**
 * Main event handlers for quiqqer/htmltopdf
 */
class Events
{
    /**
     * quiqqer/core: onPackageSetup
     */
    public static function onPackageSetup(Package $package): void
    {
        self::migrateConfigFromV3($package);
    }

    private static function migrateConfigFromV3(Package $package): void
    {
        try {
            $config = $package->getConfig();

            if (is_null($config)) {
                throw new QUI\Exception("Could not load config from {$package->getName()}.");
            }

            $convertExecutableOld = $config->get('settings', 'binary_convert');
            $convertExecutableNew = $config->get('image_magick', 'convert_executable');

            if (empty($convertExecutableOld) || !empty($convertExecutableNew)) {
                return;
            }

            $config->set('image_magick', 'convert_executable', $convertExecutableOld);
            $config->save();
        } catch (\Exception $exception) {
            QUI\System\Log::writeException($exception);
        }
    }

    /**
     * Register Smarty functions that are useful for HTML to PDF generation.
     *
     * @param Smarty $smarty
     * @return void
     * @throws SmartyException
     */
    public static function onSmartyInit(Smarty $smarty): void
    {
        $smarty->registerPlugin(
            "function",
            "imageBase64",
            SmartyFunctions::imageBase64(...)
        );
    }
}
