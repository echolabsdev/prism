<?php

describe('Arch presets', function (): void {
    arch('Security preset')->preset()->security();
    arch('PHP preset')->preset()->php();
    arch('Laravel preset')->preset()->laravel();
});

describe('Custom relaxed preset', function (): void {
    arch('No final classes')
        ->expect('PrismPHP\Prism')
        ->classes()
        ->not
        ->toBeFinal();

    arch('No private methods')
        ->expect('PrismPHP\Prism')
        ->classes()
        ->not
        ->toHavePrivateMethods();
});
