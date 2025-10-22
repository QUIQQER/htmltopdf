<?php

namespace QUI\HtmlToPdf\Provider\Mpdf;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

use function preg_replace_callback;
use function str_replace;

class Creator implements HtmlToPdfCreatorInterface
{
    private bool $initWriteHtml = true;

    /**
     * @inheritDoc
     */
    public function createPdf(Document $document): string
    {
        try {
            $this->initWriteHtml = true;

            // Get package var directory
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $varDir = $Package->getVarDir();
            
            // Create mpdf instance with document settings
            $mpdf = $this->createMpdfInstance($document);
            
            // Set header if content exists (must be BEFORE content for mPDF)
            if (!empty($document->getHeaderHTML())) {
                $this->setHeader($mpdf, $document);
            }
            
            // Set footer if content exists or page numbers are enabled
            if (!empty($document->getFooterHTML()) || $document->getAttribute('showPageNumbers')) {
                $this->setFooter($mpdf, $document);
            }
            
            // Write main content
            $this->writeContent($mpdf, $document);
            
            // Generate PDF file path
            $documentId = uniqid();
            $pdfFile = $varDir . $documentId . '.pdf';
            
            // Save PDF to file
            $mpdf->Output($pdfFile, Destination::FILE);

            // Fire event
            QUI::getEvents()->fireEvent('quiqqerHtmlToPDFCreated', [$document, $pdfFile]);
            
            return $pdfFile;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Exception([
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
            'margin_left' => (float)$document->getAttribute('marginLeft'),
            'margin_right' => (float)$document->getAttribute('marginRight'),
            'margin_top' => (float)$document->getAttribute('marginTop'),
            'margin_bottom' => (float)$document->getAttribute('marginBottom'),
            'margin_header' => (float)$document->getAttribute('headerSpacing'),
            'margin_footer' => (float)$document->getAttribute('footerSpacing'),
            'orientation' => 'P',
            'tempDir' => sys_get_temp_dir()
        ];
        
        // Apply DPI setting
        $dpi = (int)$document->getAttribute('dpi');
        if ($dpi > 0) {
            $config['dpi'] = $dpi;
        }

        $mpdf = new Mpdf($config);

        // Apply zoom factor
        $zoom = (float)$document->getAttribute('zoom');
        if ($zoom != 1) {
            $mpdf->SetDisplayMode('fullpage');
        }
        
        // Enable forms if requested
        if ($document->getAttribute('enableForms') === true) {
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
        $headerHtml = $this->extractBodyContent($fullHeaderHtml);
        
//        $headerHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $headerHtml);

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
        $fullFooterHtml = $document->getFooterHTML(false);
        
        // Extract CSS from <head> and <style> tags BEFORE extracting body content
        $fullFooterHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $fullFooterHtml);
        
        // Now extract body content (CSS is already processed)
        $footerHtml = $this->extractBodyContent($fullFooterHtml);
        
        // Add page numbers if enabled
        if ($document->getAttribute('showPageNumbers')) {
            $pageNumbersHtml = $this->getPageNumbersHtml($document);
            $footerHtml .= $pageNumbersHtml;
        }
        
        // Remove position:fixed and position:absolute from footer (not supported in mPDF footers)
        $footerHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $footerHtml);

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
        
        // Add folding marks as watermark if enabled (position:fixed works in body, not in header)
        if ($document->getAttribute('foldingMarks')) {
            $contentHtml = $this->getFoldingMarksHtml() . $contentHtml;
        }
        
        $mpdf->WriteHTML($this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $contentHtml), HTMLParserMode::HTML_BODY, $this->initWriteHtml);
    }
    
    /**
     * Extract body content from complete HTML document
     *
     * @param string $html
     * @return string
     */
    private function extractBodyContent(string $html): string
    {
        // Extract content between <body> tags if present
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
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
        $prefix = $document->getAttribute('pageNumbersPrefix');
        
        return '<div id="pages" style="text-align: right;">
                    <span id="pages_prefix">' . htmlspecialchars($prefix) . '</span>
                    <span id="pages_current">{PAGENO}</span>
                    <span> / </span>
                    <span id="pages_total">{nbpg}</span>
                </div>';
    }
    
    /**
     * Get HTML for folding marks (DIN-5008 standard)
     *
     * @return string
     */
    private function getFoldingMarksHtml(): string
    {
        return '
            <div class="folding-marks">
                <div class="folding-mark din-5008-f1"></div>
                <div class="folding-mark din-5008-f2"></div>
                <div class="folding-mark din-5008-hole"></div>
            </div>
            <style>
                .folding-marks {
                    height: 100%;
                    left: 0;
                    position: fixed;
                    top: 0;
                    width: 100%;
                }
                
                .folding-mark {
                    background: #000;
                    height: 1px;
                    left: 0;
                    position: absolute;
                    width: 40px;
                }
                
                .din-5008-f1 {
                    background: #000;
                    top: 105mm;
                }
                
                .din-5008-f2 {
                    background: #000;
                    top: 210mm;
                }
                
                .din-5008-hole {
                    top: 148.5mm;
                }
            </style>
        ';
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
        $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function($matches) use (&$styles) {
            $styles .= $matches[1];
            return '';
        }, $html);

        if (!empty($styles)) {
            $mpdf->WriteHTML($styles, HTMLParserMode::HEADER_CSS, $this->initWriteHtml, false);
            $this->initWriteHtml = false;
        }

        return $html;
    }
}
