<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\DocumentOptions;

use function file_exists;
use function file_put_contents;
use function tempnam;
use function unlink;

class DocumentTest extends TestCase
{
    #[Test]
    public function partialDocumentsContainThePdfRenderer(): void
    {
        $document = new Document();
        $document->setAttribute('data-renderer', 'chrome');

        $this->assertStringContainsString(
            '<header class="document-header renderer-chrome" data-renderer="chrome">',
            $document->getHeaderHTML()
        );
        $this->assertStringContainsString(
            '<body class="document-body renderer-chrome" data-renderer="chrome">',
            $document->getContentHTML()
        );
        $this->assertStringContainsString(
            '<footer class="document-footer renderer-chrome" data-renderer="chrome">',
            $document->getFooterHTML()
        );
    }

    #[Test]
    public function nestedHeaderElementsAreConvertedToValidDivElements(): void
    {
        $document = new Document();
        $document->setHeaderHTML(
            '<header>Plain header</header><header class="source-header">Attributed header</header>'
        );

        $headerHtml = $document->getHeaderHTML(false);

        $this->assertStringContainsString('<div>Plain header</div>', $headerHtml);
        $this->assertStringContainsString(
            '<div class="source-header">Attributed header</div>',
            $headerHtml
        );
        $this->assertStringNotContainsString('<divPlain header', $headerHtml);
    }

    #[Test]
    public function headerAndFooterContentPresenceCanBeQueried(): void
    {
        $document = new Document();

        $this->assertFalse($document->hasHeaderContent());
        $this->assertFalse($document->hasFooterContent());

        $document->setHeaderHTML(" \n\t");
        $document->setFooterHTML('   ');

        $this->assertFalse($document->hasHeaderContent());
        $this->assertFalse($document->hasFooterContent());

        $document->setHeaderHTML('<p>Header</p>');
        $document->setFooterHTML('<p>Footer</p>');

        $this->assertTrue($document->hasHeaderContent());
        $this->assertTrue($document->hasFooterContent());
    }

    #[Test]
    public function optionsCanBeProvidedAndUpdatedThroughAttributes(): void
    {
        $options = new DocumentOptions([
            'marginTop' => 42,
            'showPageNumbers' => false,
            'cssClassBodyContainer' => 'custom-body',
            'unknownOption' => 'ignored'
        ]);
        $document = new Document($options);

        $this->assertSame(42, $document->options->marginTop);
        $this->assertFalse($document->options->showPageNumbers);
        $this->assertSame('custom-body', $document->options->cssClassBodyContainer);
        $this->assertFalse(property_exists($document->options, 'unknownOption'));

        $document->setAttribute('marginTop', 23);
        $document->setAttribute('unmappedAttribute', 'value');

        $this->assertSame(23, $document->options->marginTop);
        $this->assertSame('value', $document->getAttribute('unmappedAttribute'));
    }

    #[Test]
    public function documentPartsCanBeLoadedFromFilesAndRenderedWithCssFiles(): void
    {
        $headerFile = tempnam(sys_get_temp_dir(), 'htmltopdf-header-');
        $contentFile = tempnam(sys_get_temp_dir(), 'htmltopdf-content-');
        $footerFile = tempnam(sys_get_temp_dir(), 'htmltopdf-footer-');
        $cssFile = tempnam(sys_get_temp_dir(), 'htmltopdf-css-');

        $this->assertNotFalse($headerFile);
        $this->assertNotFalse($contentFile);
        $this->assertNotFalse($footerFile);
        $this->assertNotFalse($cssFile);

        file_put_contents($headerFile, '<header class="source">Header file</header>');
        file_put_contents($contentFile, '<main><img src="/media/cache/image.png">Content file</main>');
        file_put_contents($footerFile, '<footer class="source">Footer file</footer>');
        file_put_contents($cssFile, '.from-file { color: red; }');

        $document = new Document();
        $document->setAttribute('data-renderer', 'test');

        try {
            $document->setHeaderHTML('Header direct');
            $document->setContentHTML('Content direct');
            $document->setFooterHTML('Footer direct');
            $document->setHeaderHTMLFile($headerFile);
            $document->setContentHTMLFile($contentFile);
            $document->setFooterHTMLFile($footerFile);

            $document->setHeaderCSS('.header { color: blue; }');
            $document->setContentCSS('.content { color: green; }');
            $document->setFooterCSS('.footer { color: black; }');
            $document->addHeaderCSSFile($cssFile);
            $document->addContentCSSFile($cssFile);
            $document->addFooterCSSFile($cssFile);

            $headerHtml = $document->getHeaderHTML();
            $contentHtml = $document->getContentHTML();
            $footerHtml = $document->getFooterHTML();

            $this->assertStringContainsString('<div class="source">Header file</div>', $headerHtml);
            $this->assertStringContainsString('<main>', $contentHtml);
            $this->assertStringContainsString('<div class="source">Footer file</div>', $footerHtml);
            $this->assertStringContainsString('type="text/css"', $headerHtml);
            $this->assertStringContainsString('type="text/css"', $contentHtml);
            $this->assertStringContainsString('type="text/css"', $footerHtml);
            $this->assertStringNotContainsString('="/media/cache/', $contentHtml);
            $this->assertStringContainsString('<meta charset="UTF-8">', $headerHtml);
            $this->assertStringContainsString('<meta charset="UTF-8">', $contentHtml);
            $this->assertStringContainsString('<meta charset="UTF-8">', $footerHtml);

            $this->assertStringNotContainsString('<!DOCTYPE html>', $document->getHeaderHTML(false));
            $this->assertStringNotContainsString('<!DOCTYPE html>', $document->getFooterHTML(false));
        } finally {
            unlink($headerFile);
            unlink($contentFile);
            unlink($footerFile);
            unlink($cssFile);
        }
    }

    #[Test]
    public function missingInputFilesAreRejected(): void
    {
        $document = new Document();
        $missingFile = sys_get_temp_dir() . '/htmltopdf-missing-' . uniqid();
        $methods = [
            'setHeaderHTMLFile',
            'setContentHTMLFile',
            'setFooterHTMLFile',
            'addHeaderCSSFile',
            'addContentCSSFile',
            'addFooterCSSFile'
        ];

        foreach ($methods as $method) {
            try {
                $document->$method($missingFile);
                $this->fail($method . ' accepted a missing file.');
            } catch (Exception) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function renderedDocumentPartsCanBeWrittenToTemporaryFiles(): void
    {
        $document = new Document();
        $document->setHeaderHTML('Header');
        $document->setContentHTML('Content');
        $document->setFooterHTML('Footer');

        $files = [
            $document->getHeaderHTMLFile(),
            $document->getContentHTMLFile(),
            $document->getFooterHTMLFile()
        ];

        try {
            foreach ($files as $file) {
                $this->assertFileExists($file);
                $this->assertNotSame('', file_get_contents($file));
            }
        } finally {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
