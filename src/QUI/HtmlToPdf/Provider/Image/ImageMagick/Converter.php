<?php

namespace QUI\HtmlToPdf\Provider\Image\ImageMagick;

use QUI;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageConversionFailedException;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterInterface;
use Symfony\Component\Process\Process;

use function dirname;
use function file_exists;
use function pathinfo;

readonly class Converter implements PdfToImageConverterInterface
{
    public function __construct(
        private string $convertExecutable
    ) {
    }

    /**
     * @inheritDoc
     */
    public function convertPdfToImage(string $pdfFile): array
    {
        $imageFile = dirname($pdfFile)
            . DIRECTORY_SEPARATOR
            . pathinfo($pdfFile, PATHINFO_FILENAME)
            . '.jpg';

        $command = [
            $this->convertExecutable,

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
         */
        $imageFileInfo = pathinfo($imageFile);
        // @phpstan-ignore offsetAccess.notFound ('extension' property always exists)
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
