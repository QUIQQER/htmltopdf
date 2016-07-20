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
class Document extends QUI\QDOM
{
    /**
     * Path to wkhtmltopdf bin file
     *
     * @var string
     */
    protected $converterBinary = null;

    /**
     * Unique document id
     *
     * @var string
     */
    protected $documentId = null;

    /**
     * Flag if PDF has already been created
     *
     * @var bool
     */
    protected $created = false;

    /**
     * Header data for PDF conversion
     *
     * @var array
     */
    protected $header = array(
        'css'      => '',
        'cssFiles' => array(),
        'content'  => ''
    );

    /**
     * Content (body) data for PDF conversion
     *
     * @var array
     */
    protected $body = array(
        'css'      => '',
        'cssFiles' => array(),
        'content'  => ''
    );

    /**
     * Footer data for PDF conversion
     *
     * @var array
     */
    protected $footer = array(
        'css'      => '',
        'cssFiles' => array(),
        'content'  => ''
    );

    /**
     * Document constructor.
     *
     * @param array $settings (optional) -
     */
    public function __construct($settings = array())
    {
        $this->setAttributes(array(
            'showPageNumbers'   => true,
            'pageNumbersPrefix' => QUI::getLocale()->get('quiqqer/htmltopdf', 'footer.page.prefix'),
            'filename'          => '',
            'dpi'               => 300,
            'marginTop'         => 20,    // mm
            'marginRight'       => 5,     // mm
            'marginBottom'      => 20,    // mm
            'marginLeft'        => 5,     // mm
            'headerSpacing'     => 5,     // should be 5 at minimum
            'footerSpacing'     => 0
        ));

        $this->setAttributes($settings);

        $this->documentId      = uniqid();
        $this->converterBinary = dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/wkhtmltopdf/bin/wkhtmltopdf';
    }

    /**
     * Set HTML content for PDF header area
     *
     * @param string $html
     */
    public function setHeaderContent($html)
    {
        $this->header['content'] = $html;
    }

    /**
     * Set CSS rules for PDF header area
     *
     * @param string $css
     */
    public function setHeaderCSS($css)
    {
        $this->header['css'] = $css;
    }

    /**
     * Adds a css file that is loaded into the PDF header area
     *
     * @param string $file - path to css file
     *
     * @throws QUI\Exception
     */
    public function addHeaderCSSFile($file)
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(array(
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                array(
                    'file' => $file
                )
            ));
        }

        $this->header['cssFiles'][] = $file;
    }

    /**
     * Set HTML content for PDF body area
     *
     * @param string $html
     */
    public function setContent($html)
    {
        $this->body['content'] = $html;
    }

    /**
     * Set CSS rules for PDF content area
     *
     * @param string $css
     */
    public function setContentCSS($css)
    {
        $this->body['css'] = $css;
    }

    /**
     * Adds a css file that is loaded into the PDF content area
     *
     * @param string $file - path to css file
     *
     * @throws QUI\Exception
     */
    public function addContentCSSFile($file)
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(array(
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                array(
                    'file' => $file
                )
            ));
        }

        $this->body['cssFiles'][] = $file;
    }

    /**
     * Set HTML content for PDF footer area
     *
     * @param string $html
     */
    public function setFooterContent($html)
    {
        $this->footer['content'] = $html;
    }

    /**
     * Set CSS rules for PDF footer area
     *
     * @param string $css
     */
    public function setFooterCSS($css)
    {
        $this->footer['css'] = $css;
    }

    /**
     * Adds a css file that is loaded into the PDF footer area
     *
     * @param string $file - path to css file
     *
     * @throws QUI\Exception
     */
    public function addFooterCSSFile($file)
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(array(
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                array(
                    'file' => $file
                )
            ));
        }

        $this->footer['cssFiles'][] = $file;
    }

    /**
     * Create PDF file based on settings
     *
     * @return string - pdf file path
     *
     * @throws QUI\Exception
     */
    public function createPDF()
    {
        $Package = QUI::getPackage('quiqqer/htmltopdf');
        $varDir  = $Package->getVarDir();

        $cmd = $this->converterBinary . ' ';

        $cmd .= ' -T ' . $this->getAttribute('marginTop') . 'mm';
        $cmd .= ' -R ' . $this->getAttribute('marginRight') . 'mm';
        $cmd .= ' -B ' . $this->getAttribute('marginBottom') . 'mm';
        $cmd .= ' -L ' . $this->getAttribute('marginLeft') . 'mm';

        $headerHtmlFile = false;
        $footerHtmlFile = false;

        if (!empty($this->header['content'])) {
            $cmd .= ' --header-spacing ' . $this->getAttribute('headerSpacing');

            $headerHtmlFile = $varDir . 'header_' . $this->documentId . '.html';
            file_put_contents($headerHtmlFile, $this->buildHeaderHTML());

            $cmd .= ' --header-html "' . $headerHtmlFile . '"';
            $cmd .= ' --header-line';
        }

        if (!empty($this->footer['content'])
            || $this->getAttribute('showPageNumbers')
        ) {
            $cmd .= ' --footer-spacing ' . $this->getAttribute('footerSpacing');

            $footerHtmlFile = $varDir . 'footer_' . $this->documentId . '.html';
            file_put_contents($footerHtmlFile, $this->buildFooterHTML());

            $cmd .= ' --footer-html "' . $footerHtmlFile . '"';
        }

        $cmd .= ' --dpi ' . (int)$this->getAttribute('dpi');

        $bodyHtmlFile = $varDir . 'body_' . $this->documentId . '.html';
        file_put_contents($bodyHtmlFile, $this->buildBodyHTML());

        $pdfFile = $varDir . $this->documentId . '.pdf';

        $cmd .= ' ' . $bodyHtmlFile . ' ' . $pdfFile;

        exec($cmd, $output, $exitStatus);

        if ($exitStatus !== 0) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF conversion failed:: ' . json_encode($output)
            );

            throw new QUI\Exception(array(
                'quiqqer/htmltopdf',
                'exception.document.pdf.conversion.failed'
            ));
        }

        // delete html files
        if ($headerHtmlFile) {
            unlink($headerHtmlFile);
        }

        unlink($bodyHtmlFile);

        if ($footerHtmlFile) {
            unlink($footerHtmlFile);
        }

        $this->created = true;

        return $pdfFile;
    }

    /**
     * Download PDF file
     *
     * @param bool $deletePdfFile (optional) - delete pdf file after download
     * @return void
     *
     * @throws QUI\Exception
     */
    public function download($deletePdfFile = true)
    {
        if (!$this->created) {
            $file = $this->createPDF();
        } else {
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $varDir  = $Package->getVarDir();

            $file = $varDir . $this->documentId . '.pdf';

            if (!file_exists($file)) {
                $file = $this->createPDF();
            }
        }

        $filename = $this->getAttribute('filename');

        if (empty($filename)) {
            $filename = $this->documentId . '_' . date("d_m_Y__H_m");
        }

        try {
            QUI\Utils\System\File::send($file, 0, $filename);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF download failed:: ' . $Exception->getMessage()
            );

            throw new QUI\Exception(array(
                'quiqqer/htmltopdf',
                'exception.document.pdf.download.failed'
            ));
        }

        if ($deletePdfFile) {
            unlink($file);
        }
    }

    /**
     * Build header html from header settings
     *
     * @return string - complete HTML for PDF header
     */
    protected function buildHeaderHTML()
    {
        $hd = $this->header;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $header .= '<style>' . $hd['css'] . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>' . $hd['content'] . '</body></html>';

        return $header . $body;
    }

    /**
     * Build body html from body settings
     *
     * @return string - complete HTML for PDF body
     */
    protected function buildBodyHTML()
    {
        $hd = $this->body;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $header .= '<style>' . $hd['css'] . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>' . $hd['content'] . '</body></html>';

        return $header . $body;
    }

    /**
     * Build body html from body settings
     *
     * @return string - complete HTML for PDF footer
     */
    protected function buildFooterHTML()
    {
        $hd = $this->footer;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $header .= '<style>' . $hd['css'] . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>';
        $body .= $hd['content'];

        if ($this->getAttribute('showPageNumbers')) {
            $body .= '<div id="pages">
                        <span id="pages_prefix">' . $this->getAttribute('pageNumbersPrefix') . '</span>
                        <span id="pages_current"></span>
                        <span id="pages_total"></span>
                    </div>';

            $body .= '<script>
                          var parts = document.location.href.split("&");
                          var currentPage, totalPages;

                          for (var i = 0, len = parts.length; i < len; i++) {
                              var param = parts[i].split("=");

                              switch (param[0]) {
                                case "sitepage":
                                    currentPage = decodeURIComponent(param[1]);
                                    break;
                                case "topage":
                                    totalPages = decodeURIComponent(param[1]);
                                    break;
                              }
                          }

                          document.getElementById("pages_current").innerHTML = currentPage + " / ";
                          document.getElementById("pages_total").innerHTML = totalPages;
                      </script>';
        }

        $body .= '</body></html>';

        return $header . $body;
    }
}