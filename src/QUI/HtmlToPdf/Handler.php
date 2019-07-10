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
    /**
     * Checks if the binary for generating PDF files from HTML is installed
     * and executable in the current PHP environment.
     *
     * @throws \QUI\HtmlToPdf\Exception
     */
    public static function checkPDFGeneratorBinary()
    {
        $binaryPath = `which wkhtmltopdf 2> /dev/null`;
        $binaryPath = trim($binaryPath);

        if (empty($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.binary_not_found'
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
                'requiredVersion'  => '0.12.5'
            ]
        ]);
    }
}
