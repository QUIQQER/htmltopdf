<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

use QUI\Locale;

interface HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string;
    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface;
}
