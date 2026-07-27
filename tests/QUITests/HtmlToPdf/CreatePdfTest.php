<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless\Provider as ProviderChromeHeadless;
use QUI\HtmlToPdf\Provider\Pdf\Mpdf\Provider as ProviderMpdf;

use function dirname;
use function file_exists;
use function unlink;

class CreatePdfTest extends TestCase
{
    #[Test]
    public function pdfIsCreatedWithMdf(): void
    {
        $document = new Document();
        $document->setHeaderHTML('<div class="header-test"><p>Ich bin ein Header</p></div>');
        $document->setContentHTML('<div class="body-test">Ich bin DER Body</div>');
        $document->setContentCSS('.body-test { color: #ABC123; }');
        $document->setFooterHTML('<div class="footer-test">Ich bin ein Footer</div>');
        $document->setFooterCSS('.footer-test { color: #CFE123; }');
        // dateien hinzufügen
        try {
            $document->addHeaderCSSFile(dirname(__FILE__) . '/files/header.css');
            $document->addContentCSSFile(dirname(__FILE__) . '/files/body.css');
            $document->addContentCSSFile(dirname(__FILE__) . '/files/body2.css');
        } catch (\Exception $Exception) {
            $this->fail('CSS files could not be added to PDF document :: ' . $Exception->getMessage());
        }

        try {
            $provider = new ProviderMpdf();
            $pdfFile = $provider->getHtmlToPdfCreator()->createPdf($document);
        } catch (\Exception $Exception) {
            $this->fail('PDF file could not be created :: ' . $Exception->getMessage());
        }

        $this->assertFileExists($pdfFile, 'PDF file not found.');

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
    }

    #[Test]
    public function pdfIsCreatedWithChromeHeadless(): void
    {
        $document = new Document();
        $document->setHeaderHTML('<div class="header-test"><p>Ich bin ein Header</p></div>');
        $document->setContentHTML('<div class="body-test">Ich bin DER Body</div>');
        $document->setContentCSS('.body-test { color: #ABC123; }');
        $document->setFooterHTML('<div class="footer-test">Ich bin ein Footer</div>');
        $document->setFooterCSS('.footer-test { color: #CFE123; }');
        // dateien hinzufügen
        try {
            $document->addHeaderCSSFile(dirname(__FILE__) . '/files/header.css');
            $document->addContentCSSFile(dirname(__FILE__) . '/files/body.css');
            $document->addContentCSSFile(dirname(__FILE__) . '/files/body2.css');
        } catch (\Exception $Exception) {
            $this->fail('CSS files could not be added to PDF document :: ' . $Exception->getMessage());
        }

        try {
            $provider = new ProviderChromeHeadless();
            $pdfFile = $provider->getHtmlToPdfCreator()->createPdf($document);
        } catch (\Exception $Exception) {
            $this->fail('PDF file could not be created :: ' . $Exception->getMessage());
        }

        $this->assertFileExists($pdfFile, 'PDF file not found.');

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
    }

    #[Test]
    public function chromeHeadlessRequirementsUseARealBrowserStartup(): void
    {
        $provider = new ProviderChromeHeadless();
        $provider->checkRequirements();

        $this->addToAssertionCount(1);
    }
}
