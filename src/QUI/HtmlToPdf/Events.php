<?php
/**
 * This file contains \QUI\HtmlToPdf\Events
 */

namespace QUI\HtmlToPdf;

use QUI;

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
    public static function onPackageSetup(QUI\Package\Package $Package)
    {
        if ($Package->getName() !== 'quiqqer/htmltopdf') {
            return;
        }

        $binary = Handler::getPDFGeneratorBinaryPath();

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
        \exec("whereis wkhtmltopdf", $output);

        if (!empty($output)) {
            $binary = \str_replace("wkhtmltopdf: ", "", $output[0]);
            $Conf->setValue('settings', 'binary', $binary);
            $Conf->save();

            return;
        }

        // Try default
        $binary = "/usr/local/bin/wkhtmltopdf";

        if (\file_exists($binary) && \is_executable($binary)) {
            $Conf->setValue('settings', 'binary', $binary);
            $Conf->save();
            return;
        }

        $binary = "/usr/bin/wkhtmltopdf";

        if (\file_exists($binary) && \is_executable($binary)) {
            $Conf->setValue('settings', 'binary', $binary);
            $Conf->save();
        }
    }
}
