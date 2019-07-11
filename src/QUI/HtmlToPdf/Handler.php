<?php

namespace QUI\HtmlToPdf;

use QUI\HtmlToPdf\Exception as HtmlToPdfException;

/**
 * Class Handler
 *
 * General handler for quiqqer/htmltopdf
 */
class Handler
{
    const PDF_GENERATOR_BINARY_REQUIRED_VERSION = '0.12.5';

    /**
     * Get path to the PDF generator binary
     *
     * @return string
     */
    public static function getPDFGeneratorBinaryPath()
    {
        $binaryPath = `which wkhtmltopdf 2> /dev/null`;
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
                'exception.Handler.checkPDFGeneratorBinary.binary_not_executable'
            ]);
        }

        $binaryVersion = explode(' ', `$binaryPath -V`);
        $versionParts  = explode('.', $binaryVersion[1]);

        if ((int)$versionParts[0] > 0) {
            return;
        }

        if ((int)$versionParts[1] > 12) {
            return;
        }

        if ((int)$versionParts[2] >= 5) {
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
}
