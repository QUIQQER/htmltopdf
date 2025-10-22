<?php

namespace QUI\HtmlToPdf\Provider\ChromeHeadless;

use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;

class Creator implements HtmlToPdfCreatorInterface
{
    /**
     * @inheritDoc
     */
    public function createPdf(Document $document): string
    {
        // TODO
        return '';
    }
}
