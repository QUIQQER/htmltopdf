<?php

namespace QUI\HtmlToPdf;

use QUI;

use function file_exists;
use function file_get_contents;
use function gettype;
use function is_array;
use function property_exists;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Document extends QUI\QDOM
{
    /**
     * Unique document id
     *
     * @var string
     */
    public readonly string $documentId;

    /**
     * Var directory of quiqqer/htmltopdf package
     *
     * @var string|null
     */
    private ?string $varDir = null;

    /**
     * Header data for PDF conversion
     * @var array<string,mixed>
     */
    private array $header = [
        'css' => '',
        'cssFiles' => [],
        'content' => '',
        'htmlFile' => false
    ];

    /**
     * Content (body) data for PDF conversion
     * @var array<string,mixed>
     */
    private array $body = [
        'css' => '',
        'cssFiles' => [],
        'content' => ''
    ];

    /**
     * Footer data for PDF conversion
     * @var array<string,mixed>
     */
    private array $footer = [
        'css' => '',
        'cssFiles' => [],
        'content' => ''
    ];

    public readonly DocumentOptions $options;

    /**
     * @param DocumentOptions|array<string,string|bool|numeric>|null $options - If array, keys will be mapped to {@see DocumentOptions} properties;
     * If NULL a default options object is created.
     */
    public function __construct(DocumentOptions | array | null $options = null)
    {
        if (is_null($options)) {
            $this->options = new DocumentOptions();
        } elseif (is_array($options)) {
            $this->options = new DocumentOptions($options);
        } else {
            $this->options = $options;
        }

        $this->documentId = uniqid();

        try {
            $Package = QUI::getPackage('quiqqer/htmltopdf');
            $this->varDir = $Package->getVarDir();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * @inheritDoc
     *
     * This also sets corresponding {@see DocumentOptions} properties for this document if available.
     */
    public function setAttribute(string $name, mixed $value): void
    {
        parent::setAttribute($name, $value);

        if (property_exists($this->options, $name) && gettype($value) === gettype($this->options->$name)) {
            $this->options->$name = $value;
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
     * Please use {@see QUI\HtmlToPdf\Handler::getPdfCreator()} with {@see PdfCreator::createPdf()}
     */
    public function createPDF(): string
    {
        trigger_error(
            "Please use QUI\HtmlToPdf\Handler::getPdfCreator()->createPdf() instead of"
            . " QUI\HtmlToPdf\Document::createPDF()",
            E_USER_DEPRECATED
        );

        $handler = new Handler();
        return $handler->getPdfCreator()->createPdf($this);
    }

    /**
     * @param bool $deletePdfFile
     * @param array<string> $cliParams (optional) - Additional CLI params for the "convert" command [default: no additional params]
     * @param bool $trim (optional) - Trim margin of PDF file before generating image [default: true]
     * @return string|array<string> - File to generated image or array with image files if multiple images are generated
     *
     * @throws QUI\Exception
     * @deprecated This direct call will be removed in the next major release.
     *  Please use {@see QUI\HtmlToPdf\Handler::getPdfCreator()} with {@see PdfCreator::createPdfAndConvertToImage()}
     */
    public function createImage(bool $deletePdfFile = true, array $cliParams = [], bool $trim = true): array | string
    {
        trigger_error(
            "Please use QUI\HtmlToPdf\Handler::getPdfCreator()->createPdfAndConvertToImage() instead of"
            . " QUI\HtmlToPdf\Document::createImage()",
            E_USER_DEPRECATED
        );

        $handler = new Handler();
        return $handler->getPdfCreator()->createPdfAndConvertToImage($this);
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
        trigger_error(
            "Please use QUI\HtmlToPdf\Handler::getPdfCreator()->createAndDownloadPdf() instead of"
            . " QUI\HtmlToPdf\Document::download()",
            E_USER_DEPRECATED
        );

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
        $content = '<header class="' . $this->options->cssClassHeaderContainer . '">' . $content . '</header>';

        if ($fullHtml) {
            $head = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF - 8">';

            // add css
            $head .= '<style>' . $css . '</style>';

            foreach ($header['cssFiles'] as $file) {
                $head .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
            }

            $head .= '</head>';
            $body = $head . '<body>' . $content . '</body></html>';
        } else {
            $body = '<style>' . $css . '</style>';

            foreach ($header['cssFiles'] as $file) {
                $body .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
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
//                            <meta charset="UTF - 8">';
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
//            $header .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
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
                            <meta charset="UTF - 8">';

        // add css
        $css = $hd['css'];

        if (empty($css)) {
            $css = file_get_contents(dirname(__FILE__) . '/default/body.css');
        }

        $header .= '<style>' . $css . '</style>';

        foreach ($hd['cssFiles'] as $file) {
            $header .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
        }

        $header .= '</head>';

        $body = '<body class="' . $this->options->cssClassBodyContainer . '">' . $hd['content'] . '</body></html>';

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
        $content = '<footer class="' . $this->options->cssClassFooterContainer . '">' . $content . '</footer>';

        if ($fullHtml) {
            $head = '<!DOCTYPE html>
                        <html>
                         <head>
                            <meta charset="UTF - 8">';

            // add css
            $head .= '<style>' . $css . '</style>';

            foreach ($footer['cssFiles'] as $file) {
                $head .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
            }

            $head .= '</head>';
            $body = '<body>';
        } else {
            $body = '<style>' . $css . '</style>';

            foreach ($footer['cssFiles'] as $file) {
                $body .= '<link href="' . $file . '" rel="stylesheet" type="text / css">';
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
    private function parseRelativeLinks(string $str): string
    {
        $replaced = preg_replace('#=[\'"]\/media\/cache\/#i', '="' . CMS_DIR . 'media/cache/', $str);
        return $replaced ?: $str;
    }
}
