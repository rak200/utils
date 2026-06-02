<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Str;
use RuntimeException;

final class StrTest extends TestCase {
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable {
        yield 'empty string' => ['', true];
        yield 'word'         => ['hello', true];
        yield 'whitespace'   => [' ', true];
        yield 'null'         => [null, false];
        yield 'int'          => [42, false];
        yield 'float'        => [3.14, false];
        yield 'bool'         => [true, false];
        yield 'array'        => [[], false];
        yield 'object'       => [new \stdClass(), false];
    }

    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void {
        $this->assertSame($expected, Str::is($value));
    }

    public function testIsBlankDetectsWhitespaceOnly(): void {
        $this->assertTrue(Str::isBlank(''));
        $this->assertTrue(Str::isBlank('   '));
        $this->assertTrue(Str::isBlank("\t\n"));
        $this->assertFalse(Str::isBlank(' a '));
    }

    public function testIsBlankAcceptsMixed(): void {
        $this->assertTrue(Str::isBlank(null));
        $this->assertFalse(Str::isBlank(0));
        $this->assertFalse(Str::isBlank([]));
        $this->assertFalse(Str::isBlank(false));
    }

    public function testIsNotBlankRejectsBlankAndNonStrings(): void {
        $this->assertFalse(Str::isNotBlank('   '));
        $this->assertTrue(Str::isNotBlank('x'));
        $this->assertFalse(Str::isNotBlank(null));
        $this->assertFalse(Str::isNotBlank(42));
    }

    public function testIsEmptyChecksStrictEmpty(): void {
        $this->assertTrue(Str::isEmpty(''));
        $this->assertFalse(Str::isEmpty(' '));
    }

    public function testIsNonEmptyStr(): void {
        $this->assertTrue(Str::isNonEmptyStr('a'));
        $this->assertTrue(Str::isNonEmptyStr(' '));
        $this->assertFalse(Str::isNonEmptyStr(''));
        $this->assertFalse(Str::isNonEmptyStr(null));
        $this->assertFalse(Str::isNonEmptyStr(0));
    }

    public function testIsWhitespace(): void {
        $this->assertTrue(Str::isWhitespace(' '));
        $this->assertTrue(Str::isWhitespace("\t"));
        $this->assertTrue(Str::isWhitespace("\n"));
        $this->assertTrue(Str::isWhitespace("\r"));
        $this->assertTrue(Str::isWhitespace(" \t\n"));
        $this->assertFalse(Str::isWhitespace(''));
        $this->assertFalse(Str::isWhitespace('a'));
        $this->assertFalse(Str::isWhitespace(' a '));
        $this->assertFalse(Str::isWhitespace('42'));
        $this->assertFalse(Str::isWhitespace("\xC2\xA0"));
    }

    public function testLengthCountsMultibyteCharacters(): void {
        $this->assertSame(5, Str::length('hello'));
        $this->assertSame(3, Str::length('açú'));
    }

    public function testByteLengthCountsBytes(): void {
        $this->assertSame(5, Str::byteLength('hello'));
        $this->assertSame(0, Str::byteLength(''));
        // "açú" is 3 characters but 5 bytes in UTF-8 (ç and ú are two bytes each)
        $this->assertSame(5, Str::byteLength('açú'));
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

    public function testJoinNaturalFiltersBlankItems(): void {
        $this->assertSame('a,b,c', Str::joinNatural(['a', '', 'b', '   ', 'c'], ','));
        $this->assertSame('ab', Str::joinNatural(['a', '', 'b']));   // default '' separator = concat dropping blanks
    }

    public function testJoinNaturalWithPrefixSuffix(): void {
        $this->assertSame('(a, b)', Str::joinNatural(['a', 'b'], ', ', '(', ')'));
    }

    public function testJoinNaturalWithLastSeparator(): void {
        $this->assertSame('a, b and c', Str::joinNatural(['a', 'b', 'c'], ', ', '', '', ' and '));
        $this->assertSame('a and b', Str::joinNatural(['a', 'b'], ', ', '', '', ' and '));
        $this->assertSame('a', Str::joinNatural(['a'], ', ', '', '', ' and '));
    }

    public function testJoinNaturalReturnsEmptyForEmptyInput(): void {
        $this->assertSame('', Str::joinNatural([], ','));
        $this->assertSame('', Str::joinNatural(['', '   '], ','));
    }

    public function testJoinWithoutSkipBlanksMirrorsImplode(): void {
        $items = ['a', '', 'b', '   ', 'c'];
        $this->assertSame(implode(',', $items), Str::join($items, ',', skipBlanks: false));
        $this->assertSame('a,,b', Str::join(['a', '', 'b'], ',', skipBlanks: false));
        $this->assertSame('abc', Str::join(['a', 'b', 'c'], skipBlanks: false));   // default '' separator = concat
        // prefix/suffix/lastSeparator still apply; blanks just are not dropped
        $this->assertSame('[a, , b]', Str::join(['a', '', 'b'], ', ', '[', ']', skipBlanks: false));
        $this->assertSame('a, b and c', Str::join(['a', 'b', 'c'], ', ', '', '', ' and ', skipBlanks: false));
    }

    public function testJoinWithSkipBlanksDefaultTriggersDeprecation(): void {
        $captured = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$captured): bool {
            $captured = $errstr;
            return true;
        }, E_USER_DEPRECATED);
        try {
            Str::join(['a', 'b'], ',');
        } finally {
            restore_error_handler();
        }
        $this->assertStringContainsString('joinNatural', $captured);
        $this->assertStringContainsString('deprecated', $captured);
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
        $this->assertSame('helloWorld', Str::toCamel('hello_world'));
        $this->assertSame('helloWorld', Str::toCamel('hello-world'));
        $this->assertSame('helloWorld', Str::toCamel('Hello World'));
        $this->assertSame('HelloWorld', Str::toPascal('hello world'));
        $this->assertSame('hello_world', Str::toSnake('helloWorld'));
        $this->assertSame('hello_world', Str::toSnake('HelloWorld'));
        $this->assertSame('hello-world', Str::toKebab('helloWorld'));
        $this->assertSame('html_parser', Str::toSnake('HTMLParser'));
    }

    public function testCaseConversionHandlesUnicodeBoundaries(): void {
        $this->assertSame('ó_água', Str::toSnake('óÁgua'));
        $this->assertSame('ó-água', Str::toKebab('óÁgua'));
    }

    public function testDeprecatedCaseConversionAliasesStillWork(): void {
        $this->assertSame(Str::toCamel('hello_world'), Str::toCamelCase('hello_world'));
        $this->assertSame(Str::toPascal('hello world'), Str::toPascalCase('hello world'));
        $this->assertSame(Str::toSnake('HelloWorld'), Str::toSnakeCase('HelloWorld'));
        $this->assertSame(Str::toKebab('HelloWorld'), Str::toKebabCase('HelloWorld'));
    }

    public function testSubstringMultibyte(): void {
        $this->assertSame('ell', Str::substring('hello', 1, 3));
        $this->assertSame('ção', Str::substring('ação', 1));
        $this->assertSame('', Str::substring('hello', 0, 0));
    }

    public function testIndexOfAndLastIndexOf(): void {
        $this->assertSame(6, Str::indexOf('hello world', 'world'));
        $this->assertSame(-1, Str::indexOf('hello', 'xyz'));
        $this->assertSame(-1, Str::indexOf('hello', ''));
        $this->assertSame(5, Str::lastIndexOf('abcabc', 'c'));
        $this->assertSame(-1, Str::lastIndexOf('abc', 'z'));
    }

    public function testCountSubstring(): void {
        $this->assertSame(3, Str::count('abcabcabc', 'a'));
        $this->assertSame(0, Str::count('abc', 'z'));
        $this->assertSame(0, Str::count('abc', ''));
    }

    public function testTruncate(): void {
        $this->assertSame('hello', Str::truncate('hello', 10));
        $this->assertSame('hel…', Str::truncate('hello world', 4));
        $this->assertSame('h…', Str::truncate('hello', 2));
        $this->assertSame('…', Str::truncate('hello', 1));
        $this->assertSame('', Str::truncate('hello', 0));
        $this->assertSame('hello…', Str::truncate('hello world', 6));
    }

    public function testTruncateRejectsNegativeLength(): void {
        $this->expectException(RuntimeException::class);
        Str::truncate('x', -1);
    }

    public function testReplaceFirstAndLast(): void {
        $this->assertSame('xyz-foo-foo', Str::replaceFirst('foo-foo-foo', 'foo', 'xyz'));
        $this->assertSame('foo-foo-xyz', Str::replaceLast('foo-foo-foo', 'foo', 'xyz'));
        $this->assertSame('hello', Str::replaceFirst('hello', 'x', 'y'));
        $this->assertSame('hello', Str::replaceFirst('hello', '', 'y'));
    }

    public function testSlug(): void {
        $this->assertSame('hello-world', Str::slug('Hello World!'));
        $this->assertSame('one-two-three', Str::slug('one  two  three'));
        $this->assertSame('foo_bar', Str::slug('Foo  Bar', '_'));
        $this->assertSame('', Str::slug('   '));
    }

    public function testIndexOfWithIgnoreCase(): void {
        $this->assertSame(0, Str::indexOf('Hello', 'h', 0, true));
        $this->assertSame(2, Str::indexOf('Hello', 'L', 0, true));
        $this->assertSame(6, Str::indexOf('Héllo Héllo', 'héllo', 1, true));
        $this->assertSame(-1, Str::indexOf('Hello', 'h'));          // case-sensitive: no match
        $this->assertSame(-1, Str::indexOf('Hello', 'x', 0, true));
    }

    public function testLastIndexOfWithOffsetAndIgnoreCase(): void {
        $this->assertSame(6, Str::lastIndexOf('Hello Hello', 'h', 0, true));
        $this->assertSame(3, Str::lastIndexOf('Hello', 'L', 0, true));
        $this->assertSame(-1, Str::lastIndexOf('Hello', 'h'));      // case-sensitive: no match
        $this->assertSame(3, Str::lastIndexOf('abcabc', 'a', 1));   // offset limits the region searched
        $this->assertSame(-1, Str::lastIndexOf('abcabc', 'a', 4));
    }

    public function testOrd(): void {
        $this->assertSame(65, Str::ord('A'));
        $this->assertSame(97, Str::ord('abc'));
        $this->assertSame(0x20AC, Str::ord('€'));
        $this->assertSame(0x1F600, Str::ord('😀'));
    }

    public function testOrdRejectsEmptyString(): void {
        $this->expectException(RuntimeException::class);
        Str::ord('');
    }

    public function testOrdRejectsInvalidUtf8(): void {
        $this->expectException(RuntimeException::class);
        Str::ord("\xFF");
    }

    public function testChr(): void {
        $this->assertSame('A', Str::chr(65));
        $this->assertSame('€', Str::chr(0x20AC));
        $this->assertSame('😀', Str::chr(0x1F600));
    }

    public function testChrRejectsNegativeCodePoint(): void {
        $this->expectException(RuntimeException::class);
        Str::chr(-1);
    }

    public function testChrRejectsCodePointAboveMax(): void {
        $this->expectException(RuntimeException::class);
        Str::chr(0x110000);
    }

    public function testOrdChrRoundTrip(): void {
        foreach (['A', 'z', '€', '😀', 'ç'] as $char) {
            $this->assertSame($char, Str::chr(Str::ord($char)));
        }
    }

    public function testTranslate(): void {
        $this->assertSame('hippo', Str::translate('hello', 'el', 'ip'));
        $this->assertSame('h3ll0', Str::translate('hello', 'eo', '30'));
        $this->assertSame('abc', Str::translate('abc', '', ''));
    }

    public function testTranslateIsMultibyte(): void {
        $this->assertSame('aeiou', Str::translate('áéíóú', 'áéíóú', 'aeiou'));
    }

    public function testTranslateIsSinglePass(): void {
        $this->assertSame('bc', Str::translate('ab', 'ab', 'bc'));
    }

    public function testTranslateRejectsLengthMismatch(): void {
        $this->expectException(RuntimeException::class);
        Str::translate('hello', 'el', 'x');
    }

    public function testSpan(): void {
        $this->assertSame(5, Str::span('hello', 'helo'));    // every char is in the set
        $this->assertSame(3, Str::span('aaabbb', 'a'));      // leading run only
        $this->assertSame(0, Str::span('xabc', 'abc'));      // first char not in set
        $this->assertSame(2, Str::span('01x1', '01'));       // stops at the first char outside the set
        $this->assertSame(4, Str::span('xx0011', '01', 2));  // $start offsets into the string
        $this->assertSame(2, Str::span('0011', '01', 0, 2)); // $length bounds the window
    }

    public function testIsDigitsAlphaAlnum(): void {
        $this->assertTrue(Str::isDigits('0123456789'));
        $this->assertFalse(Str::isDigits(''));
        $this->assertFalse(Str::isDigits('12.3'));
        $this->assertFalse(Str::isDigits('12a'));

        $this->assertTrue(Str::isAlpha('abcXYZ'));
        $this->assertFalse(Str::isAlpha(''));
        $this->assertFalse(Str::isAlpha('abcé'));   // multibyte is not ASCII
        $this->assertFalse(Str::isAlpha('ab1'));

        $this->assertTrue(Str::isAlnum('abc123'));
        $this->assertFalse(Str::isAlnum(''));
        $this->assertFalse(Str::isAlnum('abc 123'));
    }

    public function testTitle(): void {
        $this->assertSame('Hello World', Str::title('hello world'));
        $this->assertSame('Hello World', Str::title('hello WORLD'));
        $this->assertSame('', Str::title(''));
        $this->assertSame('Árvore Útil', Str::title('árvore ÚTIL'));   // multibyte
    }

    public function testReplaceIgnoreCase(): void {
        $this->assertSame('x x', Str::replace('Hello HELLO', 'hello', 'x', true));
        $this->assertSame('Hello HELLO', Str::replace('Hello HELLO', 'hello', 'x')); // default sensitive
        $this->assertSame('aXc', Str::replace('abc', 'b', 'X'));
    }

    public function testReplaceAt(): void {
        $this->assertSame('hXYo', Str::replaceAt('hello', 1, 3, 'XY'));
        $this->assertSame('a-bc', Str::replaceAt('abc', 1, 0, '-'));      // insert (length 0)
        $this->assertSame('hel!!', Str::replaceAt('hello', -2, 2, '!!')); // negative start
        $this->assertSame('hXo', Str::replaceAt('hello', 1, -1, 'X'));    // negative length
        $this->assertSame('ABCDE', Str::replaceAt('', 0, 0, 'ABCDE'));    // empty subject
        $this->assertSame('héllo x', Str::replaceAt('héllo world', 6, 5, 'x')); // multibyte char indices
    }

    public function testBeforeAfter(): void {
        $this->assertSame('user', Str::before('user@host', '@'));
        $this->assertSame('host', Str::after('user@host', '@'));
        $this->assertSame('abc', Str::before('abc', '@'));   // not found → whole
        $this->assertSame('abc', Str::after('abc', '@'));     // not found → whole
        $this->assertSame('abc', Str::before('abc', ''));     // empty search → whole
        $this->assertSame('abc', Str::after('abc', ''));
        $this->assertSame('a', Str::before('a.b.c', '.'));    // first occurrence
        $this->assertSame('b.c', Str::after('a.b.c', '.'));
    }

    public function testWordWrap(): void {
        $this->assertSame("aaa bbb\nccc", Str::wordWrap('aaa bbb ccc', 7));
        $this->assertSame('aaa-bbb', Str::wordWrap('aaa bbb', 4, '-'));
        $this->assertSame("ab\ncd", Str::wordWrap('abcd', 2, "\n", true)); // cut long words
    }

    public function testWordWrapThrowsForBadWidth(): void {
        $this->expectException(RuntimeException::class);
        Str::wordWrap('abc', 0);
    }

    public function testWordCount(): void {
        $this->assertSame(0, Str::wordCount(''));
        $this->assertSame(0, Str::wordCount('   '));
        $this->assertSame(3, Str::wordCount('one two three'));
        $this->assertSame(2, Str::wordCount("it's"));        // apostrophe splits the word
        $this->assertSame(2, Str::wordCount('café résumé')); // multibyte words counted
    }

    public function testFormat(): void {
        $this->assertSame('x is 5', Str::format('%s is %d', 'x', 5));
        $this->assertSame('3.14', Str::format('%.2f', 3.14159));
        $this->assertSame('no args', Str::format('no args'));
    }

    public function testScan(): void {
        $this->assertSame([42], Str::scan('age:42', 'age:%d'));
        $this->assertSame(['John', 25], Str::scan('John 25', '%s %d'));
        $this->assertSame([null], Str::scan('nope', 'age:%d')); // no match → null slot
    }

    public function testFormatScanRoundTrip(): void {
        $formatted = Str::format('%s %d', 'x', 7);
        $this->assertSame(['x', 7], Str::scan($formatted, '%s %d'));
    }

    public function testLevenshtein(): void {
        $this->assertSame(0, Str::levenshtein('abc', 'abc'));
        $this->assertSame(3, Str::levenshtein('kitten', 'sitting'));
        $this->assertSame(3, Str::levenshtein('', 'abc'));
    }

    public function testSimilarity(): void {
        $this->assertSame(100.0, Str::similarity('abc', 'abc'));
        $this->assertSame(0.0, Str::similarity('abc', 'xyz'));
        $this->assertGreaterThan(50.0, Str::similarity('World', 'word'));
    }

    public function testSplitDefaultSeparator(): void {
        $this->assertSame(['a', 'b', 'c'], Str::split('abc'));
        $this->assertSame(['ab', 'cd', 'ef'], Str::split('abcdef', '', 2)); // chunk size via $limit
        $this->assertSame(['é', 'ñ'], Str::split('éñ'));                     // multibyte chars
        $this->assertSame(['a', 'b'], Str::split('a,b', ','));
        $this->assertSame(['a', 'b,c'], Str::split('a,b,c', ',', 2));        // piece limit
    }

    public function testToBytesAndFromBytes(): void {
        $this->assertSame([], Str::toBytes(''));
        $this->assertSame([104, 105], Str::toBytes('hi'));
        $this->assertSame([0, 255, 16], Str::toBytes("\x00\xff\x10"));
        $this->assertSame('hi', Str::fromBytes([104, 105]));
        $this->assertSame("\x00\xff", Str::fromBytes([0, 255]));
        $this->assertSame('', Str::fromBytes([]));
        $bin = random_bytes(64);
        $this->assertSame($bin, Str::fromBytes(Str::toBytes($bin))); // round-trip arbitrary binary
    }

    public function testFromBytesRejectsOutOfRange(): void {
        $this->expectException(RuntimeException::class);
        Str::fromBytes([0, 256]);
    }

    public function testRenamedMethodsAndAliases(): void {
        $this->assertSame(3, Str::len('abc'));
        $this->assertSame(Str::length('açú'), Str::len('açú'));         // alias forwards
        $this->assertSame(2, Str::byteLen('é'));
        $this->assertSame(Str::byteLength('héllo'), Str::byteLen('héllo'));
        $this->assertSame('ell', Str::sub('hello', 1, 3));
        $this->assertSame(Str::substring('héllo', 1), Str::sub('héllo', 1));
        $this->assertSame('he…', Str::trunc('hello world', 3));
        $this->assertSame(Str::truncate('hello world', 6), Str::trunc('hello world', 6));
    }
}
