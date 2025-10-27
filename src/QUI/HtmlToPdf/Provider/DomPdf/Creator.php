<?php

namespace QUI\HtmlToPdf\Provider\DomPdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

use function file_put_contents;
use function str_replace;

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

            // Create Dompdf instance with options
            $dompdf = $this->createDompdfInstance($document);

            // Load HTML
            $dompdf->loadHtml($html);

            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Render the HTML as PDF
            $dompdf->render();

            // Add page numbers after rendering (Canvas API)
            if ($document->getAttribute('showPageNumbers')) {
                $this->addPageNumbers($dompdf, $document);
            }

            // Alternative: Add header/footer via Canvas API for guaranteed repetition
            // This is more reliable than position:fixed for complex layouts
            // Uncomment to use:
            // $this->addHeaderFooterViaCanvas($dompdf, $document);

            // Generate PDF file path
            $documentId = uniqid();
            $pdfFile = $varDir . $documentId . '.pdf';

            // Save PDF to file
            file_put_contents($pdfFile, $dompdf->output());

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
     * Create and configure Dompdf instance based on document attributes
     *
     * @param Document $document
     * @return Dompdf
     */
    private function createDompdfInstance(Document $document): Dompdf
    {
        $options = new Options();

        // Enable remote resources (for external stylesheets, images, etc.)
        $options->set('isRemoteEnabled', true);

        // Disable embedded PHP (deprecated and security risk)
        // We use Canvas API for page numbers instead
        $options->set('isPhpEnabled', false);

        // Enable HTML5 parser
        $options->set('isHtml5ParserEnabled', true);

        // Set default font
        $options->set('defaultFont', 'DejaVu Sans');

        // Enable font subsetting for smaller file sizes
        $options->set('isFontSubsettingEnabled', true);

        // Set DPI
        $dpi = (int)$document->getAttribute('dpi');
        if ($dpi > 0) {
//            $options->set('dpi', $dpi);
        }

        // Debug mode
        if ($document->getAttribute('debug') === true) {
            $options->set('debugKeepTemp', true);
            $options->set('debugCss', true);
            $options->set('debugLayout', true);
        }

        return new Dompdf($options);
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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>PDF Document</title>';

        // Add CSS for page setup and print media
        $marginTop = (float)$document->getAttribute('marginTop');
        $marginRight = (float)$document->getAttribute('marginRight');
        $marginBottom = (float)$document->getAttribute('marginBottom');
        $marginLeft = (float)$document->getAttribute('marginLeft');

        $headerSpacing = (float)$document->getAttribute('headerSpacing');
        $footerSpacing = (float)$document->getAttribute('footerSpacing');

        // Calculate header and footer heights (estimate based on spacing)
        $headerHeight = $headerSpacing > 0 ? $headerSpacing : 20;
        $footerHeight = $footerSpacing > 0 ? $footerSpacing : 20;

        $html .= '<style>
        @page {
            margin-top: ' . $marginTop . 'mm;
            margin-right: ' . $marginRight . 'mm;
            margin-bottom: ' . ($marginBottom + $footerHeight) . 'mm;
            margin-left: ' . $marginLeft . 'mm;
            width: 210mm;
        }
        
        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Header positioning - Fixed at top of every page */
        /* Method: position:fixed with negative margin outside content area */
        /* See: https://github.com/dompdf/dompdf/wiki/Usage#canvas */
        header.pdf-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: ' . $headerHeight . 'mm;
            margin-top: -' . $marginTop . 'mm;
            margin-left: ' . $marginLeft . 'mm;
            margin-right: ' . $marginRight . 'mm;
            width: 210mm;
        }
        
        /* Footer positioning - Fixed at bottom of every page */
        /* Note: For complex footers, consider using Canvas callbacks instead */
        footer.pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: ' . $footerHeight . 'mm;
            margin-bottom: -' . ($marginBottom + $footerHeight) . 'mm;
            margin-left: ' . $marginLeft . 'mm;
            margin-right: ' . $marginRight . 'mm;
        }
        
        /* Content area */
        .pdf-content {
            position: relative;
            margin-top: ' . $headerHeight . 'mm;
            width: 100%;
        }
        </style>';

        // Extract and add styles from header
        $headerHtml = $document->getHeaderHTML();
        if (!empty($headerHtml)) {
            $headerStyles = $this->extractStyles($headerHtml);
            $html .= $headerStyles;
            $headerHtml = QUI\HtmlToPdf\Provider\Utils::removeElementFromHtml($headerHtml, 'style');
        }

        // Extract and add styles from content
        $contentHtml = $document->getContentHTML();
        $contentStyles = $this->extractStyles($contentHtml);
        $html .= $contentStyles;
        $contentHtml = str_replace($contentStyles, '', $contentHtml);

        // Extract and add styles from footer
        $footerHtml = $document->getFooterHTML(false);
        if (!empty($footerHtml)) {
            $html .= $this->extractStyles($footerHtml);
        }

        $html .= '</head>
<body>';

        // Add header section - Using <header> tag for DomPDF
        if (!empty($headerHtml)) {
            $html .= '<header class="pdf-header" style="border: 4px solid purple;">';
            $html .= $this->extractBodyContent($headerHtml);
            $html .= '</header>';
        }

        // Add content section
        $html .= '<div class="pdf-content" style="border: 2px solid red;">';
        $html .= $this->extractBodyContent($contentHtml);

        // Add folding marks if enabled
        if ($document->getAttribute('foldingMarks')) {
//            $html .= $this->getFoldingMarksHtml();
        }

        $html .= '</div>';

        // Add footer section - Using <footer> tag for DomPDF
        if (!empty($footerHtml)) {
//            $html .= '<footer class="pdf-footer">';
//            $html .= QUI\HtmlToPdf\Provider\Utils::extractContentFromHtmlElement($footerHtml, 'footer');
////            $html .= $this->extractBodyContent($footerHtml);
//            $html .= '</footer>';
        }

        $html .= '</body>
</html>';

        file_put_contents("/home/peat/test_pdf.html", $html);

        return $html;
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
     * Add page numbers to PDF using Canvas API (modern approach)
     *
     * Uses DomPDF's Canvas API instead of deprecated embedded PHP.
     * See: https://github.com/dompdf/dompdf/wiki/Usage#canvas
     *
     * @param Dompdf $dompdf
     * @param Document $document
     * @return void
     */
    private function addPageNumbers(Dompdf $dompdf, Document $document): void
    {
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $prefix = $document->getAttribute('pageNumbersPrefix');

        // Use page_text() to add page numbers on every page
        // This is the official recommended method per DomPDF documentation
        // Placeholders {PAGE_NUM} and {PAGE_COUNT} are automatically replaced
        $canvas->page_text(
            $canvas->get_width() - 100,  // X position (from right)
            $canvas->get_height() - 30,   // Y position (from bottom)
            $prefix . ' {PAGE_NUM} / {PAGE_COUNT}',  // Text with placeholders
            $fontMetrics->getFont('DejaVu Sans'),
            10,  // Font size
            [0, 0, 0]  // Color (black)
        );

        // Alternative: Use page_script() callback for more complex layouts
        // $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($prefix) {
        //     $text = $prefix . ' ' . $pageNumber . ' / ' . $pageCount;
        //     $font = $fontMetrics->getFont('DejaVu Sans');
        //     $size = 10;
        //     $width = $fontMetrics->getTextWidth($text, $font, $size);
        //     $canvas->text($canvas->get_width() - $width - 20, $canvas->get_height() - 30, $text, $font, $size);
        // });
    }

    /**
     * Alternative: Add header/footer via Canvas API (most reliable method)
     *
     * This method uses Canvas callbacks to render header/footer on every page.
     * More reliable than position:fixed for complex layouts.
     * See: https://github.com/dompdf/dompdf/wiki/Usage#callbacks
     *
     * @param Dompdf $dompdf
     * @param Document $document
     * @return void
     */
    private function addHeaderFooterViaCanvas(Dompdf $dompdf, Document $document): void
    {
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();

        // Use page_script callback to render on every page
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($document) {
            $font = $fontMetrics->getFont('DejaVu Sans');
            $size = 10;
            $color = [0, 0, 0];

            // Get dimensions
            $pageWidth = $canvas->get_width();
            $pageHeight = $canvas->get_height();

            // --- HEADER ---
            // Example: Add a line at top
            $canvas->line(
                20, 40,  // x1, y1
                $pageWidth - 20, 40,  // x2, y2
                $color, 1
            );

            // Example: Add header text
            $headerText = "Company Name - Document Header";
            $canvas->text(20, 25, $headerText, $font, $size, $color);

            // --- FOOTER ---
            // Example: Add a line at bottom
            $canvas->line(
                20, $pageHeight - 40,
                $pageWidth - 20, $pageHeight - 40,
                $color, 1
            );

            // Example: Add footer text (left side)
            $footerText = "Generated by QUIQQER";
            $canvas->text(20, $pageHeight - 25, $footerText, $font, $size, $color);

            // Page numbers (right side)
            if ($document->getAttribute('showPageNumbers')) {
                $prefix = $document->getAttribute('pageNumbersPrefix');
                $pageText = $prefix . ' ' . $pageNumber . ' / ' . $pageCount;
                $textWidth = $fontMetrics->getTextWidth($pageText, $font, $size);
                $canvas->text(
                    $pageWidth - $textWidth - 20,
                    $pageHeight - 25,
                    $pageText,
                    $font,
                    $size,
                    $color
                );
            }
        });
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
