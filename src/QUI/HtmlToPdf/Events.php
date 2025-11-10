<?php

/**
 * This file contains \QUI\HtmlToPdf\Events
 */

namespace QUI\HtmlToPdf;

use QUI;
use Smarty;

use function exec;
use function explode;
use function file_exists;
use function is_executable;
use function str_replace;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Events
{
    /**
     * Event: onPackageSetup
     *
     * @param QUI\Package\Package $Package
     * @return void
     *
     * @throws QUI\Exception
     */
    public static function onPackageSetup(QUI\Package\Package $Package): void
    {
        if ($Package->getName() !== 'quiqqer/htmltopdf') {
            return;
        }

//        self::setupConvertBinary();
    }

    /**
     * Set up the binary for "convert" (ImageMagick)
     *
     * @return void
     * @throws QUI\Exception
     */
    protected static function setupConvertBinary(): void
    {
        $binary = Handler::getConvertBinaryPath();

        if (!empty($binary)) {
            return;
        }

        try {
            $Conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        // Try to locate "wkhtmltopdf"
        exec("whereis convert", $output);

        if (!empty($output)) {
            $output = str_replace("convert: ", "", $output[0]);
            $binaries = explode(' ', $output);

            // Try all binaries and set the one that works
            foreach ($binaries as $binary) {
                if (file_exists($binary) && is_executable($binary)) {
                    $Conf->setValue('settings', 'binary_convert', $binary);
                    $Conf->save();
                    return;
                }
            }
        }

        // Try defaults
        $binary = "/usr/local/bin/convert";

        if (file_exists($binary) && is_executable($binary)) {
            $Conf->setValue('settings', 'binary_convert', $binary);
            $Conf->save();
            return;
        }

        $binary = "/usr/bin/convert";

        if (file_exists($binary) && is_executable($binary)) {
            $Conf->setValue('settings', 'binary_convert', $binary);
            $Conf->save();
        }
    }

    public static function onSmartyInit(Smarty $smarty): void
    {
        $smarty->registerPlugin(
            "function",
            "imageBase64",
            SmartyFunctions::imageBase64(...)
        );
    }
}
