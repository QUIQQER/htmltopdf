<?php

namespace QUI\HtmlToPdf;

use QUI;
use Smarty_Internal_Template;
use Throwable;

use function base64_encode;
use function file_exists;
use function file_get_contents;
use function is_readable;

class SmartyFunctions
{
    /**
     * @param array<string,mixed> $params
     * @param Smarty_Internal_Template $smarty
     * @return string
     */
    public static function imageBase64(array $params, Smarty_Internal_Template $smarty): string
    {
        if (empty($params['image'])) {
            QUI\System\Log::addWarning(
                "\$params does not container 'image'"
            );
            return '';
        }

        $image = $params['image'];

        if (is_string($image)) {
            if (!file_exists($image) || !is_readable($image)) {
                QUI\System\Log::addWarning(
                    "\$params['image'] does not exist or is not readable."
                );
                return '';
            }

            $mimeType = mime_content_type($image);
            $imagerFileContent = file_get_contents($image);

            if ($imagerFileContent === false) {
                QUI\System\Log::addWarning(
                    "{$params['image']} file content could not be read."
                );
                return '';
            }

            return "data:" . $mimeType . ";base64," . base64_encode($imagerFileContent);
        } elseif (!($image instanceof QUI\Projects\Media\Image)) {
            QUI\System\Log::addWarning(
                "\$params['image'] is not a " . QUI\Projects\Media\Image::class . "."
            );
            return '';
        }

        $mimeType = $image->getAttribute('mime_type');

        if (empty($mimeType)) {
            QUI\System\Log::addWarning(
                "\$params does not contain mime type information"
            );
            return '';
        }

        $fullImgPath = $image->getFullPath();

        try {
            $width = $image->getWidth();
            $height = $image->getHeight();

            if (
                !empty($params['width']) &&
                (
                    $params['width'] < $width ||
                    empty($width)
                )
            ) {
                $width = $params['width'];
            }

            if (
                !empty($params['height']) &&
                (
                    $params['height'] < $height ||
                    empty($height)
                )
            ) {
                $height = $params['height'];
            }

            $fullImgPathResized = $image->createResizeCache($width, $height);

            if (!is_string($fullImgPathResized)) {
                QUI\System\Log::addWarning(
                    "Image resize failed",
                    [
                        'image' => $image,
                        'width' => $width,
                        'height' => $height,
                        'fullImgPath' => $fullImgPath
                    ]
                );
            } else {
                $fullImgPath = $fullImgPathResized;
            }
        } catch (\Exception $exception) {
            QUI\System\Log::writeException($exception);
            return '';
        }

        // Convert SVG to PNG
        if (!empty($params['svgtopng']) && str_contains($fullImgPath, '.svg')) {
            $pngImage = $fullImgPath . '.png';

            if (file_exists($pngImage)) {
                $src = $pngImage;
                $mimeType = 'image/png';
            } elseif (class_exists('\Imagick')) {
                $svg = file_get_contents($fullImgPath);

                if ($svg === false) {
                    QUI\System\Log::addWarning(
                        "Image resize failed. SVG file not found / readable.",
                        [
                            'image' => $image,
                            'width' => $width,
                            'height' => $height,
                            'fullImgPath' => $fullImgPath
                        ]
                    );
                    return '';
                }

                try {
                    $im = new \Imagick();
                    $im->readImageBlob($svg);
                    $im->setImageBackgroundColor(new \ImagickPixel('transparent'));
                    $im->setImageFormat("png24");
                    $im->writeImage($pngImage);
                    $im->clear();
                    $im->destroy();

                    $src = $pngImage;
                    $mimeType = 'image/png';
                } catch (Throwable $exception) {
                    QUI\System\Log::writeException($exception);
                    return '';
                }
            } else {
                $src = $fullImgPath;
            }

            $imageFileContent = file_get_contents($src);
        } else {
            $imageFileContent = file_get_contents($fullImgPath);
        }

        if ($imageFileContent === false) {
            QUI\System\Log::addWarning(
                "Cannot render image. Image file not found / readable.",
                [
                    'image' => $image,
                    'width' => $width,
                    'height' => $height,
                    'fullImgPath' => $fullImgPath
                ]
            );
            return '';
        }

        return "data:" . $mimeType . ";base64," . base64_encode($imageFileContent);
    }
}
