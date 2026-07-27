<?php

namespace QUITests\HtmlToPdf\Provider\Pdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Provider\Pdf\Utils;

class UtilsTest extends TestCase
{
    #[Test]
    public function elementContentIsExtractedIncludingEmptyContent(): void
    {
        $this->assertSame(
            '<p>Content</p>',
            Utils::extractContentFromHtmlElement(
                '<html><body class="document"><p>Content</p></body></html>'
            )
        );
        $this->assertSame(
            '',
            Utils::extractContentFromHtmlElement('<html><body></body></html>')
        );

        $htmlWithoutBody = '<main>Content</main>';
        $this->assertSame(
            $htmlWithoutBody,
            Utils::extractContentFromHtmlElement($htmlWithoutBody)
        );
    }

    #[Test]
    public function stringIsAppendedToElementContent(): void
    {
        $html = '<body class="document">Existing</body>';

        $this->assertSame(
            '<body class="document">Existing<span>$10</span></body>',
            Utils::appendStringInHtmlElement($html, 'body', '<span>$10</span>')
        );
    }

    #[Test]
    public function stringIsPrependedToElementContent(): void
    {
        $html = "<body class=\"document\">\nExisting\n</body>";

        $this->assertSame(
            "<body class=\"document\"><span>New</span>\nExisting\n</body>",
            Utils::prependStringInHtmlElement($html, 'body', '<span>New</span>')
        );
    }

    #[Test]
    public function htmlWithoutTargetElementRemainsUnchanged(): void
    {
        $html = '<main>Existing</main>';

        $this->assertSame(
            $html,
            Utils::appendStringInHtmlElement($html, 'body', 'New')
        );
        $this->assertSame(
            $html,
            Utils::prependStringInHtmlElement($html, 'body', 'New')
        );
    }

    #[Test]
    public function elementAndItsContentCanBeRemoved(): void
    {
        $html = '<html><head><style>body { color: red; }</style></head><body>Content</body></html>';

        $this->assertSame(
            '<html><head></head><body>Content</body></html>',
            Utils::removeElementFromHtml($html, 'style')
        );
        $this->assertSame(
            $html,
            Utils::removeElementFromHtml($html, 'script')
        );
    }
}
