<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Unit;

use Asignua\FilamentTranslationManager\Support\KeyResolver;
use PHPUnit\Framework\TestCase;

class KeyResolverTest extends TestCase
{
    public function test_a_known_group_splits_at_the_first_dot(): void
    {
        $resolver = new KeyResolver(['auth', 'messages']);

        $this->assertSame(['auth', 'failed'], $resolver->resolve('auth.failed'));
        $this->assertSame(['messages', 'checkout.title'], $resolver->resolve('messages.checkout.title'));
    }

    public function test_an_unknown_prefix_is_a_json_key_even_with_dots(): void
    {
        $resolver = new KeyResolver(['auth']);

        $this->assertSame(['*', 'Mr. Smith'], $resolver->resolve('Mr. Smith'));
        $this->assertSame(['*', 'e.g. this'], $resolver->resolve('e.g. this'));
        $this->assertSame(['*', 'Hello.'], $resolver->resolve('Hello.'));
        $this->assertSame(['*', 'auth.'], $resolver->resolve('auth.'));
        $this->assertSame(['*', 'Add to basket'], $resolver->resolve('Add to basket'));
    }

    public function test_namespaced_keys_are_not_resolvable(): void
    {
        $this->assertNull((new KeyResolver(['pkg']))->resolve('pkg::group.key'));
        $this->assertNull((new KeyResolver([]))->resolve('a::b'));
        $this->assertNull((new KeyResolver([]))->resolve(''));
    }

    public function test_address_is_the_reverse(): void
    {
        $this->assertSame('Hello', KeyResolver::address('*', 'Hello'));
        $this->assertSame('auth.failed', KeyResolver::address('auth', 'failed'));
    }
}
