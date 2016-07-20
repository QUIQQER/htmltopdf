<?php
/**
 * This file contains \QUI\HtmlToPdf\Document
 */

namespace QUI\HtmlToPdf;

use QUI;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Document
{
    /**
     * Path to wkhtmltopdf bin file
     *
     * @var string
     */
    protected $converterBinary = null;


    public function __construct()
    {
        \QUI\System\Log::writeRecursive(1);
        $this->converterBinary = dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/wkhtmltopdf/bin/wkhtmltopdf';

        \QUI\System\Log::writeRecursive(2);

        \QUI\System\Log::writeRecursive($this->converterBinary);
    }

    protected function createPDF()
    {
        
    }
}