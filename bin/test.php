<?php

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

require_once dirname(__FILE__, 4).'/header.php';

use QUI\HtmlToPdf\Document;

$User = QUI::getUserBySession();

if (!$User->canUseBackend()) {
    exit;
}

$type = $_GET['type'];

try {
    $Document = new Document([
        'marginTop' => 30, // dies ist variabel durch quiqqerInvoicePdfCreate
//        'marginBottom'  => 10, // dies ist variabel durch quiqqerInvoicePdfCreate
        'filename'  => 'test.pdf',
    ]);

    $Document->setAttribute('marginBottom', 25);
    $Document->setAttribute('marginLeft', 0);
    $Document->setAttribute('marginRight', 0);

    $tplDir = OPT_DIR.'quiqqer/htmltopdf/template/';
    $Engine = QUI::getTemplateManager()->getEngine();

    $Engine->assign([
        'headerImg' => OPT_DIR.'quiqqer/htmltopdf/bin/images/Logo.jpg',
        'bodyImg'   => OPT_DIR.'quiqqer/htmltopdf/bin/images/Readme.jpg'
    ]);

    $Document->setHeaderHTML(
        $Engine->fetch($tplDir.'test.header.html')
    );

    $Document->setContentHTML(
        $Engine->fetch($tplDir.'test.body.html')
    );

    $Document->setFooterHTML(
        $Engine->fetch($tplDir.'test.footer.html')
    );

    if ($type === 'image') {
        $imageFile = $Document->createImage(
            true,
            [
                '-transparent-color',
                '-background white',
                '-alpha remove',
                '-alpha off',
                '-bordercolor white',
                '-border 10'
            ]
        );

        QUI\Utils\System\File::send($imageFile, 0, 'test.jpg');

        exit;
    }

    $Document->download();
} catch (\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
}

exit;
