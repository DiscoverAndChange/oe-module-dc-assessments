<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

class HTMLSanitizer
{
    const ALLOWED_TAGS = [
        'p', 'span', 'br', 'strong', 'em'
        ,'div','caption'
        ,'table', 'tbody', 'thead', 'tr','th','td'
        ,'b','i','u'
        ,'h1','h2','h3','h4','h5','h6'
        ,'ol','li','ul'
        ,'blockquote','sub','sup','strike','hr'
    ];
    const ALLOWED_ATTRIBUTES = ['class', 'id'];
    const ALLOWED_CLASSES = ['editable', 'exercise-answer', 'table', 'table-bordered', 'table-striped'];

    const ALLOWED_EMAIL_TAGS = ['b', 'i', 'u', 'br'];

    public function sanitize(string $value): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set("HTML.AllowedElements", self::ALLOWED_TAGS);
        $config->set("HTML.AllowedAttributes", self::ALLOWED_ATTRIBUTES);
        $config->set("Attr.AllowedClasses", self::ALLOWED_CLASSES);
        $purify = new \HTMLPurifier($config);
        $purifiedHtml = $purify->purify($value);
        return $purifiedHtml;
    }

    public function stripHTML($contents)
    {
        return strip_tags($contents);
    }

    public function sanitizeEmailHTML(string $value): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set("HTML.AllowedElements", self::ALLOWED_EMAIL_TAGS);
        $config->set("HTML.AllowedAttributes", []);
        $config->set("Attr.AllowedClasses", []);
        $purify = new \HTMLPurifier($config);
        $purifiedHtml = $purify->purify($value);
        return $purifiedHtml;
    }
}
