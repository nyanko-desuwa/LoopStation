<?php

use App\Support\EmailBox;

it('normalizes gmail dots and plus tags', function (): void {
    expect(EmailBox::normalize('Test.User+promo@gmail.com'))->toBe('testuser@gmail.com');
    expect(EmailBox::normalize('test.user+promo@googlemail.com'))->toBe('testuser@gmail.com');
});

it('strips plus tags but keeps dots for outlook family', function (): void {
    expect(EmailBox::normalize('test.user+promo@outlook.com'))->toBe('test.user@outlook.com');
    expect(EmailBox::normalize('test.user+promo@hotmail.com'))->toBe('test.user@hotmail.com');
    expect(EmailBox::normalize('test.user+promo@live.com'))->toBe('test.user@live.com');
});

it('strips plus tags for icloud and me domains', function (): void {
    expect(EmailBox::normalize('test.user+promo@icloud.com'))->toBe('test.user@icloud.com');
    expect(EmailBox::normalize('test.user+promo@me.com'))->toBe('test.user@icloud.com');
});

it('strips plus tags for proton domains', function (): void {
    expect(EmailBox::normalize('test.user+promo@proton.me'))->toBe('test.user@proton.me');
    expect(EmailBox::normalize('test.user+promo@protonmail.com'))->toBe('test.user@protonmail.com');
});

it('keeps yahoo and custom domains unchanged apart from trim and lowercase', function (): void {
    expect(EmailBox::normalize('Test.1+promo@Yahoo.com'))->toBe('test.1+promo@yahoo.com');
    expect(EmailBox::normalize('First.Last+tag@Company.com'))->toBe('first.last+tag@company.com');
});

it('returns trimmed lowercase input for non email strings', function (): void {
    expect(EmailBox::normalize('  NOT-AN-EMAIL  '))->toBe('not-an-email');
    expect(EmailBox::normalize('   '))->toBe('');
});
