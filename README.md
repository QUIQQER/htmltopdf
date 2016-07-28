HTML zu PDF Converter
========



Paketname:

    quiqqer/htmltopdf


Features (Funktionen)
--------

Wandelt HTML-Code in PDF-Dateien um.

Installation
------------

Der Paketname ist: quiqqer/htmltopdf

Abhängigkeiten
--------------

* **wkhtmltopdf** - wird im Paket mitgeliefert; s. http://wkhtmltopdf.org/downloads.html
* Weitere benötigte Pakete, die **wkhtmltopdf** voraussetzt:
  * `sudo apt-get install zlib1g-dev`
  * `sudo apt-get install fontconfig fontconfig-config`
  * `sudo apt-get install libfreetype6`
  * `sudo apt-get install libx11-dev libxext-dev libxrender-dev`

Mitwirken
----------

- Issue Tracker: https://dev.quiqqer.com/quiqqer/htmltopdf/issues
- Source Code: https://dev.quiqqer.com/quiqqer/htmltopdf/tree/master


Support
-------

Falls Sie ein Fehler gefunden haben, oder Verbesserungen wünschen,
dann können Sie gerne an support@pcsg.de eine E-Mail schreiben.


Lizenz
-------


Entwickler
--------

Patrick Müller (p.mueller@pcsg.de)


Beispiel
--------
```php
$Document = new \QUI\HtmlToPdf\Document();

$Document->setHeaderHTML('<div class="header-test"><p>Ich bin ein Header</p></div>');

$Document->setContentHTML('<div class="body-test">Ich bin DER Body</div>');
$Document->setContentCSS('.body-test { color: #ABC123; }');
$Document->addContentCSSFile('/tmp/test.css');

$Document->setFooterHTML('<div class="footer-test">Ich bin ein Footer</div>');
$Document->setFooterCSS('.footer-test { color: #CFE123; }');

// erstellt PDF datei
$pdfFile = $Document->createPDF();

// Download der Datei
$Document->download();
```

Settings
--------
s. https://dev.quiqqer.com/quiqqer/htmltopdf/wikis/settings
