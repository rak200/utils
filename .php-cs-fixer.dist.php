<?php

declare(strict_types=1);

// Style is Layer 2: the preset, the overrides and the reason for each live in
// vendor/rak200/coding-standard-php. This file only says what to look at.

return (require __DIR__ . '/vendor/rak200/coding-standard-php/.php-cs-fixer.dist.php')
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    );
