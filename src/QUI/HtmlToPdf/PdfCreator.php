<?php

namespace QUI\HtmlToPdf;

use QUI;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;
use QUI\Utils\System\File;

use function date;
use function unlink;

class PdfCreator
{
    public function __construct(
        private HtmlToPdfCreatorInterface $creator
    ) {
    }

    public function createPdf(Document $document): string
    {
        $pdfFilePath = $this->creator->createPdf($document);

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

    public function createAndDownloadPdf(Document $document, bool $keepPdfFile = false): void
    {
        $pdfFile = $this->createPDF($document);
        $filename = $document->getAttribute('filename');

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
        }
    }
}
