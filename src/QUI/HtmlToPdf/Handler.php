<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\HtmlToPdf\Exception as HtmlToPdfException;

/**
 * Class Handler
 *
 * General handler for quiqqer/htmltopdf
 */
class Handler
{
    const PDF_GENERATOR_BINARY_REQUIRED_VERSION = '0.12.4 (with patched qt)';

    /**
     * Additional wkhtmltopdf CLI parameters based on version
     *
     * @var array
     */
    public static $cliParams = [];

    /**
     * Get path to the PDF generator binary
     *
     * @return string
     */
    public static function getPDFGeneratorBinaryPath()
    {
        try {
            $Conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }

        $binaryPath = $Conf->get('settings', 'binary');
        $binaryPath = trim($binaryPath);

        return empty($binaryPath) ? false : $binaryPath;
    }

    /**
     * Checks if the binary for generating PDF files from HTML is installed
     * and executable in the current PHP environment.
     *
     * @throws \QUI\HtmlToPdf\Exception
     */
    public static function checkPDFGeneratorBinary()
    {
        $binaryPath = self::getPDFGeneratorBinaryPath();

        if (empty($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.binary_not_found',
                [
                    'requiredVersion' => self::PDF_GENERATOR_BINARY_REQUIRED_VERSION
                ]
            ]);
        }

        if (!\is_executable($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.binary_not_executable',
                [
                    'path' => $binaryPath
                ]
            ]);
        }

        $binaryVersion = explode(' ', `$binaryPath -V`);
        $versionParts  = explode('.', $binaryVersion[1]);

        if (isset($versionParts[0]) && (int)$versionParts[0] > 0) {
            return;
        }

        if (isset($versionParts[1]) && (int)$versionParts[1] > 12) {
            return;
        }

        if (isset($versionParts[2]) && (int)$versionParts[2] >= 4) {
            // --enable-local-file-access is required since version 0.12.6
            // see also: https://stackoverflow.com/q/62315246
            if ((int)$versionParts[2] >= 6) {
                self::$cliParams[] = '--enable-local-file-access';
            }

            return;
        }

        if (!empty($binaryVersion[2]) && $binaryVersion[2] === '(with'
            && !empty($binaryVersion[3]) && $binaryVersion[2] === 'patched'
            && !empty($binaryVersion[4]) && $binaryVersion[2] === 'qt)') {
            return;
        }

        throw new HtmlToPdfException([
            'quiqqer/htmltopdf',
            'exception.Handler.checkPDFGeneratorBinary.binary_wrong_version',
            [
                'installedVersion' => $binaryVersion[1],
                'requiredVersion'  => self::PDF_GENERATOR_BINARY_REQUIRED_VERSION
            ]
        ]);
    }

    /**
     * Send e-mail about wrong/missing wkhtmltopdf binary to admin
     *
     * @param string $error - Error text
     * @return void
     */
    public static function sendBinaryWarningMail(string $error)
    {
        $Mailer    = new QUI\Mail\Mailer();
        $adminMail = QUI::conf('mail', 'admin_mail');

        if (empty($adminMail)) {
            return;
        }

        $Mailer->addRecipient($adminMail);
        $Mailer->setSubject(
            QUI::getLocale()->get(
                'quiqqer/htmltopdf',
                'mail.warning.binary_missing.subject',
                [
                    'host' => QUI::conf('globals', 'host')
                ]
            )
        );
        $Mailer->setBody(
            QUI::getLocale()->get(
                'quiqqer/htmltopdf',
                'mail.warning.binary_missing.body',
                [
                    'host'  => QUI::conf('globals', 'host'),
                    'error' => $error
                ]
            )
        );

        try {
            $Mailer->send();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Get path to the ImageMagick `convert` command
     *
     * @return string
     */
    public static function getConvertBinaryPath()
    {
        try {
            $Conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }

        $binaryPath = $Conf->get('settings', 'binary_convert');
        $binaryPath = trim($binaryPath);

        return empty($binaryPath) ? false : $binaryPath;
    }

    /**
     * Checks if the binary for ImageMagick`convert` is installed
     * and executable in the current PHP environment.
     *
     * @throws \QUI\HtmlToPdf\Exception
     */
    public static function checkConvertBinary()
    {
        $binaryPath = self::getConvertBinaryPath();

        if (empty($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.convert.binary_not_found'
            ]);
        }

        if (!\is_executable($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.convert.binary_not_executable'
            ]);
        }
    }
}
