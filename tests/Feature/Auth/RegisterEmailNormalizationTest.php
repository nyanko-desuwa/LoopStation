<?php

use App\Models\User;
use App\Support\EmailBox;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects gmail dot variants as duplicate mailboxes during registration', function (): void {
    User::factory()->create([
        'email' => 'test.1@gmail.com',
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Another User',
        'phone' => '0912345678',
        'email' => 'test1@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertDatabaseCount('users', 1);
});

it('allows yahoo dot variants because they are different mailboxes', function (): void {
    User::factory()->create([
        'email' => 'test.1@yahoo.com',
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Another User',
        'phone' => '0912345678',
        'email' => 'test1@yahoo.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('users', 2);
});

it('rejects invalid email formats with strict rfc validation', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Invalid Email User',
        'phone' => '0912345678',
        'email' => 'test@@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
});

it('normalizes provider mailbox variants consistently', function (): void {
    expect(EmailBox::normalize('Test.User+promo@gmail.com'))->toBe('testuser@gmail.com');
    expect(EmailBox::normalize('test.user+promo@googlemail.com'))->toBe('testuser@gmail.com');
    expect(EmailBox::normalize('test.user+promo@outlook.com'))->toBe('test.user@outlook.com');
    expect(EmailBox::normalize('test.user+promo@yahoo.com'))->toBe('test.user+promo@yahoo.com');
});
