<?php

namespace QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless;

use HeadlessChromium\BrowserFactory;
use QUI;
use QUI\Exception;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\Utils\System\File;
use RuntimeException;

use function file_exists;
use function file_put_contents;
use function preg_match_all;
use function str_replace;
use function uniqid;

class Creator implements HtmlToPdfCreatorInterface
{
    public function __construct(
        private readonly ?string $chromeExecutable = null,
        private readonly bool $noSandbox = false,
        private readonly bool $ignoreCertificateErrors = false
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createPdf(Document $document): string
    {
        $htmlFile = null;
        $pdfFile = null;
        $pdfCreated = false;

        try {
            $document->setAttribute('data-renderer', 'chrome');

            // Get package var directory
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $varDir = $Package->getVarDir();
            $chromeHome = $varDir . 'chrome-home/';

            if (!File::mkdir($chromeHome) || !is_writable($chromeHome)) {
                throw new RuntimeException(
                    'Chrome home directory could not be created or is not writable: ' . $chromeHome
                );
            }

            // Build complete HTML document
            $html = $this->buildCompleteHtml($document);

            // Generate temporary HTML file
            $documentId = uniqid();
            $htmlFile = $varDir . $documentId . '.html';
            $pdfFile = $varDir . $documentId . '.pdf';

            if (file_put_contents($htmlFile, $html) === false) {
                throw new RuntimeException('Could not write temporary HTML file: ' . $htmlFile);
            }

            // Create PDF using Chrome Headless
            $this->generatePdfWithChrome($htmlFile, $pdfFile, $document, $chromeHome);
            $pdfCreated = true;

            return $pdfFile;
        } catch (\Throwable $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.pdf.conversion.failed'
            ]);
        } finally {
            $this->removeTemporaryFile($htmlFile);

            if ($pdfCreated === false) {
                $this->removeTemporaryFile($pdfFile);
            }
        }
    }

    private function removeTemporaryFile(?string $file): void
    {
        if ($file === null || !file_exists($file)) {
            return;
        }

        try {
            if (!unlink($file)) {
                QUI\System\Log::addWarning('Could not delete temporary HTML-to-PDF file: ' . $file);
            }
        } catch (\Throwable $Exception) {
            QUI\System\Log::writeException($Exception);
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
<html class="renderer-chrome" data-renderer="chrome">
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
        $html .= '</div>';
        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }

    /**
     * Generate PDF using Chrome Headless
     *
     * @param string $htmlFile
     * @param string $pdfFile
     * @param Document $document
     * @return void
     *
     * @throws \Exception
     */
    private function generatePdfWithChrome(
        string $htmlFile,
        string $pdfFile,
        Document $document,
        string $chromeHome
    ): void {
        $userDataDir = $chromeHome . 'profiles/' . uniqid('profile-', true) . '/';

        if (!File::mkdir($userDataDir) || !is_writable($userDataDir)) {
            throw new RuntimeException(
                'Chrome user data directory could not be created or is not writable: ' . $userDataDir
            );
        }

        $browserFactory = new BrowserFactory($this->chromeExecutable);
        $browser = null;

        try {
            // Configure Chrome options
            $browser = $browserFactory->createBrowser([
                'headless' => true,
                'noSandbox' => $this->noSandbox,
                'ignoreCertificateErrors' => $this->ignoreCertificateErrors,
                'userDataDir' => $userDataDir,
                'envVariables' => [
                    'HOME' => $chromeHome,
                    'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                    'XDG_CACHE_HOME' => $chromeHome . '.cache',
                    'XDG_CONFIG_HOME' => $chromeHome . '.config',
                    'XDG_DATA_HOME' => $chromeHome . '.local/share'
                ],

                'headers' => [
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0'
                ]
            ]);

            $page = $browser->createPage();

            // Navigate and wait for page to load
            $navigation = $page->navigate('file://' . $htmlFile);
            $navigation->waitForNavigation();

            // Build header and footer templates
            $headerTemplate = $this->buildHeaderTemplate($document);
            $footerTemplate = $this->buildFooterTemplate($document);

//            preg_match_all('#\d*mm#i', $footerTemplate, $matches);
//
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

                'marginTop' => $this->mmToInches((float)$document->options->marginTop),
                'marginRight' => $this->mmToInches((float)$document->options->marginRight),
                'marginBottom' => $this->mmToInches((float)$document->options->marginBottom),
                'marginLeft' => $this->mmToInches((float)$document->options->marginLeft),

                // A4 paper size in inches
                'paperWidth' => 8.26772,   // A4 width: 210mm = 8.27 inches
                'paperHeight' => 11.6929, // A4 height: 297mm = 11.69 inches
            ];

            // Generate PDF
            $pdf = $page->pdf($pdfOptions);

            // Save to file
            $pdf->saveToFile($pdfFile);
        } finally {
            if ($browser !== null) {
                $browser->close();
            }

            File::deleteDir($userDataDir);
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

        // Add folding marks if enabled
        if ($document->options->foldingMarks) {
            $headerHtml = str_replace('</header>', $this->getFoldingMarksHtml() . '</header>', $headerHtml);
        }

        // Specific styling for footer wrapper
        $style = '<style>
             * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
             }
             
            .' . $document->options->cssClassHeaderContainer . '{
                position: absolute;
                width: calc(100% - ' . $document->options->marginLeft . 'mm - ' . $document->options->marginRight . 'mm);
                height: ' . $document->options->marginTop . 'mm;
                top: 0;
                left: ' . $document->options->marginLeft . 'mm;
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
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
             }

            .' . $document->options->cssClassFooterContainer . '{
                position: absolute;
                width: calc(100% - ' . $document->options->marginLeft . 'mm - ' . $document->options->marginRight . 'mm);
                height: ' . $document->options->marginBottom . 'mm;
                bottom: 0;
                left: ' . $document->options->marginLeft . 'mm;
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
                    width: ' . $this->mmToInches(4) . 'in
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
