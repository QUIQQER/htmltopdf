<?php

namespace QUI\HtmlToPdf\Provider\Mpdf;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use QUI;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

use function array_pop;
use function mb_strlen;
use function mb_strpos;
use function preg_match_all;
use function preg_replace_callback;

class Creator implements HtmlToPdfCreatorInterface
{
    private bool $initWriteHtml = true;
    private string $styles = '';
    private string $body = '';

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
//            'margin_footer' => (float)$document->getAttribute('marginBottom'),
            'margin_header' => (float)$document->getAttribute('headerSpacing'),
            'margin_footer' => (float)$document->getAttribute('footerSpacing'),
            'orientation' => 'P',
            'tempDir' => sys_get_temp_dir()
        ];

        // Apply DPI setting
        $dpi = (int)$document->getAttribute('dpi');
        if ($dpi > 0) {
//            $config['dpi'] = $dpi;
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
        $headerHtml = $this->extractElementContent($fullHeaderHtml);

//        $headerHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $headerHtml);

        $this->body .= $headerHtml;


        $headerHtml = '

<!-- Workaround to achieve full a4 format as PDF file -->

<div class="invoice-header invoice-header-top" style="border: 1px solid red;">
    <table class="invoice-header-image">
        <tbody>
            <tr>
            <td style="vertical-align: top;">
            
            <div style="vertical-align: top; border: 1px solid red; float: left; width: 150px; display: inline; bottom: initial; font-size: 12px; text-align: left; line-height: 16px;">
                <b>Überweisen per Code</b>
            Ganz bequem Code mit
            der Banking-App scannen.
            </div>
            </td>
            
            <td>
            <img src="data:image/png;base64,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" style="height: 90px;  float: right; border: 1px solid green; top: 0; position: absolute;"/>
            </td>
            </tr> 
        </tbody>
            </table>

    <div class="invoice-body-header-text">
        Party Peat - Ruhrstr. 13 - 42697 Solingen
    </div>

    <div class="invoice-customer">
                <address class="vcard">
    <div class="adr">

                    
            
            <div class="name"> a a</div>

                    
        <div class="street-address">a 1</div>
                <div class="locality">
            <span class="postal-country">DE-</span>
            <span class="postal-code">a</span>
            <span class="postal-city">a</span>
        </div>
        
            </div>
</address>

            </div>

    <div class="invoice-data">
        <div class="invoice-data-highlight">
                        <h2>Rechnung</h2>
                        <table>
                <tbody>
                <tr>
                    <td>Beleg-Nr.</td>
                    <td class="value-id"><span>INV-2025-261326</span></td>
                </tr>
                <tr>
                    <td>Datum</td>
                    <td class="value-date"><span>05.03.25</span></td>
                </tr>
                                <tr>
                    <td>Kunden-Nr.</td>
                                        <td class="value-customer"><span>KD-1015000002</span></td>
                                    </tr>
                
                                <tr>
                    <td>Bestell-Nr.</td>
                    <td class="value-orderNumber"><span>2025-52</span></td>
                </tr>
                
                
                                                <tr>
                    <td>Vertrags-Nr.</td>
                    <td class="value-contract-id">
                        
                        <span>CONTR-2148</span>
                    </td>
                </tr>
                

                

                
                </tbody>
            </table>
        </div>

            </div>

    <div class="invoice-header-line"></div>
</div>

';

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
        if ($document->getAttribute('showPageNumbers')) {
            $pageNumbersHtml = $this->getPageNumbersHtml($document);
            // Extract last HTML closing element name in $footerHtml
            preg_match_all('/<\/(\w+)>$/m', $footerHtml, $matches);

            if (!empty($matches)) {
                $match = array_pop($matches);
                $lastClosingElementName = array_pop($match);
                $pos = mb_strpos(
                    $footerHtml,
                    "</$lastClosingElementName>",
                    -(mb_strlen("</$lastClosingElementName>") + 2)
                );
                $footerHtml = mb_substr($footerHtml, 0, $pos) . $pageNumbersHtml . mb_substr($footerHtml, $pos);
            }
        }

        // Remove position:fixed and position:absolute from footer (not supported in mPDF footers)
        $footerHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $footerHtml);
        $this->body .= $footerHtml;

        $footerHtml = '

<footer class="invoice-footer" style="border: 1px solid red;">
    <div class="invoice-footer-line"></div>

    <div style="width: 100%;">
        &nbsp;
    </div>

    <div class="invoice-footer-container invoice-footer-container__company">
        <ul>
                        <li>
                <header>
                Party Peat
                </header>
            </li>
                                    <li>
                Ruhrstr. 13
            </li>
            
                        <li>
                                42697
                
                                Solingen
                            </li>
                    </ul>
    </div>

    <div class="invoice-footer-container">
        <header>
            Telefon | Mail | Web
        </header>


        <table>
            <tbody>

            
            
                        <tr>
                <td class="table-label">E-Mail:
                </td>
                <td>peat+party@mailbox.org</td>
            </tr>
            
                        </tbody>
        </table>

    </div>

    <div class="invoice-footer-container">
        <header>
            Steuerinformationen
        </header>

        <ul>
                                            </ul>

                <header class="invoice-footer-companyOwner">
            Geschäftsführer
        </header>
        <ul>
            <li>
                Patrick Müller
            </li>
        </ul>
            </div>

        <div class="invoice-footer-container">

        <header>
            Bankverbindung
        </header>
        <ul>
                        <li>
                Feier und Sauf Bank Solingen
            </li>
                                    <li>
                IBAN:
                DE11 1111 2222 3333 4444 55
            </li>
                                    <li>
                BIC:
                DEUTPARTY69
            </li>
                    </ul>
    </div>
    <div id="pages" style="text-align: right; width: 100%; border: 1px solid red;">
                    <span id="pages_prefix">Seite</span>
                    <span id="pages_current">{PAGENO}</span>
                    <span> / </span>
                    <span id="pages_total">{nbpg}</span>
                </div></footer>

';

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

        // Add folding marks as watermark if enabled (position:fixed works in body, not in header)
        if ($document->getAttribute('foldingMarks')) {
            $contentHtml = $this->getFoldingMarksHtml() . $contentHtml;
        }

        $contentHtml = $this->extractAndRemoveStyleElementsAndAppendToMpdf($mpdf, $contentHtml);
        $this->body .= $contentHtml;


        $contentHtml = '
        

<div class="invoice-body" style="border: 1px solid red";>
    <div class="invoice-body-extra">
        
        
                <p class="invoice-body-orderedBy">
            Bestellt durch:
            a a
        </p>
            </div>

    

    <div class="invoice-articles">
        <!-- articles -->
<h1 class="articles-header">
    Produkte
</h1>

<table class="articles-article">
    <thead>
    <tr class="articles-article-header">
        <th class="articles-article-header-pos">
            POS
        </th>
        <th class="articles-article-header-articleNo">
            Artikel Nr.
        </th>
        <th class="articles-article-header-information">
            Beschreibung
        </th>
                    <th class="articles-article-header-vat">
                MwSt.
            </th>
                <th class="articles-article-header-quantity" colspan="2">
            Menge
        </th>
        <th class="articles-article-header-unitprice">
            Einzelpreis
        </th>
        <th class="articles-article-header-price">
            Preis
        </th>
    </tr>
    </thead>

    <tbody>
        <tr class="articles-article-entry articles-article--real articles-article--odd">
    <td class="articles-article-pos"
        data-label="POS"
    >
        1
    </td>
    <td class="articles-article-articleNo"
        data-label="Artikel Nr."
    >
        PROD-2024-2260-XTREME-
    </td>
    <td class="articles-article-information"
        data-label="Beschreibung"
    >
        <div class="articles-article-information-title">
            Download-Mitgliedschaft
        </div>
        <div class="articles-article-information-description">
            deutsche Kurzbeschreibung<br/>
<b>Rechnungsintervall:</b> monatlich<br/>
<b>Mindestlaufzeit:</b> 1 Monat<br/>
<b>Kündigungsfrist:</b> 1 Monat<br/><br/>
        </div>

        <ul class="quiqqer-order-basket-articles-article-fields">
                    </ul>
    </td>
        <td class="articles-article-vat"
        data-label="MwSt."
    >
        19%
    </td>
    
        <td class="articles-article-quantity"
        data-label="Menge"
    >
        1
    </td>
    <td class="articles-article-quantityUnit">
        Stück
    </td>
    
        <td class="articles-article-unitprice"
        data-label="Einzelpreis"
    >
                        <span class="articles-article-unitprice-price">10,00 €</span>
                    </td>
    
        <td class="articles-article-price"
        data-label="Preis"
    >
                10,00 €
            </td>
    </tr>

        </tbody>
</table>

<!-- sum display -->
<div class="articles-sum-container">
    <table class="articles-sum">
        <tr class="articles-sum-row-subsum">
            <td class="articles-sum-row-firstCell">
            <span class="articles-sum-row-subsum-text">
                Zwischensumme:
            </span>
            </td>
            <td style="width: 140px" class="articles-sum-row-sndCell">
            <span class="articles-sum-row-subsum-value">
                10,00 €
            </span>
            </td>
        </tr>

        
                <tr>
            <td>
            <span class="articles-sum-vat-text">
                zzgl. 19% MwSt.
            </span>
            </td>
            <td>
            <span class="articles-sum-vat-value">
                1,90 €
            </span>
            </td>
        </tr>
        
        
        <tr class="articles-sum-row-sum">
            <td class="articles-sum-row-firstCell">
            <span class="articles-sum-row-sum-text">
                Gesamtsumme
            </span>
            </td>
            <td class="articles-sum-row-sndCell">
            <span class="articles-sum-row-sum-value">
                11,90 €
            </span>
            </td>
        </tr>
    </table>

    </div>

    </div>

    <div class="invoice-additional">
        <div class="invoice-additional-left">
                                                <span class="additional-invoice-title">Zahlungsziel:</span>
            <span class="additional-invoice-value">12.03.25</span>
                
                <br/>
                <span class="additional-invoice-title">Zahlungsart:</span>
                <span class="additional-invoice-value">Rechnung</span>

                                    <br/>Bitte überweisen Sie den Rechnungsbetrag bis zum 12.03.25 auf das unten angegebene Konto.
                                    </div>

                <div class="invoice-additional-global-invoice-text">
            Es gelten meine allgemeinen Geschäftsbedingungen: Der Zug, der Zug, der Zug hat keine Bremse...
        </div>
        
            </div>
</div>

';

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
        $prefix = $document->getAttribute('pageNumbersPrefix');

        return '<div id="pages" style="text-align: right; width: 100%; border: 1px solid red;">
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
        $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function ($matches) use (&$styles) {
            $styles .= $matches[1];
            return '';
        }, $html);

        if (!empty($styles)) {
            $mpdf->WriteHTML($styles, HTMLParserMode::HEADER_CSS, $this->initWriteHtml, false);
            $this->styles .= $styles;
            $this->initWriteHtml = false;
        }

        return $html;
    }
}
