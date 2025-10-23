<?php

namespace QUI\HtmlToPdf\Provider\DomPdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

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
        
        // Enable PHP functions in CSS (for dynamic page numbers)
        // Note: This is safe as we control the HTML content
        $options->set('isPhpEnabled', true);
        
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
            margin-top: ' . ($marginTop + $headerHeight) . 'mm;
            margin-right: ' . $marginRight . 'mm;
            margin-bottom: ' . ($marginBottom + $footerHeight) . 'mm;
            margin-left: ' . $marginLeft . 'mm;
            width: 110mm;
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
        header.pdf-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: ' . $headerHeight . 'mm;
            margin-top: -' . ($marginTop + $headerHeight) . 'mm;
            margin-left: ' . $marginLeft . 'mm;
            margin-right: ' . $marginRight . 'mm;
        }
        
        /* Footer positioning - Fixed at bottom of every page */
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
        }
        </style>';
        
        // Extract and add styles from header
        $headerHtml = $document->getHeaderHTML();
        if (!empty($headerHtml)) {
            $html .= $this->extractStyles($headerHtml);
        }
        
        // Extract and add styles from content
        $contentHtml = $document->getContentHTML();
        $html .= $this->extractStyles($contentHtml);
        
        // Extract and add styles from footer
        $footerHtml = $document->getFooterHTML(false);
        if (!empty($footerHtml)) {
            $html .= $this->extractStyles($footerHtml);
        }
        
        $html .= '</head>
<body>';
        
        // Add header section - Using <header> tag for DomPDF
        if (!empty($headerHtml)) {
            $html .= '<header class="pdf-header">';
            $html .= $this->extractBodyContent($headerHtml);
            $html .= '</header>';
        }
        
        // Add content section
        $html .= '<div class="pdf-content" style="border: 1px solid red;">';
        $html .= $this->extractBodyContent($contentHtml);
        
        // Add folding marks if enabled
        if ($document->getAttribute('foldingMarks')) {
            $html .= $this->getFoldingMarksHtml();
        }
        
        $html .= '</div>';
        
        // Add footer section - Using <footer> tag for DomPDF
        if (!empty($footerHtml) || $document->getAttribute('showPageNumbers')) {
            $html .= '<footer class="pdf-footer">';
            
            if (!empty($footerHtml)) {
                $html .= $this->extractBodyContent($footerHtml);
            }
            
            if ($document->getAttribute('showPageNumbers')) {
                $html .= $this->getPageNumbersHtml($document);
            }
            
            $html .= '</footer>';
        }
        
        // Add Dompdf page number script
        if ($document->getAttribute('showPageNumbers')) {
            $html .= $this->getDompdfPageScript();
        }
        
        $html .= '</body>
</html>';
        
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
     * Get HTML for page numbers display
     * 
     * DomPDF uses inline PHP to replace page number placeholders
     *
     * @param Document $document
     * @return string
     */
    private function getPageNumbersHtml(Document $document): string
    {
        $prefix = $document->getAttribute('pageNumbersPrefix');
        
        // Use inline PHP script for dynamic page numbers
        return '<div id="page-numbers" style="text-align: right; margin-top: 10px;">
                    <span>' . htmlspecialchars($prefix) . '</span>
                    <script type="text/php">
                        if (isset($pdf)) {
                            $text = $pdf->get_page_number() . " / " . $pdf->get_page_count();
                            $pdf->text(0, 0, $text, null, 10);
                        }
                    </script>
                </div>';
    }
    
    /**
     * Get Dompdf page numbering script
     * 
     * DomPDF supports inline PHP scripts to inject page numbers dynamically
     *
     * @return string
     */
    private function getDompdfPageScript(): string
    {
        return '<script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->getFont("DejaVu Sans", "normal");
                $size = 10;
                $color = array(0, 0, 0);
                
                // Get page number and total pages
                $pageNumber = $pdf->get_page_number();
                $pageCount = $pdf->get_page_count();
                
                // You can use this to render page numbers at a specific position
                // Example: render at bottom right of footer
                // $pdf->page_text(500, 780, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
            }
        </script>';
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
