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
     * Var directory of quiqqer/htmltopdf package
     *
     * @var string
     */
    protected $varDir = null;

    /**
     * Header data for PDF conversion
     *
     * @var array
     */
    protected $header = [
        'css'      => '',
        'cssFiles' => [],
        'content'  => '',
        'htmlFile' => false
    ];

    /**
     * Content (body) data for PDF conversion
     *
     * @var array
     */
    protected $body = [
        'css'      => '',
        'cssFiles' => [],
        'content'  => ''
    ];

    /**
     * Footer data for PDF conversion
     *
     * @var array
     */
    protected $footer = [
        'css'      => '',
        'cssFiles' => [],
        'content'  => ''
    ];

    /**
     * Document constructor.
     *
     * @param array $settings (optional)
     */
    public function __construct($settings = [])
    {
        $this->setAttributes([
            'showPageNumbers'   => true,
            'pageNumbersPrefix' => QUI::getLocale()->get('quiqqer/htmltopdf', 'footer.page.prefix'),
            'filename'          => '',
            'dpi'               => 300,
            'marginTop'         => 20,    // mm
            'marginRight'       => 5,     // mm
            'marginBottom'      => 20,    // mm
            'marginLeft'        => 5,     // mm
            'headerSpacing'     => 5,     // should be 5 at minimum
            'footerSpacing'     => 0,
            'zoom'              => 1,
            'enableForms'       => false
        ]);

        $this->setAttributes($settings);

        try {
            Handler::checkPDFGeneratorBinary();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            Handler::sendBinaryWarningMail($Exception->getMessage());
        }

        $this->documentId      = uniqid();
        $this->converterBinary = Handler::getPDFGeneratorBinaryPath();

        try {
            $Package      = QUI::getPackage('quiqqer/htmltopdf');
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
    public function setHeaderHTML($html)
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
    public function setHeaderHTMLFile($file)
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
    public function setContentHTML($html)
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
    public function setContentHTMLFile($file)
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
    public function setFooterHTML($html)
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
    public function setFooterHTMLFile($file)
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
    public function createPDF()
    {
        $varDir = $this->varDir;

        // Determine library path
        $cmdPrefix = '';

        try {
            $Conf    = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
            $libPath = $Conf->get('settings', 'lib_path');

            if (is_string($libPath)) {
                $libPath = trim($libPath);
            }

            if (!empty($libPath)) {
                $cmdPrefix = 'export LD_LIBRARY_PATH='.$libPath.'; ';
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $cmd = $cmdPrefix.$this->converterBinary.' ';

        $cmd .= ' -T '.$this->getAttribute('marginTop').'mm';
        $cmd .= ' -R '.$this->getAttribute('marginRight').'mm';
        $cmd .= ' -B '.$this->getAttribute('marginBottom').'mm';
        $cmd .= ' -L '.$this->getAttribute('marginLeft').'mm';

        if ($this->getAttribute('enableForms') === true) {
            $cmd .= ' --enable-forms';
        }

        $headerHtmlFile = false;
        $footerHtmlFile = false;

        if (!empty($this->header['content'])) {
            $cmd .= ' --header-spacing '.$this->getAttribute('headerSpacing');

            $headerHtmlFile = $this->getHeaderHTMLFile();

            $cmd .= ' --header-html "'.$headerHtmlFile.'"';
//            $cmd .= ' --header-line';
        }

        if (!empty($this->footer['content'])
            || $this->getAttribute('showPageNumbers')
        ) {
            $cmd .= ' --footer-spacing '.$this->getAttribute('footerSpacing');

            $footerHtmlFile = $this->getFooterHTMLFile();

            $cmd .= ' --footer-html "'.$footerHtmlFile.'"';
        }

        $cmd .= ' --dpi '.(int)$this->getAttribute('dpi');
        $cmd .= ' --zoom '.(float)$this->getAttribute('zoom');

        $bodyHtmlFile = $this->getContentHTMLFile();

        $pdfFile = $varDir.$this->documentId.'.pdf';

        $cmd .= ' '.$bodyHtmlFile.' '.$pdfFile;

        exec($cmd.' 2> /dev/null', $output, $exitStatus);

        if ($exitStatus !== 0) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF conversion failed:: '.json_encode($output)
                .' -- PDF create cmd: > '.$cmd.' <'
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
    public function createImage($deletePdfFile = true, $cliParams = [], $trim = true)
    {
        $pdfFile   = $this->createPDF();
        $imageFile = \mb_substr($pdfFile, 0, -4).'.jpg';

        $pdfFileLine = '\''.$pdfFile.'\'';

        if ($trim) {
            $pdfFileLine = '-trim '.$pdfFileLine;
        }

        $cliParams = \array_merge(
            $cliParams,
            [
                '-density 300',
                $pdfFileLine,
                '-quality 100',
                '-resize 2480x3508',
                '\''.$imageFile.'\'',
            ]
        );

        $command = 'convert';

        foreach ($cliParams as $param) {
            $param = \trim($param);

            if (empty($param)) {
                continue;
            }

            $command .= ' '.$param;
        }

        \system($command);

        // Delete source PDF
        if ($deletePdfFile && \file_exists($pdfFile)) {
            \unlink($pdfFile);
        }

        if (!\file_exists($imageFile)) {
            /**
             * Check if the PDF was split into multiple images.
             * In this case the images need to be appended to one single image.
             */
            $imageFileInfo  = \pathinfo($imageFile);
            $imageFileExt   = $imageFileInfo['extension'];
            $imageFileDir   = $imageFileInfo['dirname'].'/';
            $imageFileNoExt = $imageFileDir.$imageFileInfo['filename'];

            if (!\file_exists($imageFileNoExt.'-0.'.$imageFileExt)) {
                throw new QUI\Exception(
                    'Could not create image from pdf. Command: "'.$command.'".'
                );
            }

            $imageFiles = [];
            $imageNo    = 0;

            do {
                $imageFileNumbered = $imageFileNoExt.'-'.$imageNo++.'.'.$imageFileExt;

                if (!\file_exists($imageFileNumbered)) {
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
    public function download($deletePdfFile = true)
    {
        if (!$this->created) {
            $file = $this->createPDF();
        } else {
            $file = $this->varDir.$this->documentId.'.pdf';

            if (!file_exists($file)) {
                $file = $this->createPDF();
            }
        }

        $filename = $this->getAttribute('filename');

        if (empty($filename)) {
            $filename = $this->documentId.'_'.date("d_m_Y__H_m").'.pdf';
        }

        try {
            QUI\Utils\System\File::send($file, 0, $filename);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                'quiqqer/htmltopdf PDF download failed:: '.$Exception->getMessage()
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
    public function getHeaderHTMLFile()
    {
        $file = $this->varDir.'header_'.$this->documentId.'.html';
        file_put_contents($file, $this->getHeaderHTML());

        return $file;
    }

    /**
     * Return HTML file with PDF header content
     *
     * @return string - path to file
     */
    public function getContentHTMLFile()
    {
        $file = $this->varDir.'body_'.$this->documentId.'.html';
        file_put_contents($file, $this->getContentHTML());

        return $file;
    }

    /**
     * Return HTML file with PDF header content
     *
     * @return string - path to file
     */
    public function getFooterHTMLFile()
    {
        $file = $this->varDir.'footer_'.$this->documentId.'.html';
        file_put_contents($file, $this->getFooterHTML());

        return $file;
    }

    /**
     * Build header html from header settings
     *
     * @return string - complete HTML for PDF header
     */
    public function getHeaderHTML()
    {
        $hd = $this->header;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__).'/default/body.css');
        }

        $header .= '<style>'.$css.'</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="'.$file.'" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>'.$hd['content'].'</body></html>';

        return $this->parseRelativeLinks($header.$body);
    }

    /**
     * Build body html from body settings
     *
     * @return string - complete HTML for PDF body
     */
    public function getContentHTML()
    {
        $hd = $this->body;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__).'/default/body.css');
        }

        $header .= '<style>'.$css.'</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="'.$file.'" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>'.$hd['content'].'</body></html>';

        return $this->parseRelativeLinks($header.$body);
    }

    /**
     * Build body html from body settings
     *
     * @return string - complete HTML for PDF footer
     */
    public function getFooterHTML()
    {
        $hd = $this->footer;

        $header = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__).'/default/body.css');
        }

        $header .= '<style>'.$css.'</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="'.$file.'" rel="stylesheet" type="text/css">';
        }

        $header .= '</head>';

        $body = '<body>';
        $body .= $hd['content'];

        if ($this->getAttribute('showPageNumbers')) {
            $body .= '<div id="pages">
                        <span id="pages_prefix">'.$this->getAttribute('pageNumbersPrefix').'</span>
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

        return $this->parseRelativeLinks($header.$body);
    }

    /**
     * Parse all relative links and change them to absolute links
     *
     * This is especially relevant for images
     *
     * @param string $str
     * @return string - Modified string
     */
    protected function parseRelativeLinks(string $str)
    {
        return \preg_replace('#=[\'"]\/media\/cache\/#i', '="'.CMS_DIR.'media/cache/', $str);
    }
}
