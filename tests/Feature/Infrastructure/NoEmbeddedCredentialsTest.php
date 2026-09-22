<?php

use Illuminate\Support\Facades\File;

it('does not embed long literal API keys in release PHP source', function () {
    $roots = [
        app_path(),
        database_path(),
        config_path(),
        base_path('routes'),
    ];

    $pattern = "/['\"]api_key['\"]\s*=>\s*['\"][A-Za-z0-9_\\-]{24,}['\"]/";

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            expect(
                File::get($file->getPathname()),
                'Embedded API credential found in ' . $file->getRelativePathname()
            )->not->toMatch($pattern);
        }
    }
});


it('does not seed fixed literal passwords', function () {
    $pattern = "/Hash::make\(\s*['\"][^'\"]+['\"]\s*\)/";

    foreach (File::allFiles(database_path('seeders')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        expect(
            File::get($file->getPathname()),
            'Fixed password found in ' . $file->getRelativePathname()
        )->not->toMatch($pattern);
    }
});
