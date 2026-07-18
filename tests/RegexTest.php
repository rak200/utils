<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Regex;

/**
 * @internal
 *
 * @coversNothing
 */
final class RegexTest extends TestCase
{
    public function testIsAcceptsValidPatterns(): void
    {
        $this->assertTrue(Regex::is('/foo/'));
        $this->assertTrue(Regex::is('/^\d+$/u'));
        $this->assertTrue(Regex::is('#https?://\S+#i'));
        $this->assertTrue(Regex::is('/(?<name>\w+)/'));
    }

    public function testIsRejectsInvalidPatterns(): void
    {
        $this->assertFalse(Regex::is('not-a-pattern'));
        $this->assertFalse(Regex::is('/['));
        $this->assertFalse(Regex::is('/foo'));
        $this->assertFalse(Regex::is(''));
    }

    public function testMatches(): void
    {
        $this->assertTrue(Regex::matches('/foo/', 'foobar'));
        $this->assertFalse(Regex::matches('/baz/', 'foobar'));
    }

    public function testThrowsOnInvalidPattern(): void
    {
        // An invalid pattern makes the underlying preg_* emit a warning and
        // return false/null; @ suppresses that expected warning while we assert
        // every throwing helper turns it into an InvalidArgumentException.
        $bad = '/[/';
        $calls = [
            'matches' => static fn (): mixed => @Regex::matches($bad, 'x'),
            'matchOrNull' => static fn (): mixed => @Regex::matchOrNull($bad, 'x'),
            'matchAll' => static fn (): mixed => @Regex::matchAll($bad, 'x'),
            'replace' => static fn (): mixed => @Regex::replace($bad, 'r', 'x'),
            'replaceCallback' => static fn (): mixed => @Regex::replaceCallback($bad, static fn (array $m): string => '', 'x'),
            'split' => static fn (): mixed => @Regex::split($bad, 'x'),
            'grep' => static fn (): mixed => @Regex::grep($bad, ['x']),
        ];
        foreach ($calls as $name => $call) {
            try {
                $call();
                $this->fail("Regex::{$name} should throw on an invalid pattern.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testMatchReturnsCapturedGroups(): void
    {
        $result = Regex::match('/(\w+)@(\w+)/', 'hello user@host more');
        $this->assertSame('user@host', $result[0]);
        $this->assertSame('user', $result[1]);
        $this->assertSame('host', $result[2]);
    }

    public function testMatchThrowsWhenNoMatch(): void
    {
        $this->expectException(OutOfBoundsException::class);
        Regex::match('/xyz/', 'abc');
    }

    public function testMatchOrNullReturnsNullWhenNoMatch(): void
    {
        $this->assertNull(Regex::matchOrNull('/xyz/', 'abc'));
        $this->assertNotNull(Regex::matchOrNull('/abc/', 'abc'));
    }

    public function testMatchAll(): void
    {
        $result = Regex::matchAll('/\d+/', 'a1 b22 c333');
        $this->assertSame(['1', '22', '333'], $result[0]);
    }

    public function testMatchAllKeepsEveryCaptureGroup(): void
    {
        $this->assertSame(
            [['a1', 'a2'], ['a', 'a'], ['1', '2']],
            Regex::matchAll('/(a)(\d)/', 'a1 a2'),
        );
    }

    public function testReplace(): void
    {
        $this->assertSame('XbX', Regex::replace('/a/', 'X', 'aba'));
    }

    public function testReplaceCallback(): void
    {
        $result = Regex::replaceCallback('/\d+/', fn (array $m): string => (string) ((int) $m[0] * 2), 'a1 b2 c3');
        $this->assertSame('a2 b4 c6', $result);
    }

    public function testSplit(): void
    {
        $this->assertSame(['a', 'b', 'c'], Regex::split('/[,;]/', 'a,b;c'));
    }

    public function testQuote(): void
    {
        $this->assertSame('a\.b', Regex::quote('a.b'));
        $this->assertSame('\/a\/', Regex::quote('/a/'));
    }

    public function testGrep(): void
    {
        $this->assertSame(
            [0 => 'apple', 2 => 'avocado'],
            Regex::grep('/^a/', ['apple', 'banana', 'avocado']),
        );
        $this->assertSame([], Regex::grep('/^z/', ['apple', 'banana']));
    }

    public function testGrepInverted(): void
    {
        $this->assertSame(
            [1 => 'banana'],
            Regex::grep('/^a/', ['apple', 'banana', 'avocado'], true),
        );
    }

    public function testGrepPreservesKeys(): void
    {
        $this->assertSame(
            ['x' => 'a1', 'z' => 'a2'],
            Regex::grep('/^a/', ['x' => 'a1', 'y' => 'b1', 'z' => 'a2']),
        );
    }
}
