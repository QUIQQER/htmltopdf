<?php

/**
 * This file contains \QUI\HtmlToPdf\Document
 */

namespace QUI\HtmlToPdf;

use QUI;

use function array_merge;
use function array_unique;
use function array_values;
use function file_exists;
use function mb_substr;
use function pathinfo;
use function preg_replace;
use function system;
use function trim;
use function unlink;

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
     * @var ?string
     */
    protected ?string $converterBinary = null;

    /**
     * Unique document id
     *
     * @var string|null
     */
    protected ?string $documentId = null;

    /**
     * Flag if PDF has already been created
     *
     * @var bool
     */
    protected bool $created = false;

    /**
     * Var directory of quiqqer/htmltopdf package
     *
     * @var string|null
     */
    protected ?string $varDir = null;

    /**
     * Header data for PDF conversion
     *
     * @var array
     */
    protected array $header = [
        'css' => '',
        'cssFiles' => [],
        'content' => '',
        'htmlFile' => false
    ];

    /**
     * Content (body) data for PDF conversion
     *
     * @var array
     */
    protected array $body = [
        'css' => '',
        'cssFiles' => [],
        'content' => ''
    ];

    /**
     * Footer data for PDF conversion
     *
     * @var array
     */
    protected array $footer = [
        'css' => '',
        'cssFiles' => [],
        'content' => ''
    ];

    /**
     * Document constructor.
     *
     * @param array $settings (optional)
     */
    public function __construct(array $settings = [])
    {
        $this->setAttributes([
            'showPageNumbers' => true,
            'pageNumbersPrefix' => QUI::getLocale()->get('quiqqer/htmltopdf', 'footer.page.prefix'),
            'filename' => '',
            'dpi' => 300,
            'marginTop' => 20,    // mm
            'marginRight' => 5,     // mm
            'marginBottom' => 20,    // mm
            'marginLeft' => 5,     // mm
            'headerSpacing' => 5,     // should be 5 at minimum
            'footerSpacing' => 0,
            'zoom' => 1,
            'enableForms' => false,
            'foldingMarks' => false,
            'disableSmartShrinking' => false
        ]);

        $this->setAttributes($settings);

        try {
            Handler::checkPDFGeneratorBinary();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            Handler::sendBinaryWarningMail($Exception->getMessage());
        }

        $this->documentId = uniqid();
        $this->converterBinary = Handler::getPDFGeneratorBinaryPath();

        try {
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $this->varDir = $Package->getVarDir();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Set HTML for PDF header area
     *
     * @param string $html - HTML code
     */
    public function setHeaderHTML(string $html): void
    {
        $this->header['content'] = $html;
    }

    /**
     * Set HTML file used for PDF header area
     *
     * @param string $file - path to html file
     *
     * @throws QUI\Exception
     */
    public function setHeaderHTMLFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.html.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
        }

        $this->header['content'] = file_get_contents($file);
    }

    /**
     * Set CSS rules for PDF header area
     *
     * @param string $css
     */
    public function setHeaderCSS(string $css): void
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
    public function addHeaderCSSFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
        }

        $this->header['cssFiles'][] = $file;
    }

    /**
     * Set HTML content for PDF body area
     *
     * @param string $html - HTML code
     */
    public function setContentHTML(string $html): void
    {
        $this->body['content'] = $html;
    }

    /**
     * Set HTML file used for PDF body area
     *
     * @param string $file - path to html file
     *
     * @throws QUI\Exception
     */
    public function setContentHTMLFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.html.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
        }

        $this->body['content'] = file_get_contents($file);
    }

    /**
     * Set CSS rules for PDF content area
     *
     * @param string $css
     */
    public function setContentCSS(string $css): void
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
    public function addContentCSSFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
        }

        $this->body['cssFiles'][] = $file;
    }

    /**
     * Set HTML content for PDF footer area
     *
     * @param string $html
     */
    public function setFooterHTML(string $html): void
    {
        $this->footer['content'] = $html;
    }

    /**
     * Set HTML file used for PDF footer area
     *
     * @param string $file - path to html file
     *
     * @throws QUI\Exception
     */
    public function setFooterHTMLFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.html.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
        }

        $this->footer['content'] = file_get_contents($file);
    }

    /**
     * Set CSS rules for PDF footer area
     *
     * @param string $css
     */
    public function setFooterCSS(string $css): void
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
    public function addFooterCSSFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.css.file.does.not.exist',
                [
                    'file' => $file
                ]
            ]);
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
    public function createPDF(): string
    {
        $varDir = $this->varDir;

        // Determine library path
        $cmdPrefix = '';

        try {
            $Conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
            $libPath = $Conf->get('settings', 'lib_path');

            if (is_string($libPath)) {
                $libPath = trim($libPath);
            }

            if (!empty($libPath)) {
                $cmdPrefix = 'export LD_LIBRARY_PATH=' . $libPath . '; ';
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $cmd = $cmdPrefix . $this->converterBinary . ' ';

        $cmd .= ' -T ' . $this->getAttribute('marginTop') . 'mm';
        $cmd .= ' -R ' . $this->getAttribute('marginRight') . 'mm';
        $cmd .= ' -B ' . $this->getAttribute('marginBottom') . 'mm';
        $cmd .= ' -L ' . $this->getAttribute('marginLeft') . 'mm';

        if ($this->getAttribute('disableSmartShrinking') === true) {
            $cmd .= ' --disable-smart-shrinking';
        }

        if ($this->getAttribute('enableForms') === true) {
            $cmd .= ' --enable-forms';
        }

        $headerHtmlFile = false;
        $footerHtmlFile = false;

        if (!empty($this->header['content'])) {
            $cmd .= ' --header-spacing ' . $this->getAttribute('headerSpacing');

            $headerHtmlFile = $this->getHeaderHTMLFile();

            $cmd .= ' --header-html "' . $headerHtmlFile . '"';
//            $cmd .= ' --header-line';
        }

        if (
            !empty($this->footer['content'])
            || $this->getAttribute('showPageNumbers')
        ) {
            $cmd .= ' --footer-spacing ' . $this->getAttribute('footerSpacing');

            $footerHtmlFile = $this->getFooterHTMLFile();

            $cmd .= ' --footer-html "' . $footerHtmlFile . '"';
        }

        $cmd .= ' --dpi ' . (int)$this->getAttribute('dpi');
        $cmd .= ' --zoom ' . (float)$this->getAttribute('zoom');

        // Additional CLI params
        foreach (Handler::$cliParams as $cliParam) {
            $cmd .= ' ' . $cliParam;
        }

        $bodyHtmlFile = $this->getContentHTMLFile();

        $pdfFile = $varDir . $this->documentId . '.pdf';

        $cmd .= ' ' . $bodyHtmlFile . ' ' . $pdfFile;

        exec($cmd . ' 2> /dev/null', $output, $exitStatus);

        if ($exitStatus !== 0) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF conversion failed:: ' . json_encode($output)
                . ' -- PDF create cmd: > ' . $cmd . ' <'
            );

            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.pdf.conversion.failed'
            ]);
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

        QUI::getEvents()->fireEvent('quiqqerHtmlToPDFCreated', [$this, $pdfFile]);

        return $pdfFile;
    }

    /**
     * @param bool $deletePdfFile
     * @param array $cliParams (optional) - Additional CLI params for the "convert" command [default: no additional params]
     * @param bool $trim (optional) - Trim margin of PDF file before generating image [default: true]
     * @return string|array - File to generated image or array with image files if multiple images are generated
     *
     * @throws QUI\Exception
     */
    public function createImage(bool $deletePdfFile = true, array $cliParams = [], bool $trim = true): array|string
    {
        // TEST
//        $html = $this->getHeaderHTML()
//                .$this->getContentHTML()
//                .$this->getFooterHTML(false);
//
//        $htmlFile  = $this->varDir.'test.html';
//        $imageFile = $this->varDir.'text.jpg';
//
//        \file_put_contents($htmlFile, $html);
//
//        $cmd = 'wkhtmltoimage';
//
//        $cmd .= ' --disable-smart-width';
//
//        $cmd .= ' '.$htmlFile.' '.$imageFile;
//
//        exec($cmd.' 2> /dev/null', $output, $exitStatus);
//
//        \QUI\System\Log::writeRecursive($imageFile);
//
//        return $imageFile;
        // /TEST

        Handler::checkConvertBinary();

        $pdfFile = $this->createPDF();
        $imageFile = mb_substr($pdfFile, 0, -4) . '.jpg';

        $pdfFileLine = '\'' . $pdfFile . '\'';

        if ($trim) {
            $pdfFileLine = '-trim ' . $pdfFileLine;
        }

        $cliParams = array_merge(
            $cliParams,
            [
                '-density 300',
                $pdfFileLine,
                '-quality 100',
                '-resize 2480x3508', // DIN A4
                '\'' . $imageFile . '\'',
            ]
        );

        $cliParams = array_values(array_unique($cliParams));
        $command = Handler::getConvertBinaryPath();

        foreach ($cliParams as $param) {
            $param = trim($param);

            if (empty($param)) {
                continue;
            }

            $command .= ' ' . $param;
        }

        system($command);

        // Delete source PDF
        if ($deletePdfFile && file_exists($pdfFile)) {
            unlink($pdfFile);
        }

        if (!file_exists($imageFile)) {
            /**
             * Check if the PDF was split into multiple images.
             * In this case the images need to be appended to one single image.
             */
            $imageFileInfo = pathinfo($imageFile);
            $imageFileExt = $imageFileInfo['extension'];
            $imageFileDir = $imageFileInfo['dirname'] . '/';
            $imageFileNoExt = $imageFileDir . $imageFileInfo['filename'];

            if (!file_exists($imageFileNoExt . '-0.' . $imageFileExt)) {
                throw new QUI\Exception(
                    'Could not create image from pdf. Command: "' . $command . '".'
                );
            }

            $imageFiles = [];
            $imageNo = 0;

            do {
                $imageFileNumbered = $imageFileNoExt . '-' . $imageNo++ . '.' . $imageFileExt;

                if (!file_exists($imageFileNumbered)) {
                    break;
                }

                $imageFiles[] = $imageFileNumbered;
            } while (true);

            return $imageFiles;
        }

        return $imageFile;
    }

    /**
     * Download PDF file
     *
     * @param bool $deletePdfFile (optional) - delete pdf file after download
     * @return void
     *
     * @throws QUI\Exception
     */
    public function download(bool $deletePdfFile = true): void
    {
        if (!$this->created) {
            $file = $this->createPDF();
        } else {
            $file = $this->varDir . $this->documentId . '.pdf';

            if (!file_exists($file)) {
                $file = $this->createPDF();
            }
        }

        $filename = $this->getAttribute('filename');

        if (empty($filename)) {
            $filename = $this->documentId . '_' . date("d_m_Y__H_m") . '.pdf';
        }

        try {
            QUI\Utils\System\File::send($file, 0, $filename);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF download failed:: ' . $Exception->getMessage()
            );

            throw new QUI\Exception([
                'quiqqer/htmltopdf',
                'exception.document.pdf.download.failed'
            ]);
        }

        if ($deletePdfFile) {
            unlink($file);
        }
    }

    /**
     * Return HTML file with PDF header content
     *
     * @return string - path to file
     */
    public function getHeaderHTMLFile(): string
    {
        $file = $this->varDir . 'header_' . $this->documentId . '.html';
        file_put_contents($file, $this->getHeaderHTML());

        return $file;
    }

    /**
     * Return HTML file with PDF header content
     *
     * @return string - path to file
     */
    public function getContentHTMLFile(): string
    {
        $file = $this->varDir . 'body_' . $this->documentId . '.html';
        file_put_contents($file, $this->getContentHTML());

        return $file;
    }

    /**
     * Return HTML file with PDF header content
     *
     * @return string - path to file
     */
    public function getFooterHTMLFile(): string
    {
        $file = $this->varDir . 'footer_' . $this->documentId . '.html';
        file_put_contents($file, $this->getFooterHTML());

        return $file;
    }

    /**
     * Build header html from header settings
     *
     * @return string - complete HTML for PDF header
     */
    public function getHeaderHTML(): string
    {
        $hd = $this->header;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/body.css');
        }

        $header .= '<style>' . $css . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>' . $hd['content'];

        if ($this->getAttribute('foldingMarks')) {
            $body .= '
                <div class="folding-marks">
                    <div class="folding-mark din-5008-f1"></div>
                    <div class="folding-mark din-5008-f2"></div>
                    <div class="folding-mark din-5008-hole"></div>
                </div>
                <style>
                       .folding-marks {
                            height: 100%;
                            left: 0;
                            position: fixed;
                            top: 0;
                            width: 100%;
                       }
                       
                       .folding-mark {
                            background: #000;
                            height: 1px;
                            left: 0;
                            position: absolute;
                            width: 40px;
                       }
                       
                       .din-5008-f1 {
                            background: #000;
                            top: 105mm;
                       }
                       
                       .din-5008-f2 {
                            background: #000;
                            top: 210mm;
                       }
                       
                       .din-5008-hole {
                            top: 148.5mm;
                       }
                </style>
            ';
        }


        $body .= '</body></html>';

        return $this->parseRelativeLinks($header . $body);
    }

    /**
     * Build body html from body settings
     *
     * @return string - complete HTML for PDF body
     */
    public function getContentHTML(): string
    {
        $hd = $this->body;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/body.css');
        }

        $header .= '<style>' . $css . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>' . $hd['content'] . '</body></html>';

        return $this->parseRelativeLinks($header . $body);
    }

    /**
     * Build body html from body settings
     *
     * @param bool $fullHtml (optional) - Return the footer with complete HTML (including DOCTYPE and header);
     * if this is set to false, return footer in a div only.
     *
     * @return string - complete HTML for PDF footer
     */
    public function getFooterHTML(bool $fullHtml = true): string
    {
        $footer = $this->footer;

        $css = $footer['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/body.css');
        }

        if ($fullHtml) {
            $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

            // add css
            $header .= '<style>' . $css . '</style>';

            foreach ($footer['cssFiles'] as $file) {
                $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }

            $header .= '</head>';

            $body = '<body>';
            $body .= $footer['content'];
        } else {
            $body = '<div id="document-body">';
            $body .= '<style>' . $css . '</style>';

            // Special CSS for page counter
            $body .= '<style>
                    #pages_current:after {
                        counter-increment: page;
                        content: counter(page);
                    }                
                </style>';

            foreach ($footer['cssFiles'] as $file) {
                $body .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }
        }

        if ($this->getAttribute('showPageNumbers')) {
            $body .= '<div id="pages">
                        <span id="pages_prefix">' . $this->getAttribute('pageNumbersPrefix') . '</span>
                        <span id="pages_current"></span>
                        <span id="pages_total"></span>
                    </div>';

            if ($fullHtml) {
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
        }

        if ($fullHtml) {
            $body .= '</body></html>';
            $body = $header . $body;
        } else {
            $body .= '</div>';
        }

        return $this->parseRelativeLinks($body);
    }

    /**
     * Parse all relative links and change them to absolute links
     *
     * This is especially relevant for images
     *
     * @param string $str
     * @return string - Modified string
     */
    protected function parseRelativeLinks(string $str): string
    {
        return preg_replace('#=[\'"]\/media\/cache\/#i', '="' . CMS_DIR . 'media/cache/', $str);
    }
}
