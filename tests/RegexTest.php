<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Regex;
use RuntimeException;

final class RegexTest extends TestCase {
    public function testIsAcceptsValidPatterns(): void {
        $this->assertTrue(Regex::is('/foo/'));
        $this->assertTrue(Regex::is('/^\d+$/u'));
        $this->assertTrue(Regex::is('#https?://\S+#i'));
        $this->assertTrue(Regex::is('/(?<name>\w+)/'));
    }

    public function testIsRejectsInvalidPatterns(): void {
        $this->assertFalse(Regex::is('not-a-pattern'));
        $this->assertFalse(Regex::is('/['));
        $this->assertFalse(Regex::is('/foo'));
        $this->assertFalse(Regex::is(''));
    }

    public function testMatches(): void {
        $this->assertTrue(Regex::matches('/foo/', 'foobar'));
        $this->assertFalse(Regex::matches('/baz/', 'foobar'));
    }

    public function testMatchReturnsCapturedGroups(): void {
        $result = Regex::match('/(\w+)@(\w+)/', 'hello user@host more');
        $this->assertSame('user@host', $result[0]);
        $this->assertSame('user', $result[1]);
        $this->assertSame('host', $result[2]);
    }

    public function testMatchThrowsWhenNoMatch(): void {
        $this->expectException(RuntimeException::class);
        Regex::match('/xyz/', 'abc');
    }

    public function testMatchOrNullReturnsNullWhenNoMatch(): void {
        $this->assertNull(Regex::matchOrNull('/xyz/', 'abc'));
        $this->assertNotNull(Regex::matchOrNull('/abc/', 'abc'));
    }

    public function testMatchAll(): void {
        $result = Regex::matchAll('/\d+/', 'a1 b22 c333');
        $this->assertSame(['1', '22', '333'], $result[0]);
    }

    public function testReplace(): void {
        $this->assertSame('XbX', Regex::replace('/a/', 'X', 'aba'));
    }

    public function testReplaceCallback(): void {
        $result = Regex::replaceCallback('/\d+/', fn(array $m): string => (string) ((int) $m[0] * 2), 'a1 b2 c3');
        $this->assertSame('a2 b4 c6', $result);
    }

    public function testSplit(): void {
        $this->assertSame(['a', 'b', 'c'], Regex::split('/[,;]/', 'a,b;c'));
    }

    public function testQuote(): void {
        $this->assertSame('a\\.b', Regex::quote('a.b'));
        $this->assertSame('\\/a\\/', Regex::quote('/a/'));
    }
}
