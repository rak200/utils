<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Str;
use RuntimeException;

final class StrTest extends TestCase {
    public function testIsBlankDetectsWhitespaceOnly(): void {
        $this->assertTrue(Str::isBlank(''));
        $this->assertTrue(Str::isBlank('   '));
        $this->assertTrue(Str::isBlank("\t\n"));
        $this->assertFalse(Str::isBlank(' a '));
    }

    public function testIsNotBlankIsInverseOfBlank(): void {
        $this->assertFalse(Str::isNotBlank('   '));
        $this->assertTrue(Str::isNotBlank('x'));
    }

    public function testIsEmptyChecksStrictEmpty(): void {
        $this->assertTrue(Str::isEmpty(''));
        $this->assertFalse(Str::isEmpty(' '));
    }

    public function testLengthCountsMultibyteCharacters(): void {
        $this->assertSame(5, Str::length('hello'));
        $this->assertSame(3, Str::length('açú'));
    }

    public function testCapitalizeUppersFirstCharOnly(): void {
        $this->assertSame('Hello', Str::capitalize('hello'));
        $this->assertSame('Olá', Str::capitalize('olá'));
        $this->assertSame('', Str::capitalize(''));
    }

    public function testUncapitalizeLowersFirstChar(): void {
        $this->assertSame('hELLO', Str::uncapitalize('HELLO'));
        $this->assertSame('', Str::uncapitalize(''));
    }

    public function testUpperLowerHandleMultibyte(): void {
        $this->assertSame('AÇÚ', Str::upper('açú'));
        $this->assertSame('açú', Str::lower('AÇÚ'));
    }

    public function testContainsStartsWithEndsWith(): void {
        $this->assertTrue(Str::contains('hello world', 'world'));
        $this->assertTrue(Str::contains('hello', ''));
        $this->assertFalse(Str::contains('hello', 'xyz'));
        $this->assertTrue(Str::startsWith('foobar', 'foo'));
        $this->assertFalse(Str::startsWith('foobar', 'bar'));
        $this->assertTrue(Str::endsWith('foobar', 'bar'));
        $this->assertFalse(Str::endsWith('foobar', 'foo'));
    }

    public function testTrimVariants(): void {
        $this->assertSame('abc', Str::trim('  abc  '));
        $this->assertSame('abc  ', Str::trimStart('  abc  '));
        $this->assertSame('  abc', Str::trimEnd('  abc  '));
        $this->assertSame('abc', Str::trim('--abc--', '-'));
    }

    public function testReplace(): void {
        $this->assertSame('hi world', Str::replace('hello world', 'hello', 'hi'));
    }

    public function testSplitOnSeparator(): void {
        $this->assertSame(['a', 'b', 'c'], Str::split('a,b,c', ','));
        $this->assertSame(['a', 'b,c'], Str::split('a,b,c', ',', 2));
    }

    public function testSplitOnEmptySeparatorReturnsChars(): void {
        $this->assertSame(['a', 'ç', 'ú'], Str::split('açú', ''));
    }

    public function testJoinFiltersBlankItems(): void {
        $this->assertSame('a,b,c', Str::join(['a', '', 'b', '   ', 'c'], ','));
    }

    public function testJoinWithPrefixSuffix(): void {
        $this->assertSame('(a, b)', Str::join(['a', 'b'], ', ', '(', ')'));
    }

    public function testJoinWithLastSeparator(): void {
        $this->assertSame('a, b and c', Str::join(['a', 'b', 'c'], ', ', '', '', ' and '));
        $this->assertSame('a and b', Str::join(['a', 'b'], ', ', '', '', ' and '));
        $this->assertSame('a', Str::join(['a'], ', ', '', '', ' and '));
    }

    public function testJoinReturnsEmptyForEmptyInput(): void {
        $this->assertSame('', Str::join([], ','));
        $this->assertSame('', Str::join(['', '   '], ','));
    }

    public function testWrapReturnsEmptyForBlank(): void {
        $this->assertSame('', Str::wrap('', '(', ')'));
        $this->assertSame('', Str::wrap('   ', '(', ')'));
        $this->assertSame('(x)', Str::wrap('x', '(', ')'));
    }

    public function testPadStartPadEnd(): void {
        $this->assertSame('  abc', Str::padStart('abc', 5));
        $this->assertSame('abc  ', Str::padEnd('abc', 5));
        $this->assertSame('00abc', Str::padStart('abc', 5, '0'));
        $this->assertSame('abc', Str::padStart('abc', 3));
        $this->assertSame('abc', Str::padStart('abc', 2));
    }

    public function testPadRejectsEmptyPad(): void {
        $this->expectException(RuntimeException::class);
        Str::padStart('abc', 5, '');
    }

    public function testRepeat(): void {
        $this->assertSame('ababab', Str::repeat('ab', 3));
        $this->assertSame('', Str::repeat('ab', 0));
    }

    public function testRepeatRejectsNegative(): void {
        $this->expectException(RuntimeException::class);
        Str::repeat('a', -1);
    }

    public function testReverseHandlesMultibyte(): void {
        $this->assertSame('cba', Str::reverse('abc'));
        $this->assertSame('úça', Str::reverse('açú'));
    }

    public function testCaseConversions(): void {
        $this->assertSame('helloWorld', Str::toCamelCase('hello_world'));
        $this->assertSame('helloWorld', Str::toCamelCase('hello-world'));
        $this->assertSame('helloWorld', Str::toCamelCase('Hello World'));
        $this->assertSame('HelloWorld', Str::toPascalCase('hello world'));
        $this->assertSame('hello_world', Str::toSnakeCase('helloWorld'));
        $this->assertSame('hello_world', Str::toSnakeCase('HelloWorld'));
        $this->assertSame('hello-world', Str::toKebabCase('helloWorld'));
        $this->assertSame('html_parser', Str::toSnakeCase('HTMLParser'));
    }
}
