<?php

namespace App\Support;

class EmailBox
{
    private const PROVIDERS = [
        'gmail.com' => [
            'canonical_domain' => 'gmail.com',
            'strip_dots' => true,
            'strip_plus' => true,
        ],
        'googlemail.com' => [
            'canonical_domain' => 'gmail.com',
            'strip_dots' => true,
            'strip_plus' => true,
        ],
        'outlook.com' => [
            'canonical_domain' => 'outlook.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'hotmail.com' => [
            'canonical_domain' => 'hotmail.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'live.com' => [
            'canonical_domain' => 'live.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'icloud.com' => [
            'canonical_domain' => 'icloud.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'me.com' => [
            'canonical_domain' => 'icloud.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'proton.me' => [
            'canonical_domain' => 'proton.me',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
        'protonmail.com' => [
            'canonical_domain' => 'protonmail.com',
            'strip_dots' => false,
            'strip_plus' => true,
        ],
    ];

    public static function normalize(string $email): string
    {
        $email = strtolower(trim($email));

        if ($email === '' || ! str_contains($email, '@')) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $rules = self::PROVIDERS[$domain] ?? null;

        if ($rules === null) {
            return $localPart.'@'.$domain;
        }

        if ($rules['strip_plus']) {
            $localPart = preg_replace('/\+.*$/', '', $localPart) ?? $localPart;
        }

        if ($rules['strip_dots']) {
            $localPart = str_replace('.', '', $localPart);
        }

        return $localPart.'@'.$rules['canonical_domain'];
    }
}
