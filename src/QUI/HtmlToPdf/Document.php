<?php

/**
 * This file contains \QUI\HtmlToPdf\Document
 */

namespace QUI\HtmlToPdf;

use QUI;

use function array_merge;
use function array_unique;
use function array_values;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_array;
use function mb_substr;
use function pathinfo;
use function preg_replace;
use function property_exists;
use function str_replace;
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
     * @var string
     */
    public readonly string $documentId;

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

    public readonly DocumentOptions $options;

    /**
     * Document constructor.
     *
     * @param DocumentOptions|array|null $options - If array, keys will be mapped to {@see DocumentOptions} properties;
     * If NULL a default options object is created.
     */
    public function __construct(DocumentOptions | array | null $options = null)
    {
        if (is_null($options) || is_array($options)) {
            $this->options = new DocumentOptions();

            if (is_array($options)) {
                foreach ($options as $k => $v) {
                    if (property_exists($this->options, $k)) {
                        $this->options->$k = $v;
                    }
                }
            }
        } else {
            $this->options = $options;
        }

//        $this->setAttributes([
//            'showPageNumbers' => true,
//            'pageNumbersPrefix' => QUI::getLocale()->get('quiqqer/htmltopdf', 'footer.page.prefix'),
//            'filename' => '',
//            'dpi' => 300,
//            'marginTop' => 20,    // mm
//            'marginRight' => 5,     // mm
//            'marginBottom' => 20,    // mm
//            'marginLeft' => 5,     // mm
//            'headerSpacing' => 5,     // should be 5 at minimum
//            'footerSpacing' => 0,
//            'zoom' => 1,
//            'enableForms' => false,
//            'foldingMarks' => false,
//            'disableSmartShrinking' => false
//        ]);
//
//        $this->setAttributes($settings);

//        try {
//            Handler::checkPDFGeneratorBinary();
//        } catch (\Exception $Exception) {
//            QUI\System\Log::writeException($Exception);
//            Handler::sendBinaryWarningMail($Exception->getMessage());
//        }

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
     * @param string $html - HTML will be wrapped by <footer class="{$this->options->cssClassFooter}"></footer>;
     *                       All <footer> tags contained in $html will be replaced by <div>!
     */
    public function setFooterHTML(string $html): void
    {
        $this->footer['content'] = $html;
    }

    /**
     * Set HTML file used for PDF footer area
     *
     * @param string $file - path to html file;
     *                       HTML will be wrapped by <footer class="{$this->options->cssClassFooter}"></footer>;
     *                       All <footer> tags provided in $file will be replaced by <div>!
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
     * @return string - pdf file path
     *
     * @throws QUI\Exception
     * @deprecated This direct call will be removed in the next major release.
     * Please use {@see QUI\HtmlToPdf\Handler::getPdfCreator()} and {@see PdfCreator::createPdf()}
     */
    public function createPDF(): string
    {
        $handler = new Handler();
        return $handler->getPdfCreator()->createPdf($this);

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
    public function createImage(bool $deletePdfFile = true, array $cliParams = [], bool $trim = true): array | string
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
     * @throws QUI\Exception
     * @deprecated This direct call will be removed in the next major release.
     * Please use {@see QUI\HtmlToPdf\Handler::getPdfCreator()} and {@see PdfCreator::createAndDownloadPdf()}
     *
     */
    public function download(bool $deletePdfFile = true): void
    {
        $handler = new Handler();
        $handler->getPdfCreator()->createAndDownloadPdf($this, !$deletePdfFile);
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
     * @param bool $fullHtml (optional) - Return the footer with complete HTML (including DOCTYPE and header);
     * if this is set to false, return footer in a div only. [default: true]
     *
     * @return string - complete HTML for PDF header
     */
    public function getHeaderHTML(bool $fullHtml = true): string
    {
        $header = $this->header;

        $css = $header['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/header.css');
        }

        $content = str_replace(['<header>', '</header>'], ['<div', '</div>'], $header['content']);
        $content = '<header class="' . $this->options->cssClassHeader . '">' . $content . '</header>';

        if ($fullHtml) {
            $head = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

            // add css
            $head .= '<style>' . $css . '</style>';

            foreach ($header['cssFiles'] as $file) {
                $head .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }

            $head .= '</head>';
            $body = $head . '<body>' . $content . '</body></html>';
        } else {
            $body = '<style>' . $css . '</style>';

            foreach ($header['cssFiles'] as $file) {
                $body .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }

            $body .= $content;
        }

        return $this->parseRelativeLinks($body);
//
//
//
//
//        $header = '<!DOCTYPE html>
//                        <html>
//                         <head>
//                            <meta charset="UTF-8">';
//
//        // add css
//        $css = $hd['css'];
//
//        if (empty($css)) {
//            $css = file_get_contents(dirname(__FILE__) . '/default/body.css');
//        }
//
//        $header .= '<style>' . $css . '</style>';
//
//        foreach ($hd['cssFiles'] as $file) {
//            $header .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
//        }
//
//        $header .= '</head>';
//
//        $body = '<body>' . $hd['content'];
//
//
//        $body .= '</body></html>';
//
//        return $this->parseRelativeLinks($header . $body);
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
     * if this is set to false, return footer in a div only. [default: true]
     *
     * @return string - complete HTML for PDF footer
     */
    public function getFooterHTML(bool $fullHtml = true): string
    {
        $footer = $this->footer;
        $css = $footer['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/footer.css');
        }

        $content = str_replace(['<footer', '</footer>'], ['<div', '</div>'], $footer['content']);
        $content = '<footer class="' . $this->options->cssClassFooter . '">' . $content . '</footer>';

        if ($fullHtml) {
            $head = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF-8">';

            // add css
            $head .= '<style>' . $css . '</style>';

            foreach ($footer['cssFiles'] as $file) {
                $head .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }

            $head .= '</head>';

            $body = '<body>';
        } else {
            $body = '<style>' . $css . '</style>';

            foreach ($footer['cssFiles'] as $file) {
                $body .= '<link href="' . $file . '" rel="stylesheet" type="text/css">';
            }
        }

        $body .= $content;

        if ($fullHtml) {
            $body .= '</body></html>';
            $body = $head . $body;
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
