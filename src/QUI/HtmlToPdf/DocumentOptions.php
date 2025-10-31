<?php

namespace QUI\HtmlToPdf;

use QUI;

/**
 * Class DocumentOptions
 * Configuration object for PDF document generation
 */
class DocumentOptions
{
    /**
     * Show page numbers in the footer
     */
    public bool $showPageNumbers = true;

    /**
     * Prefix string for page numbers (e.g. "Page" results in "Page 1/2").
     */
    public string $pageNumbersPrefix = '';

    /**
     * Output filename for the PDF.
     *
     * If null a default filename is chosen.
     */
    public ?string $filename = null;

//    /**
//     * DPI (dots per inch) for the PDF
//     */
//    public int $dpi = 300;

    /**
     * Margin in mm from PDF page top border.
     */
    public int $marginTop = 20;

    /**
     * Margin in mm from PDF page right border.
     */
    public int $marginRight = 5;

    /**
     * Margin in mm from PDF page bottom border.
     */
    public int $marginBottom = 20;

    /**
     * Margin in mm from PDF page left border.
     */
    public int $marginLeft = 5;

    /**
     * Header spacing in mm (should be 5 at minimum)
     */
    public int $headerSpacing = 5;

    /**
     * Footer spacing in mm
     */
    public int $footerSpacing = 0;

//    /**
//     * Zoom factor for the PDF
//     */
//    public float $zoom = 1;

    /**
     * Enable form fields in the PDF
     */
    public bool $enableForms = false;

    /**
     * Show folding marks
     */
    public bool $foldingMarks = false;

    /**
     * Disable smart shrinking
     */
    public bool $disableSmartShrinking = false;

    /**
     * CSS class for the header container.
     */
    public string $cssClassHeader = 'document-header';

    /**
     * CSS class for the footer container.
     */
    public string $cssClassFooter = 'document-footer';

    /**
     * Only applicable if $showPageNumbers is true
     */
    public string $cssClassPageNumbersContainer = 'document-footer-pageNumbers';

    /**
     * @param QUI\Locale|null $locale
     */
    public function __construct(private ?QUI\Locale $locale = null)
    {
        if (is_null($this->locale)) {
            $this->locale = QUI::getLocale();
        }

        $this->pageNumbersPrefix = $this->locale->get('quiqqer/htmltopdf', 'footer.page.prefix');
    }
}
