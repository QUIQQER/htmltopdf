<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\Pdf\Mpdf\Provider as ProviderMpdf;
use QUI\HtmlToPdf\Provider\Image\ImageMagick\Provider as ProviderImageMagick;

use function dirname;
use function file_exists;
use function md5_file;
use function unlink;

class CreateImageTest extends TestCase
{
    #[Test]
    public function pdfIsCreatedWithMpdfAndConvertedToImagesWithImageMagick(): void
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
            $pdfProvider = new ProviderMpdf();
            $pdfFile = $pdfProvider->getHtmlToPdfCreator()->createPdf($document);
        } catch (\Exception $Exception) {
            $this->fail('PDF file could not be created :: ' . $Exception->getMessage());
        }

        $this->assertFileExists($pdfFile, 'PDF file not found.');

        try {
            $imageProvider = new ProviderImageMagick();
            $imageFiles = $imageProvider->getPdfToImageConverter()->convertPdfToImage($pdfFile);
        } catch (\Exception $Exception) {
            $this->fail('Image file(s) could not be created :: ' . $Exception->getMessage());
        }

        $this->assertNotEmpty($imageFiles, 'Image files not found.');
        $this->assertFileExists($imageFiles[0], 'First image file not found.');
        $this->assertEquals('1d5db5c9e982818507d75a97af844fbe', md5_file($imageFiles[0]));

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }

        foreach ($imageFiles as $imageFile) {
            unlink($imageFile);
        }
    }
}
