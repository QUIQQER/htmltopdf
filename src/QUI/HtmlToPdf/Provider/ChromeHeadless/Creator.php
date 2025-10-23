<?php

namespace QUI\HtmlToPdf\Provider\ChromeHeadless;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

use function preg_match_all;
use function preg_replace;
use function str_replace;
use function substr_replace;

class Creator implements HtmlToPdfCreatorInterface
{
    /**
     * @inheritDoc
     */
    public function createPdf(Document $document): string
    {
        try {
            // Get package var directory
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $varDir = $Package->getVarDir();
            
            // Build complete HTML document
            $html = $this->buildCompleteHtml($document);
            
            // Generate temporary HTML file
            $documentId = uniqid();
            $htmlFile = $varDir . $documentId . '.html';
            $pdfFile = $varDir . $documentId . '.pdf';
            
            file_put_contents($htmlFile, $html);
            
            // Create PDF using Chrome Headless
            $this->generatePdfWithChrome($htmlFile, $pdfFile, $document);
            
            // Clean up temporary HTML file
            if (file_exists($htmlFile)) {
                unlink($htmlFile);
            }
            
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
     * Build complete HTML document from header, content and footer
     *
     * @param Document $document
     * @return string
     */
    private function buildCompleteHtml(Document $document): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Document</title>';
        
        // Add CSS for print media
        // Note: Margins are handled by Chrome PDF options, not CSS @page
        $html .= '<style>
        @page {
            size: A4;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        @media print {
            .page-break {
                page-break-after: always;
            }
            
            .no-print {
                display: none;
            }
        }
        </style>';
        
        // Extract and add styles from content only (header/footer are handled separately)
        $contentHtml = $document->getContentHTML();
        $html .= $this->extractStyles($contentHtml);
        
        $html .= '</head>
<body>';
        
        // Add content section only (no header/footer - they're in PDF options)
        $html .= '<div class="pdf-content">';
        $html .= $this->extractBodyContent($contentHtml);
        
        // Add folding marks if enabled
        if ($document->getAttribute('foldingMarks')) {
            $html .= $this->getFoldingMarksHtml();
        }
        
        $html .= '</div>';
        
        $html .= '</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate PDF using Chrome Headless
     *
     * @param string $htmlFile
     * @param string $pdfFile
     * @param Document $document
     * @return void
     * @throws CommunicationException
     * @throws NoResponseAvailable
     */
    private function generatePdfWithChrome(string $htmlFile, string $pdfFile, Document $document): void
    {
        $browserFactory = new BrowserFactory();
        
        // Configure Chrome options
        $browser = $browserFactory->createBrowser([
            'headless' => true,
            'noSandbox' => true,
            'ignoreCertificateErrors' => true,
        ]);
        
        try {
            $page = $browser->createPage();
            
            // Navigate and wait for page to load
            $navigation = $page->navigate('file://' . $htmlFile);
            $navigation->waitForNavigation();
            
            // Build header and footer templates
            $headerTemplate = $this->buildHeaderTemplate($document);
            $footerTemplate = $this->buildFooterTemplate($document);

            preg_match_all('#\d*mm#i', $footerTemplate, $matches);

//            if (!empty($matches[0])) {
//                foreach ($matches[0] as $mmCssRuleValue) {
//                    $numericValueInMm = preg_replace('#[^\d]#i', '', $mmCssRuleValue);
//                    $numericValueInInches = $this->mmToInches((float)$numericValueInMm);
//                    $footerTemplate = str_replace($mmCssRuleValue, $numericValueInInches . 'in', $footerTemplate);
//                }
//            }

            // Configure PDF options based on document attributes
            // IMPORTANT: Margins are in INCHES, not mm! (1 inch = 25.4mm)
            $pdfOptions = [
                'landscape' => false,
                'printBackground' => true,
                'preferCSSPageSize' => true,  // Use CSS @page rules
                'displayHeaderFooter' => true,  // Enable Chrome's header/footer
                'headerTemplate' => $headerTemplate,
                'footerTemplate' => $footerTemplate,
                'scale' => (float)$document->getAttribute('zoom') ?: 1.0,
                
                // Convert margins from mm to inches
                'marginTop' => $this->mmToInches((float)$document->getAttribute('marginTop')),
                'marginRight' => $this->mmToInches((float)$document->getAttribute('marginRight')),
                'marginBottom' => $this->mmToInches((float)$document->getAttribute('marginBottom')),
                'marginLeft' => $this->mmToInches((float)$document->getAttribute('marginLeft')),
                
                // A4 paper size in inches
                'paperWidth' => 8.27,   // A4 width: 210mm = 8.27 inches
                'paperHeight' => 11.69, // A4 height: 297mm = 11.69 inches
            ];
            
            // Generate PDF
            $pdf = $page->pdf($pdfOptions);

            // Save to file
            $pdf->saveToFile($pdfFile);
        } finally {
            $browser->close();
        }
    }
    
    /**
     * Convert millimeters to inches
     *
     * @param float $mm
     * @return float
     */
    private function mmToInches(float $mm): float
    {
        return round($mm / 25.4, 4);
    }
    
    /**
     * Build header template for Chrome PDF
     *
     * @param Document $document
     * @return string
     */
    private function buildHeaderTemplate(Document $document): string
    {
        return $document->getHeaderHTML();
        $headerHtml = $document->getHeaderHTML();
        
        if (empty($headerHtml)) {
            return '<div></div>';  // Empty template required by Chrome
        }

//        return $headerHtml;
        
        // Extract styles and body content
        $styles = $this->extractStyles($headerHtml);
        $content = $this->extractBodyContent($headerHtml);
        
        // Add folding marks if enabled
        if ($document->getAttribute('foldingMarks')) {
            $content .= $this->getFoldingMarksHtml();
        }
        
        // Build complete header template
        return '<div style="width: 100%; font-size: 10px; padding: 0; margin: 0;">
            ' . $styles . '
            ' . $content . '
        </div>';
    }
    
    /**
     * Build footer template for Chrome PDF
     *
     * @param Document $document
     * @return string
     */
    private function buildFooterTemplate(Document $document): string
    {
        $footerHtml = $document->getFooterHTML();
        $showPageNumbers = $document->getAttribute('showPageNumbers');
        
        if (empty($footerHtml) && !$showPageNumbers) {
            return '<div></div>';  // Empty template required by Chrome
        }
        
        $content = '';
//
//        // Extract styles and body content from footer
//        if (!empty($footerHtml)) {
//            $styles = $this->extractStyles($footerHtml);
//            $content .= $styles;
//            $content .= $this->extractBodyContent($footerHtml);
//        }
        
        // Add page numbers if enabled
        if ($showPageNumbers) {
            $prefix = $document->getAttribute('pageNumbersPrefix');
            $pageNumbersHtml = '<div style="text-align: right; width: 100%;">
                <span>' . htmlspecialchars($prefix) . '</span>
                <span class="pageNumber"></span>
                <span> / </span>
                <span class="totalPages"></span>
            </div>';

            $footerHtml = str_replace('</body>', $pageNumbersHtml . '</body>', $footerHtml);
        }

        // Build complete footer template
        return $footerHtml;
    }
    
    /**
     * Extract CSS styles from HTML
     *
     * @param string $html
     * @return string
     */
    private function extractStyles(string $html): string
    {
        $styles = '';
        
        // Extract <style> tags
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches);
        foreach ($matches[1] as $style) {
            $styles .= '<style>' . $style . '</style>';
        }
        
        // Extract <link> tags for CSS
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $html, $linkMatches);
        foreach ($linkMatches[0] as $link) {
            $styles .= $link;
        }
        
        return $styles;
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
                    pointer-events: none;
                    z-index: 9999;
                }
                
                .folding-mark {
                    background: #000;
                    height: 1px;
                    left: 0;
                    position: absolute;
                    width: 40px;
                }
                
                .din-5008-f1 {
                    top: 105mm;
                }
                
                .din-5008-f2 {
                    top: 210mm;
                }
                
                .din-5008-hole {
                    top: 148.5mm;
                }
            </style>
        ';
    }
}
