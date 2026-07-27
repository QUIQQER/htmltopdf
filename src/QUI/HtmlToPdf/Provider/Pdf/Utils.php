<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

use function is_string;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;

class Utils
{
    /**
     * Extract element content from string containing HTML.
     *
     * @param string $html
     * @param string $element
     * @return string - Everything inside <$element>
     */
    public static function extractContentFromHtmlElement(string $html, string $element = 'body'): string
    {
        // Extract content between <$element> tags if present
        if (preg_match('/<' . $element . '[^>]*>(.*?)<\/' . $element . '>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }


    /**
     * Remove element (including everything inside) from  HTML string.
     *
     * @param string $html
     * @param string $element
     * @return string
     */
    public static function removeElementFromHtml(string $html, string $element): string
    {
        $replaced = preg_replace('/<' . $element . '[^>]*>.*?<\/' . $element . '>/si', '', $html);

        if (is_string($replaced)) {
            return $replaced;
        }

        return $html;
    }

    /**
     * Put a given string inside and at the end of a given HTML element.
     *
     * @param string $html
     * @param string $element - Element name without <> (e.g. "body")
     * @param string $string - HTML to be wrappted by $element
     * @return string
     */
    public static function appendStringInHtmlElement(string $html, string $element, string $string): string
    {
        $element = preg_quote($element, '/');
        $replaced = preg_replace_callback(
            '/(<' . $element . '\b[^>]*>)(.*?)(<\/' . $element . '\s*>)/is',
            static fn(array $matches): string => $matches[1] . $matches[2] . $string . $matches[3],
            $html
        );

        if (!is_string($replaced)) {
            return $html;
        }

        return $replaced;
    }

    /**
     * Put a given string inside and at the beginnging of a given HTML element.
     *
     * @param string $html
     * @param string $element - Element name without <> (e.g. "body")
     * @param string $string - HTML to be wrappted by $element
     * @return string
     */
    public static function prependStringInHtmlElement(string $html, string $element, string $string): string
    {
        $element = preg_quote($element, '/');
        $replaced = preg_replace_callback(
            '/(<' . $element . '\b[^>]*>)(.*?)(<\/' . $element . '\s*>)/is',
            static fn(array $matches): string => $matches[1] . $string . $matches[2] . $matches[3],
            $html
        );

        if (!is_string($replaced)) {
            return $html;
        }

        return $replaced;
    }
}
