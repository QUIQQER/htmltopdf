<?php

namespace QUI\HtmlToPdf\Provider;

use QUI\Locale;

interface HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string;
    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface;
}
