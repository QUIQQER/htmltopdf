<?php

namespace QUI\HtmlToPdf\Provider\Image\ImageMagick;

use QUI;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageConversionFailedException;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterInterface;
use Symfony\Component\Process\Process;

use function basename;
use function file_exists;
use function pathinfo;

readonly class Converter implements PdfToImageConverterInterface
{
    public function __construct(
        private string $converterBinary
    ) {
    }

    /**
     * @inheritDoc
     */
    public function convertPdfToImage(string $pdfFile): array
    {
        $imageFile = basename($pdfFile, '.pdf') . '.jpg';

        $command = [
            $this->converterBinary,

            '-transparent-color',
            'white',

            '-background',
            'white',

            '-alpha',
            'remove',

            '-alpha',
            'off',

            '-bordercolor',
            'white',

            '-border',
            '10',

            '-density',
            '300',

            '-trim',
            $pdfFile,

            '-quality',
            '100',

            '-resize',
            '2480x3508', // DIN A4

            $imageFile // result file
        ];

        $process = new Process($command);

        try {
            $process->mustRun();
        } catch (\Exception $exception) {
            QUI\System\Log::writeException($exception);
            throw new PdfToImageConversionFailedException(
                $exception->getMessage(),
                $exception->getCode(),
                [
                    'pdfFile' => $pdfFile,
                    'errorOutput' => $process->getErrorOutput()
                ]
            );
        }

        // Single image
        if (file_exists($imageFile)) {
            return [$imageFile];
        }

        /**
         * Check if the PDF was split into multiple images.
         * In this case the images need to be appended to one single image.
         */
        $imageFileInfo = pathinfo($imageFile);
        $imageFileExt = $imageFileInfo['extension'];
        $imageFileDir = $imageFileInfo['dirname'] . '/';
        $imageFileNoExt = $imageFileDir . $imageFileInfo['filename'];

        if (!file_exists($imageFileNoExt . '-0.' . $imageFileExt)) {
            throw new PdfToImageConversionFailedException(
                'Could not create image from pdf. Result files not found.',
                0,
                [
                    'pdfFile' => $pdfFile,
                    'errorOutput' => $process->getErrorOutput()
                ]
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
}
