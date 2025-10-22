<?php

namespace QUI\HtmlToPdf;

use chillerlan\QRCode\QRCodeException;
use QUI;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;
use QUI\Utils\System\File;

use function date;
use function file_exists;
use function unlink;

class PdfCreator
{
    public function __construct(
        private HtmlToPdfCreatorInterface $creator
    ) {
    }

    public function createPdf(Document $document): string
    {
        return $this->creator->createPdf($document);
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
