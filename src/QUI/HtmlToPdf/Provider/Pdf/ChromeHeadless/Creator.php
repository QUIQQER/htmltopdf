<?php

namespace QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use QUI;
use QUI\Exception;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;

use function preg_match_all;
use function str_replace;

class Creator implements HtmlToPdfCreatorInterface
{
    /**
     * @inheritDoc
     * @throws Exception
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
        if ($document->options->foldingMarks) {
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

            'headers' => [
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0'
            ]
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
            // Chrome's footer templates are rendered WITHIN the page margins
            $pdfOptions = [
                'landscape' => false,
                'printBackground' => true,
                'preferCSSPageSize' => true,  // Don't use CSS @page rules
                'displayHeaderFooter' => true,  // Enable Chrome's header/footer

                'headerTemplate' => $headerTemplate,
                'footerTemplate' => $footerTemplate,
//                'scale' => (float)$document->getAttribute('zoom') ?: 1.0,

                // Set margins to 0 for full-width footer
                'marginTop' => $this->mmToInches((float)$document->options->marginTop),
                'marginRight' => $this->mmToInches((float)$document->options->marginRight),
                'marginBottom' => $this->mmToInches((float)$document->options->marginBottom),
                'marginLeft' => $this->mmToInches((float)$document->options->marginLeft),

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
//        return $document->getHeaderHTML();
        $headerHtml = $document->getHeaderHTML();

        if (empty($headerHtml)) {
            return '<div></div>';  // Empty template required by Chrome
        }

//        return $headerHtml;

        // Extract styles and body content
//        $styles = $this->extractStyles($headerHtml);
//        $content = $this->extractBodyContent($headerHtml);
//        $content = QUI\HtmlToPdf\Provider\Utils::removeElementFromHtml($content, 'style');

        // Add folding marks if enabled
        if ($document->getAttribute('foldingMarks')) {
//            $content .= $this->getFoldingMarksHtml();
        }

        // Specific styling for footer wrapper
        // TODO: remove border
        $style = '<style>
             * {
                -webkit-print-color-adjust: exact;
             }
             
            .' . $document->options->cssClassHeader . '{
                position: absolute;
                width: 210mm;
                margin: 0;
                padding: 0;
                height: ' . $document->options->marginTop . 'mm;
                top: 0;
                left: 0;
            }
        </style>';

        return str_replace('</header>', $style . '</header>', $headerHtml);
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
        $showPageNumbers = $document->options->showPageNumbers;

        if (empty($footerHtml) && $showPageNumbers === false) {
            return '<div></div>';  // Empty template required by Chrome
        }

        // Add page numbers if enabled
        if ($showPageNumbers) {
            $pageNumbersHtml = '
            <div class="' . $document->options->cssClassPageNumbersContainer . '" style="text-align: right; width: 100%;">
                <span>' . htmlspecialchars($document->options->pageNumbersPrefix) . '</span>
                <span class="pageNumber"></span>
                <span> / </span>
                <span class="totalPages"></span>
            </div>';

            $footerHtml = str_replace('</footer>', $pageNumbersHtml . '</footer>', $footerHtml);
        }

        // Specific styling for footer wrapper
        $style = '<style>
             * {
                -webkit-print-color-adjust: exact;
             }

            .' . $document->options->cssClassFooter . '{
                position: absolute;
                width: 210mm;
                margin: 0;
                padding: 0;
                height: ' . $document->options->marginBottom . 'mm;
                bottom: 0;
                left: 0;
            }
        </style>';

        return str_replace('</head>', $style . '</head>', $footerHtml);
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
