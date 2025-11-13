<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\Exception;
use QUI\HtmlToPdf\Exception as HtmlToPdfException;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepository as HtmlToPdfCreatorProviderRepository;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepositoryInterface as HtmlToPdfCreatorProviderRepositoryInterface;
use QUI\HtmlToPdf\Provider\Image\ProviderRepository as PdfToImageConverterProviderRepository;
use QUI\HtmlToPdf\Provider\Image\ProviderRepositoryInterface as PdfToImageConverterProviderRepositoryInterface;

use function is_executable;

/**
 * Class Handler
 *
 * General handler for quiqqer/htmltopdf
 */
class Handler
{
    private ?PdfCreator $pdfCreator = null;
    private HtmlToPdfCreatorProviderRepositoryInterface $htmlToPdfCreatorProviderRepository;
    private PdfToImageConverterProviderRepositoryInterface $pdfToImageConverterProviderRepository;

    public function __construct(
        ?HtmlToPdfCreatorProviderRepositoryInterface $htmlToPdfCreatorProviderRepository = null,
        ?PdfToImageConverterProviderRepositoryInterface $pdfToImageConverterRepository = null
    ) {
        if (is_null($htmlToPdfCreatorProviderRepository)) {
            $htmlToPdfCreatorProviderRepository = new HtmlToPdfCreatorProviderRepository();
        }

        if (is_null($pdfToImageConverterRepository)) {
            $pdfToImageConverterRepository = new PdfToImageConverterProviderRepository();
        }

        $this->htmlToPdfCreatorProviderRepository = $htmlToPdfCreatorProviderRepository;
        $this->pdfToImageConverterProviderRepository = $pdfToImageConverterRepository;
    }

    /**
     * @throws QUI\Exception
     */
    public function getPdfCreator(): PdfCreator
    {
        if (!is_null($this->pdfCreator)) {
            return $this->pdfCreator;
        }

        $this->pdfCreator = new PdfCreator(
            $this->htmlToPdfCreatorProviderRepository->getCurrentProvider()->getHtmlToPdfCreator(),
            $this->pdfToImageConverterProviderRepository->getCurrentProvider()->getPdfToImageConverter()
        );
        return $this->pdfCreator;
    }

    /**
     * Get path to the ImageMagick `convert` command
     *
     * @return string
     * @throws Exception
     */
    public static function getConvertExecutablePath(): string
    {
        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (is_null($conf)) {
                throw new QUI\Exception("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $exception) {
            QUI\System\Log::writeException($exception);
            throw $exception;
        }

        $executablePath = $conf->get('settings', 'executable_convert');

        if (empty($executablePath)) {
            throw new QUI\Exception("No convert executable path set for quiqqer/htmltopdf.");
        }

        return trim($executablePath);
    }

    /**
     * Checks if the executable for ImageMagick`convert` is installed
     * and executable in the current PHP environment.
     *
     * @throws Exception
     */
    public static function checkConvertExecutable(): void
    {
        $executablePath = self::getConvertExecutablePath();

        if (empty($executablePath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorExecutable.convert.executable_not_found'
            ]);
        }

        if (!is_executable($executablePath)) {
            throw new HtmlToPdfException([
                'quiqqer/htmltopdf',
                'exception.Handler.checkPDFGeneratorExecutable.convert.executable_not_executable'
            ]);
        }
    }
}
