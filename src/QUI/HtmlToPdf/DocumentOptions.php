<?php

namespace QUI\HtmlToPdf;

use QUI;

use function property_exists;

/**
 * Configuration object for PDF document generation
 */
class DocumentOptions
{
    /**
     * Show page numbers in the footer
     */
    public bool $showPageNumbers = true;

    /**
     * Prefix string for page numbers (e.g. "Page" results in "Page 1/3" on the first page of a 3-page document).
     */
    public string $pageNumbersPrefix = '';

    /**
     * Output filename for the PDF.
     *
     * If null a default filename is chosen with a random id.
     */
    public ?string $filename = null;

//    /**
//     * DPI (dots per inch) for the PDF
//     */
//    public int $dpi = 300;

    /**
     * Distance in mm from PDF page top border to PDF content, IGNORING header area.
     */
    public int $marginTop = 20;

    /**
     * Distance in mm from PDF page right border to PDF content.
     */
    public int $marginRight = 5;

    /**
     * Distance in mm from PDF page bottom border to PDF content, IGNORING footer area.
     */
    public int $marginBottom = 20;

    /**
     * Distance in mm from PDF page left border to PDF content.
     */
    public int $marginLeft = 5;

//    /**
//     * Distance in mm from bottom of header to PDF content.
//     */
//    public int $headerSpacing = 5;
//
//    /**
//     * Distance in mm from top of footer to PDF content.
//     */
//    public int $footerSpacing = 5;
//
//    /**
//     * Zoom factor for the PDF
//     */
//    public float $zoom = 1;

    /**
     * Enable form fields in the PDF.
     */
    public bool $enableForms = false;

    /**
     * Show folding marks (DIN 5008).
     */
    public bool $foldingMarks = false;

    /**
     * CSS class for the header container.
     */
    public string $cssClassHeaderContainer = 'document-header';

    /**
     * CSS class for the body container (main PDF content area; CSS class for the <body> element).
     */
    public string $cssClassBodyContainer = 'document-body';

    /**
     * CSS class for the footer container.
     */
    public string $cssClassFooterContainer = 'document-footer';

    /**
     * CSS class for the footer page numbers container.
     * Only applicable if $showPageNumbers is true
     */
    public string $cssClassPageNumbersContainer = 'document-footer-pageNumbers';

    /**
     * @param array<string,string|bool|numeric>|null $options - Properties of this class provided in array form
     * @param QUI\Locale|null $locale [default: {@see QUI::getLocale()}]
     */
    public function __construct(?array $options = null, private ?QUI\Locale $locale = null)
    {
        if (is_null($this->locale)) {
            $this->locale = QUI::getLocale();
        }

        $this->pageNumbersPrefix = $this->locale->get('quiqqer/htmltopdf', 'footer.page.prefix');

        if (!is_null($options)) {
            foreach ($options as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
            }
        }
    }
}
