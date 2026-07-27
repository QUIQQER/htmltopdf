<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\Exception;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterInterface;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\Utils\System\File;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageConversionFailedException;

use function date;
use function unlink;

readonly class PdfCreator
{
    /**
     * @param HtmlToPdfCreatorInterface $pdfCreator
     * @param PdfToImageConverterInterface|null $pdfToImageConverter (optional) - Only required if PDF to image
     * conversion shall be available.
     * @param PdfToImageConverterProviderInterface|null $pdfToImageConverterProvider (optional) - Lazily creates the
     * PDF-to-image converter when image conversion is requested.
     */
    public function __construct(
        private HtmlToPdfCreatorInterface $pdfCreator,
        private ?PdfToImageConverterInterface $pdfToImageConverter = null,
        private ?PdfToImageConverterProviderInterface $pdfToImageConverterProvider = null
    ) {
    }

    public function createPdf(Document $document): string
    {
        $pdfFilePath = $this->pdfCreator->createPdf($document);

        try {
            QUI::getEvents()->fireEvent('quiqqerHtmlToPDFCreated', [$document, $pdfFilePath]);
        } catch (\Exception $exception) {
            QUI\System\Log::writeException(
                $exception,
                QUI\System\Log::LEVEL_ERROR,
                [
                    'document' => $document,
                    'pdfFilePath' => $pdfFilePath,
                    'event' => 'quiqqerHtmlToPDFCreated'
                ]
            );
        }

        return $pdfFilePath;
    }

    /**
     * @return string|null - Pdf file path if $keepPdfFile is true; null otherwise
     * @throws Exception
     */
    public function createAndDownloadPdf(Document $document, bool $keepPdfFile = false): ?string
    {
        $pdfFile = $this->createPDF($document);
        $filename = $document->options->filename;

        if (empty($filename)) {
            $filename = $document->documentId . '_' . date("d_m_Y__H_m") . '.pdf';
        }
        try {
            File::send($pdfFile, 0, $filename);
        } catch (\Throwable $exception) {
            QUI\System\Log::writeException($exception);

            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.pdf.download.failed'
            ]);
        }

        if ($keepPdfFile === false) {
            unlink($pdfFile);
            return null;
        }

        return $pdfFile;
    }

    /**
     * @param Document $document
     * @return array<string> - Generated image files (file paths)
     * @throws PdfToImageConversionFailedException
     */
    public function createPdfAndConvertToImage(Document $document): array
    {
        $pdfToImageConverter = $this->pdfToImageConverter;

        if ($pdfToImageConverter === null && $this->pdfToImageConverterProvider !== null) {
            $pdfToImageConverter = $this->pdfToImageConverterProvider->getPdfToImageConverter();
        }

        if ($pdfToImageConverter === null) {
            throw new PdfToImageConversionFailedException([
                'quiqqer/htmltopdf',
                'exception.PdfCreator.createPdfAndConvertToImage.no_image_converter_set_up'
            ]);
        }

        $pdfFilePath = $this->createPdf($document);
        $images = $pdfToImageConverter->convertPdfToImage($pdfFilePath);

        unlink($pdfFilePath);
        return $images;
    }
}
