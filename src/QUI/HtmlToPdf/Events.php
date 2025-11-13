<?php

namespace QUI\HtmlToPdf;

use QUI;
use Smarty;
use SmartyException;
use QUI\Package\Package;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Events
{
    public static function onPackageSetup(Package $package): void
    {

    }

    private static function migrateConfigFromV3(Package $package): void
    {
        try {
            $config = $package->getConfig();

            if (is_null($config)) {
                throw new QUI\Exception("Could not load config from {$package->getName()}.");
            }

            $convertExecutable = $config->get('')
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
