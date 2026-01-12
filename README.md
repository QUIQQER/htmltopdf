![QUIQQER HTML to PDF](bin/images/Readme.jpg)

QUIQQER HTML to PDF
========

This plugin allows the conversion from HTML to PDF. Set separate HTML files for the PDF header, body and footer.
Additionally, PDF files can be directly converted to images.

Package Name:

    quiqqer/htmltopdf

Features
--------
* Convert HTML to PDF files
* Convert PDF files to image(s)
* Use simple HTML and CSS to style your PDFs
* Separate HTML files and CSS files for PDF header, body and footer (optional)
* Show page numbers in your PDF footer
* Comes with two providers:
  * mpdf (https://mpdf.github.io/) - runs natively in PHP and does not require external dependencies
  * chrome-headless (https://github.com/chrome-php/chrome) - runs in a headless Chrome browser (requires Chrome 65+ to be installed)

Installation
------------
The Package Name is: quiqqer/htmltopdf

Usage
----------

```php
$document = new \QUI\HtmlToPdf\Document();

$document->setHeaderHTML('<div class="header-test"><p>I am a header</p></div>');

$document->setContentHTML('<div class="body-test">I am THE body</div>');
$document->setContentCSS('.body-test { color: #ABC123; }');
$document->addContentCSSFile('/tmp/test.css');

$document->setFooterHTML('<div class="footer-test">I am a footer</div>');
$document->setFooterCSS('.footer-test { color: #CFE123; }');

$handler = new \QUI\HtmlToPdf\Handler();
$pdfCreator = $handler->getPdfCreator();

// create PDF file
$pdfFile = $pdfCreator->createPdf($document);

// Download (and save) PDF file
$pdfFile = $pdfCreator->createAndDownloadPdf($document, true);

// Convert PDF to imgage(s)
$pdfAsImages = $pdfCreator->createPdfAndConvertToImage($document);
```

## Version 4

Version 4 constitutes a major overhaul and cleanup of the plugin code base.

Read everything important in the wiki:  
https://dev.quiqqer.com/quiqqer/htmltopdf/-/wikis/version-4

Contribute
----------
- Project: https://dev.quiqqer.com/quiqqer/htmltopdf
- Issue Tracker: https://dev.quiqqer.com/quiqqer/htmltopdf/issues
- Source Code: https://dev.quiqqer.com/quiqqer/htmltopdf/tree/master

Support
-------
If you found any errors or have wishes or suggestions for improvement,
please contact us by email at support@pcsg.de.

We will transfer your message to the responsible developers.

License
-------
PCSG QL-1.0, CC BY-NC-SA 4.0
