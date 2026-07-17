<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Document;

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
}
