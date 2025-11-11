<?php

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

require_once dirname(__FILE__, 5) . '/header.php';

use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Handler;

$user = QUI::getUserBySession();

if (!$user->canUseBackend()) {
    exit;
}

$type = $_GET['type'];

try {
    $document = new Document([
        'marginTop' => 50, // dies ist variabel durch quiqqerInvoicePdfCreate
//        'marginBottom'  => 10, // dies ist variabel durch quiqqerInvoicePdfCreate
        'filename' => 'test.pdf',
    ]);

    $document->setAttribute('marginBottom', 25);
    $document->setAttribute('marginLeft', 10);
    $document->setAttribute('marginRight', 10);

    $tplDir = OPT_DIR . 'quiqqer/htmltopdf/template/';
    $Engine = QUI::getTemplateManager()->getEngine();

    $Engine->assign([
        'headerImg' => OPT_DIR . 'quiqqer/htmltopdf/bin/images/Logo.jpg',
        'bodyImg' => OPT_DIR . 'quiqqer/htmltopdf/bin/images/Readme.jpg'
    ]);

    $document->setHeaderHTML(
        $Engine->fetch($tplDir . 'test.header.html')
    );

    $document->setContentHTML(
        $Engine->fetch($tplDir . 'test.body.html')
    );

    $document->setFooterHTML(
        $Engine->fetch($tplDir . 'test.footer.html')
    );

    $handler = new Handler();
    $pdfCreator = $handler->getPdfCreator();

    if ($type === 'image') {
        $imageFiles = $pdfCreator->createPdfAndConvertToImage($document);
        QUI\Utils\System\File::send($imageFiles[0], 0, 'test.jpg');
        foreach ($imageFiles as $imageFile) {
            unlink($imageFile);
        }
        exit;
    }

    $pdfCreator->createAndDownloadPdf($document);
} catch (\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
}

exit;
