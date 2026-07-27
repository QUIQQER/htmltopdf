<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepository as HtmlToPdfCreatorProviderRepository;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepositoryInterface as HtmlToPdfCreatorProviderRepositoryInterface;
use QUI\HtmlToPdf\Provider\Image\ProviderRepository as PdfToImageConverterProviderRepository;
use QUI\HtmlToPdf\Provider\Image\ProviderRepositoryInterface as PdfToImageConverterProviderRepositoryInterface;

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
            pdfToImageConverterProvider: $this->pdfToImageConverterProviderRepository->getCurrentProvider()
        );
        return $this->pdfCreator;
    }
}
