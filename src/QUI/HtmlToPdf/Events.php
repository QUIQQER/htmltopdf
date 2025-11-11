<?php

namespace QUI\HtmlToPdf;

use QUI;
use Smarty;
use SmartyException;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Events
{
    /**
     * Register Smarty functions that are useful for HTML to PDF generation.
     *
     * @param Smarty $smarty
     * @return void
     * @throws SmartyException
     */
    public static function onSmartyInit(Smarty $smarty): void
    {
        $smarty->registerPlugin(
            "function",
            "imageBase64",
            SmartyFunctions::imageBase64(...)
        );
    }
}
