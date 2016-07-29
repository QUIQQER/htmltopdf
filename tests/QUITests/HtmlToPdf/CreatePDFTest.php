<?php

namespace QUITests\HtmlToPdf;

use QUI;

/**
 * Class FieldsTest
 */
class CreatePDFTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test PDF conversion with "complete" document - header, body AND footer
     *
     */
    public function testPDFCreateComplete()
    {
        $Document = new QUI\HtmlToPdf\Document();

        $Document->setHeaderHTML('<div class="header-test"><p>Ich bin ein Header</p></div>');

        $Document->setContentHTML('<div class="body-test">Ich bin DER Body</div>');
        $Document->setContentCSS('.body-test { color: #ABC123; }');

        $Document->setFooterHTML('<div class="footer-test">Ich bin ein Footer</div>');
        $Document->setFooterCSS('.footer-test { color: #CFE123; }');

        // dateien hinzufügen
        try {
            $Document->addHeaderCSSFile(dirname(__FILE__) . '/files/header.css');
            $Document->addContentCSSFile(dirname(__FILE__) . '/files/body.css');
            $Document->addContentCSSFile(dirname(__FILE__) . '/files/body2.css');
        } catch (\Exception $Exception) {
            $this->fail('PDF-Dokument konnte CSS-Datei nicht hinzugefügt werden :: ' . $Exception->getMessage());
        }

        // erstellt PDF datei
        try {
            $pdfFile = $Document->createPDF();
        } catch (\Exception $Exception) {
            $this->fail('PDF-Dokument konnte nicht erstellt werden :: ' . $Exception->getMessage());
        }

        $this->assertFileExists($pdfFile, 'PDF-Datei nicht gefunden');

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
        // Download der Datei
//        try {
//            $Document->download(false);
//        } catch (\Exception $Exception) {
//            $this->fail('PDF-Dokument konnte nicht heruntergeladen werden :: ' . $Exception->getMessage());
//        }
    }
}