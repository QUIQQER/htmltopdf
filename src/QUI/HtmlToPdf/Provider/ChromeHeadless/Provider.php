<?php

namespace QUI\HtmlToPdf\Provider\ChromeHeadless;

use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;
use QUI;

use function putenv;

class Provider implements HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.ChromeHeadless.title');
    }

    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface
    {
        // Read Chrome binary path from settings
        try {
            $config = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
            $chromePath = $config->get('chrome_headless', 'executable');
            
            if (!empty($chromePath)) {
                putenv("CHROME_PATH=" . $chromePath);
            } else {
                // Fallback to default Chrome path
                putenv("CHROME_PATH=/usr/bin/google-chrome");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            // Fallback to default Chrome path
            putenv("CHROME_PATH=/usr/bin/google-chrome");
        }
        
        return new Creator();
    }
}
