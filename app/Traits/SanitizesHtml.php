<?php

namespace App\Traits;

use Mews\Purifier\Facades\Purifier;

trait SanitizesHtml
{
    /**
     * Sanitize HTML content using HTMLPurifier library.
     * This is much more robust than regex-based filtering.
     *
     * Allows safe HTML tags (p, br, strong, em, ul, ol, li, h1-h6, a, img, table, etc.)
     * while removing all dangerous content (script, iframe, on* events, javascript: URIs, etc.)
     */
    protected function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Use HTMLPurifier for robust sanitization
        // It handles all edge cases that regex-based filtering misses
        return Purifier::clean($html, [
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,p,br,strong,b,em,i,u,s,strike,del,a[href|title|target|rel],img[src|alt|width|height|style|class],ul,ol,li,blockquote,pre,code,table,thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],div[class|style],span[class|style],hr,sub,sup,figure,figcaption',
            'HTML.TargetBlank' => true,
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
            'Attr.AllowedFrameTargets' => ['_blank'],
            'CSS.AllowedProperties' => 'text-align,color,background-color,font-weight,font-style,text-decoration,margin,padding,width,height,max-width,display,border,border-collapse',
            'AutoFormat.RemoveEmpty' => false,
        ]);
    }

    protected function sanitizeFields(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->sanitizeHtml($data[$field]);
            }
        }
    }
}
