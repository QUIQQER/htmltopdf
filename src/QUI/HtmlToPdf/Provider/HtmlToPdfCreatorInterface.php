<?php

namespace QUI\HtmlToPdf\Provider;

use QUI\HtmlToPdf\Document;

interface HtmlToPdfCreatorInterface
{
    /**
     * Create a PDF file from a document.
     * @return string - Full path to the created PDF file
     */
    public function createPdf(Document $document): string;

    // TODO: createImage
}
