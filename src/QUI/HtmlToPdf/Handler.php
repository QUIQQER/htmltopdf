<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\Exception;
use QUI\HtmlToPdf\Exception as HtmlToPdfException;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepository;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepositoryInterface;

use function is_executable;

/**
 * Class Handler
 *
 * General handler for quiqqer/htmltopdf
 */
class Handler
{
    private ?PdfCreator $pdfCreator = null;
    private ProviderRepositoryInterface $providerRepository;

    public function __construct(
        ?ProviderRepositoryInterface $providerRepository = null
    ) {
        if (is_null($providerRepository)) {
            $providerRepository = new ProviderRepository();
        }

        $this->providerRepository = $providerRepository;
    }

    /**
     * @throws QUI\Exception
     */
    public function getPdfCreator(): PdfCreator
    {
        if (!is_null($this->pdfCreator)) {
            return $this->pdfCreator;
        }

        $this->pdfCreator = new PdfCreator($this->providerRepository->getCurrentProvider()->getHtmlToPdfCreator());
        return $this->pdfCreator;
    }

    /**
     * Get path to the ImageMagick `convert` command
     *
     * @return string
     * @throws Exception
     */
    public static function getConvertBinaryPath(): string
    {
        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (is_null($conf)) {
                throw new QUI\Exception("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }

        $binaryPath = $conf->get('settings', 'binary_convert');

        if (empty($binaryPath)) {
            throw new QUI\Exception("No convert binary path set for quiqqer/htmltopdf.");
        }

        $binaryPath = trim($binaryPath);

        return empty($binaryPath) ? false : $binaryPath;
    }

    /**
     * Checks if the binary for ImageMagick`convert` is installed
     * and executable in the current PHP environment.
     *
     * @throws Exception
     */
    public static function checkConvertBinary(): void
    {
        $binaryPath = self::getConvertBinaryPath();

        if (empty($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.convert.binary_not_found'
            ]);
        }

        if (!is_executable($binaryPath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorBinary.convert.binary_not_executable'
            ]);
        }
    }
}
