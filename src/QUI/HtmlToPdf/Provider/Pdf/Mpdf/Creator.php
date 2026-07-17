<?php

namespace QUI\HtmlToPdf\Provider\Pdf\Mpdf;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfCreationFailedException;

use function preg_replace_callback;
use function str_replace;

class Creator implements HtmlToPdfCreatorInterface
{
    private bool $initWriteHtml = true;
    private string $styles = '';
    private string $body = '';

    /**
     * @inheritDoc
     * @throws HtmlToPdfCreationFailedException
     */
    public function createPdf(Document $document): string
    {
        try {
            $this->initWriteHtml = true;
            $document->setAttribute('data-renderer', 'mpdf');

            // Get package var directory
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $varDir = $Package->getVarDir();

            // Create mpdf instance with document settings
            $mpdf = $this->createMpdfInstance($document);
            $mpdf->shrink_tables_to_fit = '1'; // see https://mpdf.github.io/troubleshooting/resizing.html [13.11.2025]
            $mpdf->shrink_this_table_to_fit = false;

            // Set folding marks as watermark if enabled (appears on every page)
            if ($document->options->foldingMarks) {
                $this->setFoldingMarks($mpdf);
            }

            // Set header if content exists (must be BEFORE content for mPDF)
            if (!empty($document->getHeaderHTML())) {
                $this->setHeader($mpdf, $document);
            }

            // Set footer if content exists or page numbers are enabled
            if (!empty($document->getFooterHTML()) || $document->options->showPageNumbers) {
                $this->setFooter($mpdf, $document);
            }

            // Write main content
            $this->writeContent($mpdf, $document);

            // Generate PDF file path
            $documentId = uniqid();
            $pdfFile = $varDir . $documentId . '.pdf';

            // Save PDF to file
            $mpdf->Output($pdfFile, Destination::FILE);

            return $pdfFile;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new HtmlToPdfCreationFailedException([
                'quiqqer/htmltopdf',
                'exception.document.pdf.conversion.failed'
            ]);
        }
    }

    /**
     * Create and configure mPDF instance based on document attributes
     *
     * @param Document $document
     * @return Mpdf
     * @throws MpdfException
     */
    private function createMpdfInstance(Document $document): Mpdf
    {
        $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => (float)$document->options->marginLeft,
            'margin_right' => (float)$document->options->marginRight,
            'margin_top' => (float)$document->options->marginTop,
            'margin_bottom' => (float)$document->options->marginBottom,
//            'margin_footer' => (float)$document->options->marginBottom,
            'margin_header' => 0, //(float)$document->options->headerSpacing,
            'margin_footer' => 0, //(float)$document->options->footerSpacing,
            'orientation' => 'P',
            'tempDir' => sys_get_temp_dir()
        ];

        // Apply DPI setting
//        $dpi = (int)$document->options->dpi;
//        if ($dpi > 0) {
//            $config['dpi'] = $dpi;
//        }

        $mpdf = new Mpdf($config);

        // Apply zoom factor
//        $zoom = (float)$document->options->zoom;
//        if ($zoom != 1) {
//            $mpdf->SetDisplayMode('fullpage');
//        }

        // Enable forms if requested
        if ($document->options->enableForms === true) {
            $mpdf->useActiveForms = true;
        }

        return $mpdf;
    }

    /**
     * Set header HTML in mPDF
     *
     * @param Mpdf $mpdf
     * @param Document $document
     * @return void
     * @throws MpdfException
     */
    private function setHeader(Mpdf $mpdf, Document $document): void
    {
        $fullHeaderHtml = $document->getHeaderHTML();

        // Extract CSS from <head> and <style> tags BEFORE extracting body content
        $fullHeaderHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $fullHeaderHtml);

        // Now extract body content (CSS is already processed)
        $headerHtml = $this->extractElementContent($fullHeaderHtml);
        $headerHtml = '<div>' . $headerHtml . '</div>';
        $this->body .= $headerHtml;

        $mpdf->SetHTMLHeader($headerHtml);
    }

    /**
     * Set footer HTML in mPDF with optional page numbers
     *
     * @param Mpdf $mpdf
     * @param Document $document
     * @return void
     * @throws MpdfException
     */
    private function setFooter(Mpdf $mpdf, Document $document): void
    {
        $fullFooterHtml = $document->getFooterHTML();

        // Extract CSS from <head> and <style> tags BEFORE extracting body content
        $fullFooterHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $fullFooterHtml);

        // Now extract body content (CSS is already processed)
        $footerHtml = $this->extractElementContent($fullFooterHtml);

        // Add page numbers if enabled
        if ($document->options->showPageNumbers) {
            $pageNumbersHtml = $this->getPageNumbersHtml($document);
            $footerHtml = str_replace('</footer>', $pageNumbersHtml . '</footer>', $footerHtml);
        }

        // Remove position:fixed and position:absolute from footer (not supported in mPDF footers)
        $footerHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $footerHtml);
        $this->body .= $footerHtml;

        // Specific styling for footer wrapper
        $mpdf->WriteHTML(
            ' 
             <style>
                .' . $document->options->cssClassFooterContainer . '{
                    position: absolute;
                    width: 210mm;
                    margin: 0;
                    padding: 0;
                    height: ' . $document->options->marginBottom . 'mm;
                    bottom: 0;
                    left: 0;
                }
            </style>
            ',
            HTMLParserMode::HEADER_CSS,
            $this->initWriteHtml,
            false
        );

        $mpdf->SetHTMLFooter($footerHtml);
    }

    /**
     * Write main content to mPDF
     *
     * @param Mpdf $mpdf
     * @param Document $document
     * @return void
     * @throws MpdfException
     */
    private function writeContent(Mpdf $mpdf, Document $document): void
    {
        $contentHtml = $document->getContentHTML();
        $contentHtml = $this->extractElementContent($contentHtml);

        $contentHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $contentHtml);
        $this->body .= $contentHtml;

        $mpdf->WriteHTML($contentHtml, HTMLParserMode::HTML_BODY, $this->initWriteHtml);
    }

    /**
     * Extract body content from complete HTML document
     *
     * @param string $html
     * @param string $element
     * @return string
     */
    private function extractElementContent(string $html, string $element = 'body'): string
    {
        // Extract content between <body> tags if present
        if (preg_match('/<' . $element . '[^>]*>(.*?)<\/' . $element . '>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    /**
     * Get HTML for page numbers display
     *
     * @param Document $document
     * @return string
     */
    private function getPageNumbersHtml(Document $document): string
    {
        $prefix = $document->options->pageNumbersPrefix;

        return '<div class="' . $document->options->cssClassPageNumbersContainer . '">
                    <span id="pages_prefix">' . htmlspecialchars($prefix) . '</span>
                    <span id="pages_current">{PAGENO}</span>
                    <span> / </span>
                    <span id="pages_total">{nbpg}</span>
                </div>';
    }

    /**
     * Set folding marks (DIN-5008 standard; Form type B) as watermark
     * Watermarks automatically appear on every page
     *
     * @param Mpdf $mpdf
     * @return void
     * @throws QUI\Exception
     */
    private function setFoldingMarks(Mpdf $mpdf): void
    {
        $Package = QUI::getPackage('quiqqer/htmltopdf');
        $foldingMarksPath = $Package->getDir() . 'src/QUI/HtmlToPdf/Provider/Pdf/Mpdf/bin/folding-marks-din5008.svg';

        if (!file_exists($foldingMarksPath)) {
            QUI\System\Log::addWarning('Folding marks file not found: ' . $foldingMarksPath);
            return;
        }

        // Set watermark image with full opacity (folding marks should be clearly visible)
        // 'P' = Resize to full physical page size (not just print area), keeping aspect ratio
        // [0, 0] = Position at top-left of physical page (x=0mm, y=0mm)
        $mpdf->SetWatermarkImage(
            $foldingMarksPath,
            1.0,   // Full opacity (1.0 = completely opaque, 0 = transparent)
            'P',   // 'P' = full Page size (physical page, not print area)
            [0, 0] // Position: top-left corner of physical page
        );
        $mpdf->showWatermarkImage = true;
    }

    /**
     * @param Mpdf $mpdf
     * @param string $html
     * @return string
     * @throws MpdfException
     */
    private function extractAndRemoveStyleElementsAndAppendToMpdf(Mpdf $mpdf, string $html): string
    {
        $styles = '';
        $htmlReplaced = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function ($matches) use (&$styles) {
            $styles .= $matches[1];
            return '';
        }, $html);

        if (!is_string($htmlReplaced)) {
            return $html;
        }

        if (!empty($styles)) {
            $mpdf->WriteHTML($styles, HTMLParserMode::HEADER_CSS, $this->initWriteHtml, false);
            $this->styles .= $styles;
            $this->initWriteHtml = false;
        }

        return $htmlReplaced;
    }
}
