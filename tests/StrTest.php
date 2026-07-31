<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\EmptySourceException;
use Rak200\Utils\Exception\MalformedArgumentException;
use Rak200\Utils\Str;
use stdClass;
use Stringable;

/**
 * @internal
 *
 * @coversNothing
 */
final class StrTest extends TestCase
{
    #[DataProvider('isProvider')]
    public function testIs(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Str::is($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isProvider(): iterable
    {
        yield 'empty string' => ['', true];

        yield 'word' => ['hello', true];

        yield 'whitespace' => [' ', true];

        yield 'null' => [null, false];

        yield 'int' => [42, false];

        yield 'float' => [3.14, false];

        yield 'bool' => [true, false];

        yield 'array' => [[], false];

        yield 'object' => [new stdClass(), false];
    }

    public function testIsBlankDetectsWhitespaceOnly(): void
    {
        $this->assertTrue(Str::isBlank(''));
        $this->assertTrue(Str::isBlank('   '));
        $this->assertTrue(Str::isBlank("\t\n"));
        $this->assertFalse(Str::isBlank(' a '));
    }

    public function testIsBlankAcceptsMixed(): void
    {
        $this->assertTrue(Str::isBlank(null));
        $this->assertFalse(Str::isBlank(0));
        $this->assertFalse(Str::isBlank([]));
        $this->assertFalse(Str::isBlank(false));
    }

    public function testIsNotBlankRejectsBlankAndNonStrings(): void
    {
        $this->assertFalse(Str::isNotBlank('   '));
        $this->assertTrue(Str::isNotBlank('x'));
        $this->assertFalse(Str::isNotBlank(null));
        $this->assertFalse(Str::isNotBlank(42));
    }

    public function testIsEmptyChecksStrictEmpty(): void
    {
        $this->assertTrue(Str::isEmpty(''));
        $this->assertFalse(Str::isEmpty(' '));
    }

    public function testIsWhitespace(): void
    {
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

    public function testLengthCountsMultibyteCharacters(): void
    {
        $this->assertSame(5, Str::len('hello'));
        $this->assertSame(3, Str::len('açú'));
    }

    public function testByteLengthCountsBytes(): void
    {
        $this->assertSame(5, Str::byteLen('hello'));
        $this->assertSame(0, Str::byteLen(''));
        // "açú" is 3 characters but 5 bytes in UTF-8 (ç and ú are two bytes each)
        $this->assertSame(5, Str::byteLen('açú'));
        $this->assertSame(3, Str::len('açú'));
    }

    public function testCapitalizeUppersFirstCharOnly(): void
    {
        $this->assertSame('Hello', Str::capitalize('hello'));
        $this->assertSame('Olá', Str::capitalize('olá'));
        $this->assertSame('', Str::capitalize(''));
    }

    public function testUncapitalizeLowersFirstChar(): void
    {
        $this->assertSame('hELLO', Str::uncapitalize('HELLO'));
        $this->assertSame('', Str::uncapitalize(''));
    }

    public function testUpperLowerHandleMultibyte(): void
    {
        $this->assertSame('AÇÚ', Str::upper('açú'));
        $this->assertSame('açú', Str::lower('AÇÚ'));
    }

    public function testContainsStartsWithEndsWith(): void
    {
        $this->assertTrue(Str::contains('hello world', 'world'));
        $this->assertTrue(Str::contains('hello', ''));
        $this->assertFalse(Str::contains('hello', 'xyz'));
        $this->assertTrue(Str::startsWith('foobar', 'foo'));
        $this->assertFalse(Str::startsWith('foobar', 'bar'));
        $this->assertTrue(Str::endsWith('foobar', 'bar'));
        $this->assertFalse(Str::endsWith('foobar', 'foo'));
    }

    public function testTrimVariants(): void
    {
        $this->assertSame('abc', Str::trim('  abc  '));
        $this->assertSame('abc  ', Str::trimStart('  abc  '));
        $this->assertSame('  abc', Str::trimEnd('  abc  '));
        $this->assertSame('abc', Str::trim('--abc--', '-'));
    }

    public function testReplace(): void
    {
        $this->assertSame('hi world', Str::replace('hello world', 'hello', 'hi'));
    }

    public function testSplitOnSeparator(): void
    {
        $this->assertSame(['a', 'b', 'c'], Str::split('a,b,c', ','));
        $this->assertSame(['a', 'b,c'], Str::split('a,b,c', ',', 2));
    }

    public function testSplitOnEmptySeparatorReturnsChars(): void
    {
        $this->assertSame(['a', 'ç', 'ú'], Str::split('açú', ''));
    }

    public function testJoinNaturalFiltersBlankItems(): void
    {
        $this->assertSame('a,b,c', Str::joinNatural(['a', '', 'b', '   ', 'c'], ','));
        $this->assertSame('ab', Str::joinNatural(['a', '', 'b']));   // default '' separator = concat dropping blanks
    }

    public function testJoinNaturalWithPrefixSuffix(): void
    {
        $this->assertSame('(a, b)', Str::joinNatural(['a', 'b'], ', ', '(', ')'));
    }

    public function testJoinNaturalWithLastSeparator(): void
    {
        $this->assertSame('a, b and c', Str::joinNatural(['a', 'b', 'c'], ', ', '', '', ' and '));
        $this->assertSame('a and b', Str::joinNatural(['a', 'b'], ', ', '', '', ' and '));
        $this->assertSame('a', Str::joinNatural(['a'], ', ', '', '', ' and '));
    }

    public function testJoinNaturalReturnsEmptyForEmptyInput(): void
    {
        $this->assertSame('', Str::joinNatural([], ','));
        $this->assertSame('', Str::joinNatural(['', '   '], ','));
    }

    public function testJoinNaturalCastsStringableObjects(): void
    {
        $hello = new class implements Stringable {
            public function __toString(): string
            {
                return 'hello';
            }
        };
        $blank = new class implements Stringable {
            public function __toString(): string
            {
                return '   ';
            }
        };
        // a Stringable item is cast via __toString() and joined like any other
        $this->assertSame('hello, world', Str::joinNatural([$hello, 'world'], ', '));
        // ...including with the Oxford-style last separator
        $this->assertSame('a, b and hello', Str::joinNatural(['a', 'b', $hello], ', ', '', '', ' and '));
        // a Stringable whose __toString() is blank is dropped, like a blank string
        $this->assertSame('a, b', Str::joinNatural(['a', $blank, 'b'], ', '));
    }

    public function testJoinNaturalAcceptsAnyIterable(): void
    {
        // a fresh Generator each call (generators are single-use), not just an array
        $items = static function () {
            yield 'a';

            yield '';

            yield 'b';

            yield '   ';

            yield 'c';
        };
        // blanks are still dropped when the items come from a Generator
        $this->assertSame('a, b, c', Str::joinNatural($items(), ', '));
        // ...and the Oxford-style last separator still applies
        $this->assertSame('a, b and c', Str::joinNatural($items(), ', ', '', '', ' and '));
    }

    public function testJoinMirrorsImplode(): void
    {
        $items = ['a', '', 'b', '   ', 'c'];
        $this->assertSame(implode(',', $items), Str::join($items, ','));
        $this->assertSame('a,,b', Str::join(['a', '', 'b'], ','));   // blanks kept
        $this->assertSame('abc', Str::join(['a', 'b', 'c']));        // default '' separator = concat
        $this->assertSame('', Str::join([], ','));
        // accepts any iterable, not just arrays (unlike native implode)
        $gen = (static function () {
            yield 'a';

            yield 'b';

            yield 'c';
        })();
        $this->assertSame('a-b-c', Str::join($gen, '-'));
    }

    public function testJoinCastsStringableObjects(): void
    {
        $hello = new class implements Stringable {
            public function __toString(): string
            {
                return 'hello';
            }
        };
        $blank = new class implements Stringable {
            public function __toString(): string
            {
                return '   ';
            }
        };
        // a Stringable item is cast via __toString() and joined like any other
        $this->assertSame('hello, world', Str::join([$hello, 'world'], ', '));
        // unlike joinNatural, a blank __toString() is kept (mirrors implode)
        $this->assertSame(implode(', ', ['a', '   ', 'b']), Str::join(['a', $blank, 'b'], ', '));
    }

    public function testWrapReturnsEmptyForBlank(): void
    {
        $this->assertSame('', Str::wrap('', '(', ')'));
        $this->assertSame('', Str::wrap('   ', '(', ')'));
        $this->assertSame('(x)', Str::wrap('x', '(', ')'));
    }

    public function testPadStartPadEnd(): void
    {
        $this->assertSame('  abc', Str::padStart('abc', 5));
        $this->assertSame('abc  ', Str::padEnd('abc', 5));
        $this->assertSame('00abc', Str::padStart('abc', 5, '0'));
        $this->assertSame('abc', Str::padStart('abc', 3));
        $this->assertSame('abc', Str::padStart('abc', 2));
    }

    public function testPadRejectsEmptyPad(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::padStart('abc', 5, '');
    }

    public function testPadEndRejectsEmptyPad(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::padEnd('abc', 5, '');
    }

    public function testRepeat(): void
    {
        $this->assertSame('ababab', Str::repeat('ab', 3));
        $this->assertSame('', Str::repeat('ab', 0));
    }

    public function testRepeatRejectsNegative(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::repeat('a', -1);
    }

    public function testReverseHandlesMultibyte(): void
    {
        $this->assertSame('cba', Str::reverse('abc'));
        $this->assertSame('úça', Str::reverse('açú'));
    }

    public function testCaseConversions(): void
    {
        $this->assertSame('helloWorld', Str::toCamel('hello_world'));
        $this->assertSame('helloWorld', Str::toCamel('hello-world'));
        $this->assertSame('helloWorld', Str::toCamel('Hello World'));
        $this->assertSame('HelloWorld', Str::toPascal('hello world'));
        $this->assertSame('hello_world', Str::toSnake('helloWorld'));
        $this->assertSame('hello_world', Str::toSnake('HelloWorld'));
        $this->assertSame('hello-world', Str::toKebab('helloWorld'));
        $this->assertSame('html_parser', Str::toSnake('HTMLParser'));
    }

    public function testCaseConversionHandlesUnicodeBoundaries(): void
    {
        $this->assertSame('ó_água', Str::toSnake('óÁgua'));
        $this->assertSame('ó-água', Str::toKebab('óÁgua'));
    }

    public function testSubstringMultibyte(): void
    {
        $this->assertSame('ell', Str::sub('hello', 1, 3));
        $this->assertSame('ção', Str::sub('ação', 1));
        $this->assertSame('', Str::sub('hello', 0, 0));
    }

    public function testIndexOfAndLastIndexOf(): void
    {
        $this->assertSame(6, Str::indexOf('hello world', 'world'));
        $this->assertSame(-1, Str::indexOf('hello', 'xyz'));
        $this->assertSame(-1, Str::indexOf('hello', ''));
        $this->assertSame(5, Str::lastIndexOf('abcabc', 'c'));
        $this->assertSame(-1, Str::lastIndexOf('abc', 'z'));
        $this->assertSame(-1, Str::lastIndexOf('abc', ''));   // empty needle
    }

    public function testCountSubstring(): void
    {
        $this->assertSame(3, Str::count('abcabcabc', 'a'));
        $this->assertSame(0, Str::count('abc', 'z'));
        $this->assertSame(0, Str::count('abc', ''));
    }

    public function testTruncate(): void
    {
        $this->assertSame('hello', Str::trunc('hello', 10));
        $this->assertSame('hel…', Str::trunc('hello world', 4));
        $this->assertSame('h…', Str::trunc('hello', 2));
        $this->assertSame('…', Str::trunc('hello', 1));
        $this->assertSame('', Str::trunc('hello', 0));
        $this->assertSame('hello…', Str::trunc('hello world', 6));
    }

    public function testTruncateRejectsNegativeLength(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::trunc('x', -1);
    }

    public function testReplaceFirstAndLast(): void
    {
        $this->assertSame('xyz-foo-foo', Str::replaceFirst('foo-foo-foo', 'foo', 'xyz'));
        $this->assertSame('foo-foo-xyz', Str::replaceLast('foo-foo-foo', 'foo', 'xyz'));
        $this->assertSame('hello', Str::replaceFirst('hello', 'x', 'y'));
        $this->assertSame('hello', Str::replaceFirst('hello', '', 'y'));
        $this->assertSame('hello', Str::replaceLast('hello', 'x', 'y'));   // not found
        $this->assertSame('hello', Str::replaceLast('hello', '', 'y'));    // empty search
    }

    public function testSlug(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World!'));
        $this->assertSame('one-two-three', Str::slug('one  two  three'));
        $this->assertSame('foo_bar', Str::slug('Foo  Bar', '_'));
        $this->assertSame('', Str::slug('   '));
    }

    public function testMask(): void
    {
        $this->assertSame('************1111', Str::mask('4111111111111111', 0, -4));
        $this->assertSame('tay***************', Str::mask('taylor@example.com', 3));
        $this->assertSame('sec***', Str::mask('secret', -3));
        $this->assertSame('##34', Str::mask('1234', 0, 2, '#'));
        $this->assertSame('hello', Str::mask('hello', 0, 0));
        $this->assertSame('*****', Str::mask('hello'));
    }

    public function testMaskKeepsSeparators(): void
    {
        $this->assertSame('***.***.***-09', Str::mask('123.456.789-09', 0, -2, keep: '.-'));
        $this->assertSame('123.***.***-09', Str::mask('123.456.789-09', 4, -2, keep: '.-'));
        $this->assertSame(
            '***.456.***-09',
            Str::mask(Str::mask('123.456.789-09', 0, 3, keep: '.-'), 8, 3, keep: '.-'),
        );
    }

    public function testMaskMultibyte(): void
    {
        $this->assertSame('caf**', Str::mask('café!', 3));
        $this->assertSame('•••', Str::mask('abc', 0, null, '•'));
    }

    public function testMaskRejectsEmptyMask(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::mask('hello', 0, 2, '');
    }

    public function testIndexOfWithIgnoreCase(): void
    {
        $this->assertSame(0, Str::indexOf('Hello', 'h', 0, true));
        $this->assertSame(2, Str::indexOf('Hello', 'L', 0, true));
        $this->assertSame(6, Str::indexOf('Héllo Héllo', 'héllo', 1, true));
        $this->assertSame(-1, Str::indexOf('Hello', 'h'));          // case-sensitive: no match
        $this->assertSame(-1, Str::indexOf('Hello', 'x', 0, true));
    }

    public function testLastIndexOfWithOffsetAndIgnoreCase(): void
    {
        $this->assertSame(6, Str::lastIndexOf('Hello Hello', 'h', 0, true));
        $this->assertSame(3, Str::lastIndexOf('Hello', 'L', 0, true));
        $this->assertSame(-1, Str::lastIndexOf('Hello', 'h'));      // case-sensitive: no match
        $this->assertSame(3, Str::lastIndexOf('abcabc', 'a', 1));   // offset limits the region searched
        $this->assertSame(-1, Str::lastIndexOf('abcabc', 'a', 4));
    }

    public function testOrd(): void
    {
        $this->assertSame(65, Str::ord('A'));
        $this->assertSame(97, Str::ord('abc'));
        $this->assertSame(0x20AC, Str::ord('€'));
        $this->assertSame(0x1F600, Str::ord('😀'));
    }

    public function testOrdRejectsEmptyString(): void
    {
        $this->expectException(EmptySourceException::class);
        Str::ord('');
    }

    public function testOrdRejectsInvalidUtf8(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::ord("\xFF");
    }

    public function testChr(): void
    {
        $this->assertSame('A', Str::chr(65));
        $this->assertSame('€', Str::chr(0x20AC));
        $this->assertSame('😀', Str::chr(0x1F600));
    }

    public function testChrRejectsNegativeCodePoint(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::chr(-1);
    }

    public function testChrRejectsCodePointAboveMax(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::chr(0x110000);
    }

    public function testOrdChrRoundTrip(): void
    {
        foreach (['A', 'z', '€', '😀', 'ç'] as $char) {
            $this->assertSame($char, Str::chr(Str::ord($char)));
        }
    }

    public function testTranslate(): void
    {
        $this->assertSame('hippo', Str::translate('hello', 'el', 'ip'));
        $this->assertSame('h3ll0', Str::translate('hello', 'eo', '30'));
        $this->assertSame('abc', Str::translate('abc', '', ''));
    }

    public function testTranslateIsMultibyte(): void
    {
        $this->assertSame('aeiou', Str::translate('áéíóú', 'áéíóú', 'aeiou'));
    }

    public function testTranslateIsSinglePass(): void
    {
        $this->assertSame('bc', Str::translate('ab', 'ab', 'bc'));
    }

    public function testTranslateRejectsLengthMismatch(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::translate('hello', 'el', 'x');
    }

    public function testSpan(): void
    {
        $this->assertSame(5, Str::span('hello', 'helo'));    // every char is in the set
        $this->assertSame(3, Str::span('aaabbb', 'a'));      // leading run only
        $this->assertSame(0, Str::span('xabc', 'abc'));      // first char not in set
        $this->assertSame(2, Str::span('01x1', '01'));       // stops at the first char outside the set
        $this->assertSame(4, Str::span('xx0011', '01', 2));  // $start offsets into the string
        $this->assertSame(2, Str::span('0011', '01', 0, 2)); // $length bounds the window
    }

    public function testIsDigitsAlphaAlnum(): void
    {
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

    public function testTitle(): void
    {
        $this->assertSame('Hello World', Str::title('hello world'));
        $this->assertSame('Hello World', Str::title('hello WORLD'));
        $this->assertSame('', Str::title(''));
        $this->assertSame('Árvore Útil', Str::title('árvore ÚTIL'));   // multibyte
    }

    public function testReplaceIgnoreCase(): void
    {
        $this->assertSame('x x', Str::replace('Hello HELLO', 'hello', 'x', true));
        $this->assertSame('Hello HELLO', Str::replace('Hello HELLO', 'hello', 'x')); // default sensitive
        $this->assertSame('aXc', Str::replace('abc', 'b', 'X'));
    }

    public function testReplaceAt(): void
    {
        $this->assertSame('hXYo', Str::replaceAt('hello', 1, 3, 'XY'));
        $this->assertSame('a-bc', Str::replaceAt('abc', 1, 0, '-'));      // insert (length 0)
        $this->assertSame('hel!!', Str::replaceAt('hello', -2, 2, '!!')); // negative start
        $this->assertSame('hXo', Str::replaceAt('hello', 1, -1, 'X'));    // negative length
        $this->assertSame('ABCDE', Str::replaceAt('', 0, 0, 'ABCDE'));    // empty subject
        $this->assertSame('héllo x', Str::replaceAt('héllo world', 6, 5, 'x')); // multibyte char indices
    }

    public function testBeforeAfter(): void
    {
        $this->assertSame('user', Str::before('user@host', '@'));
        $this->assertSame('host', Str::after('user@host', '@'));
        $this->assertSame('abc', Str::before('abc', '@'));   // not found → whole
        $this->assertSame('abc', Str::after('abc', '@'));     // not found → whole
        $this->assertSame('abc', Str::before('abc', ''));     // empty search → whole
        $this->assertSame('abc', Str::after('abc', ''));
        $this->assertSame('a', Str::before('a.b.c', '.'));    // first occurrence
        $this->assertSame('b.c', Str::after('a.b.c', '.'));
    }

    public function testWordWrap(): void
    {
        $this->assertSame("aaa bbb\nccc", Str::wordWrap('aaa bbb ccc', 7));
        $this->assertSame('aaa-bbb', Str::wordWrap('aaa bbb', 4, '-'));
        $this->assertSame("ab\ncd", Str::wordWrap('abcd', 2, "\n", true)); // cut long words
    }

    public function testWordWrapThrowsForBadWidth(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::wordWrap('abc', 0);
    }

    public function testWordCount(): void
    {
        $this->assertSame(0, Str::wordCount(''));
        $this->assertSame(0, Str::wordCount('   '));
        $this->assertSame(3, Str::wordCount('one two three'));
        $this->assertSame(2, Str::wordCount("it's"));        // apostrophe splits the word
        $this->assertSame(2, Str::wordCount('café résumé')); // multibyte words counted
    }

    public function testFormat(): void
    {
        $this->assertSame('x is 5', Str::format('%s is %d', 'x', 5));
        $this->assertSame('3.14', Str::format('%.2f', 3.14159));
        $this->assertSame('no args', Str::format('no args'));
    }

    public function testScan(): void
    {
        $this->assertSame([42], Str::scan('age:42', 'age:%d'));
        $this->assertSame(['John', 25], Str::scan('John 25', '%s %d'));
        $this->assertSame([null], Str::scan('nope', 'age:%d')); // no match → null slot
    }

    public function testFormatScanRoundTrip(): void
    {
        $formatted = Str::format('%s %d', 'x', 7);
        $this->assertSame(['x', 7], Str::scan($formatted, '%s %d'));
    }

    public function testLevenshtein(): void
    {
        $this->assertSame(0, Str::levenshtein('abc', 'abc'));
        $this->assertSame(3, Str::levenshtein('kitten', 'sitting'));
        $this->assertSame(3, Str::levenshtein('', 'abc'));
    }

    public function testSimilarity(): void
    {
        $this->assertSame(100.0, Str::similarity('abc', 'abc'));
        $this->assertSame(0.0, Str::similarity('abc', 'xyz'));
        $this->assertGreaterThan(50.0, Str::similarity('World', 'word'));
    }

    public function testSplitDefaultSeparator(): void
    {
        $this->assertSame(['a', 'b', 'c'], Str::split('abc'));
        $this->assertSame(['ab', 'cd', 'ef'], Str::split('abcdef', '', 2)); // chunk size via $limit
        $this->assertSame(['é', 'ñ'], Str::split('éñ'));                     // multibyte chars
        $this->assertSame(['a', 'b'], Str::split('a,b', ','));
        $this->assertSame(['a', 'b,c'], Str::split('a,b,c', ',', 2));        // piece limit
    }

    public function testToBytesAndFromBytes(): void
    {
        $this->assertSame([], Str::toBytes(''));
        $this->assertSame([104, 105], Str::toBytes('hi'));
        $this->assertSame([0, 255, 16], Str::toBytes("\x00\xff\x10"));
        $this->assertSame('hi', Str::fromBytes([104, 105]));
        $this->assertSame("\x00\xff", Str::fromBytes([0, 255]));
        $this->assertSame('', Str::fromBytes([]));
        $bin = random_bytes(64);
        $this->assertSame($bin, Str::fromBytes(Str::toBytes($bin))); // round-trip arbitrary binary
    }

    public function testFromBytesRejectsOutOfRange(): void
    {
        $this->expectException(MalformedArgumentException::class);
        Str::fromBytes([0, 256]);
    }

    public function testRenamedMethods(): void
    {
        $this->assertSame(3, Str::len('abc'));
        $this->assertSame(2, Str::byteLen('é'));
        $this->assertSame('ell', Str::sub('hello', 1, 3));
        $this->assertSame('he…', Str::trunc('hello world', 3));
    }

    public function testCapitalizeUncapitalizeMultibyte(): void
    {
        $this->assertSame('Éxito', Str::capitalize('éxito'));
        $this->assertSame('éxito', Str::uncapitalize('Éxito'));
    }

    public function testReplaceAtClampsOutOfRangeStart(): void
    {
        $this->assertSame('ééx', Str::replaceAt('ééé', -1, 1, 'x'));  // multibyte char count
        $this->assertSame('Xbc', Str::replaceAt('abc', 0, 1, 'X'));   // start at zero replaces
        $this->assertSame('Xc', Str::replaceAt('abc', -5, 2, 'X'));   // start clamped to 0
        $this->assertSame('Xbc', Str::replaceAt('abc', -3, 1, 'X'));  // start == -len
    }

    public function testTranslateMultibyteReplacements(): void
    {
        $this->assertSame('éöc', Str::translate('abc', 'ab', 'éö'));
    }

    public function testTranslateLengthMismatchMessage(): void
    {
        $this->expectException(MalformedArgumentException::class);
        $this->expectExceptionMessage('Translation strings must have the same length.');
        Str::translate('x', 'ab', 'xyz');
    }

    public function testSubMultibyteStart(): void
    {
        $this->assertSame('xito', Str::sub('éxito', 1));
    }

    public function testIndexOfCountsMultibyteChars(): void
    {
        $this->assertSame(2, Str::indexOf('ééa', 'a'));
        $this->assertSame(2, Str::lastIndexOf('ééaé', 'a'));
        $this->assertSame(2, Str::lastIndexOf('ééAé', 'a', 0, true));
        $this->assertSame(0, Str::lastIndexOf('abc', 'a'));   // match at position 0
        $this->assertSame(-1, Str::lastIndexOf('', 'x'));     // empty haystack
    }

    public function testAfterMatchAtPositionZero(): void
    {
        $this->assertSame('bc', Str::after('abc', 'a'));
    }

    public function testTruncBoundaries(): void
    {
        $this->assertSame('ééé', Str::trunc('ééé', 4));           // multibyte length fits
        $this->assertSame('abcd', Str::trunc('abcd', 4));         // exact length untouched
        $this->assertSame('..', Str::trunc('abcdef', 2, '...'));  // leading ellipsis chars
        $this->assertSame('ééé…', Str::trunc('ééééé', 4));        // multibyte truncation
    }

    public function testSlugTransliteratesAccents(): void
    {
        // iconv translit output is platform-dependent ('cafe' on glibc, 'caf-e' on
        // libiconv, whose "'e" apostrophe becomes a separator) — assert the shape
        // instead of the exact slug: without transliteration the é is dropped ('caf').
        $slug = Str::slug('café');
        $this->assertStringStartsWith('caf', $slug);
        $this->assertStringEndsWith('e', $slug);
    }

    public function testMaskClampsAndMultibyte(): void
    {
        $this->assertSame('éé*', Str::mask('ééé', -1));           // multibyte char count
        $this->assertSame('**c', Str::mask('abc', -5, 2));        // start clamped to 0
        $this->assertSame('*bc', Str::mask('abc', -3, 1));        // start == -len
        $this->assertSame('xxc', Str::mask('abc', 0, 2, 'xy'));   // first mask char only
        $this->assertSame('é*é', Str::mask('ééé', 1, 1));         // multibyte reconstruction
    }

    public function testSplitClampsNegativeChunkSize(): void
    {
        $this->assertSame(['a', 'b', 'c'], Str::split('abc', '', -5));
    }

    public function testJoinNaturalPrefixSuffixPlacement(): void
    {
        $this->assertSame('<a, b and c>', Str::joinNatural(['a', 'b', 'c'], ', ', '<', '>', ' and '));
        $this->assertSame('', Str::joinNatural(['', ' '], ', ', '<', '>')); // all blank → no wrapping
    }

    public function testChrAcceptsCodepointBounds(): void
    {
        $this->assertSame("\0", Str::chr(0));
        $this->assertSame("\u{10FFFF}", Str::chr(0x10FFFF));
    }

    public function testWordWrapDefaultWidthBoundary(): void
    {
        $fits = str_repeat('a', 37) . ' ' . str_repeat('b', 37);   // 75 chars
        $this->assertSame($fits, Str::wordWrap($fits));

        $wraps = str_repeat('a', 38) . ' ' . str_repeat('b', 37);  // 76 chars
        $this->assertSame(str_repeat('a', 38) . "\n" . str_repeat('b', 37), Str::wordWrap($wraps));

        $longWord = str_repeat('a', 80);
        $this->assertSame($longWord, Str::wordWrap($longWord));    // cut defaults to false
    }

    public function testWordWrapAcceptsWidthOne(): void
    {
        $this->assertSame("a\nb", Str::wordWrap('a b', 1));
    }

    public function testWordCountInvalidUtf8ReturnsZero(): void
    {
        $this->assertSame(0, Str::wordCount("\xC3\x28"));
    }
}
