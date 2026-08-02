<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Filter;
use stdClass;
use Stringable;

/**
 * @internal
 *
 * @coversNothing
 */
final class FilterTest extends TestCase
{
    public function testEscapeHtml(): void
    {
        $this->assertSame(
            '&lt;b&gt;&quot;Tom&quot; &amp; &#039;Jerry&#039;&lt;/b&gt;',
            Filter::escapeHtml('<b>"Tom" & \'Jerry\'</b>'),
        );
        // Multibyte content is preserved; only the markup characters are escaped.
        $this->assertSame('café &lt;b&gt;', Filter::escapeHtml('café <b>'));
    }

    public function testEscapeHtmlDoubleEncode(): void
    {
        $this->assertSame('&amp;amp;', Filter::escapeHtml('&amp;'));
        $this->assertSame('&amp; &lt;', Filter::escapeHtml('&amp; <', false));
    }

    public function testUnescapeHtml(): void
    {
        $this->assertSame(
            '<a> & "x" \'y\'',
            Filter::unescapeHtml('&lt;a&gt; &amp; &quot;x&quot; &#039;y&#039;'),
        );
        $this->assertSame('café', Filter::unescapeHtml(Filter::escapeHtml('café')));
    }

    public function testStripTags(): void
    {
        $this->assertSame('Hi there', Filter::stripTags('<p>Hi <b>there</b></p>'));
        $this->assertSame('Hi <b>there</b>', Filter::stripTags('<p>Hi <b>there</b></p>', '<b>'));
        $this->assertSame('', Filter::stripTags('<br/>'));
    }

    public function testDigits(): void
    {
        $this->assertSame('123', Filter::digits('a1b2c3'));
        $this->assertSame('5511999990000', Filter::digits('+55 (11) 99999-0000'));
        $this->assertSame('', Filter::digits('no digits here'));
        // Full-width digits are not ASCII 0-9, so they are removed.
        $this->assertSame('2', Filter::digits('４2'));
    }

    public function testAlpha(): void
    {
        $this->assertSame('abcdef', Filter::alpha('abc123 def'));
        $this->assertSame('café', Filter::alpha('café 1!'));
        $this->assertSame('', Filter::alpha('123 456'));
    }

    public function testAlnum(): void
    {
        $this->assertSame('a1b2café', Filter::alnum('a1 b2! café'));
        $this->assertSame('', Filter::alnum('!@#$ %^&'));
    }

    public function testSquish(): void
    {
        $this->assertSame('a b c', Filter::squish("  a\t\n b   c  "));
        $this->assertSame('', Filter::squish("   \t\n  "));
        $this->assertSame('one two', Filter::squish('one two'));
    }

    public function testStripControl(): void
    {
        $this->assertSame('abcd', Filter::stripControl("a\tb\nc\0d"));
        $this->assertSame('café', Filter::stripControl("caf\x00é"));
        $this->assertSame('plain', Filter::stripControl('plain'));
    }

    public function testAscii(): void
    {
        // ASCII passes through untouched.
        $this->assertSame('Hello World 123!', Filter::ascii('Hello World 123!'));
        // Accented input is transliterated to an ASCII-only string (the exact
        // mapping is platform-dependent, so assert the contract, not literals).
        $result = Filter::ascii('café résumé');
        $this->assertSame(1, preg_match('/^[\x00-\x7F]*$/', $result));
        $this->assertStringContainsString('caf', $result);
    }

    public function testEmail(): void
    {
        $this->assertSame('john.doe@example.com', Filter::email('john.doe@example.com'));
        $this->assertSame('johndoe@example.com', Filter::email('john doe@example.com'));
        // Illegal characters (here the angle brackets) are dropped, not the
        // text between them.
        $this->assertSame('a@b.comscript', Filter::email('a@b.com<script>'));
    }

    public function testIsEmail(): void
    {
        $this->assertTrue(Filter::isEmail('john.doe@example.com'));
        $this->assertTrue(Filter::isEmail('a+tag@sub.example.co.uk'));

        $this->assertFalse(Filter::isEmail(''));
        $this->assertFalse(Filter::isEmail('john.doe'));            // no @
        $this->assertFalse(Filter::isEmail('@example.com'));        // no local part
        $this->assertFalse(Filter::isEmail('john@'));               // no domain
        $this->assertFalse(Filter::isEmail('john doe@example.com')); // inner space
        $this->assertFalse(Filter::isEmail(' john@example.com '));  // surrounding whitespace
        $this->assertFalse(Filter::isEmail('john@localhost'));      // dot-less domain
    }

    public function testIsEmailDoesNotValidateWhatEmailSanitises(): void
    {
        // Sanitising is not validating: email() drops the illegal characters and
        // the *result* passes isEmail(), but the input it came from never did.
        $this->assertFalse(Filter::isEmail('a@b.com<script>'));
        $this->assertSame('a@b.comscript', Filter::email('a@b.com<script>'));
        $this->assertTrue(Filter::isEmail(Filter::email('a@b.com<script>')));
    }

    public function testUrl(): void
    {
        $this->assertSame('https://example.com/path', Filter::url('https://example.com/path'));
        $this->assertSame('https://example.com/a%20b', Filter::url('https://example.com/a%20b'));
        $this->assertSame('https://examplecom', Filter::url('https://example“com'));
    }

    #[DataProvider('toStrProvider')]
    public function testToStr(mixed $value, ?string $expected): void
    {
        $this->assertSame($expected, Filter::toStr($value));
    }

    /**
     * @return iterable<string, array{mixed, ?string}>
     */
    public static function toStrProvider(): iterable
    {
        yield 'string' => ['hi', 'hi'];

        yield 'empty' => ['', ''];

        yield 'int' => [42, '42'];

        yield 'float' => [1.5, '1.5'];

        yield 'true' => [true, '1'];

        yield 'false' => [false, ''];

        yield 'stringable' => [new class implements Stringable {
            public function __toString(): string
            {
                return 'str';
            }
        }, 'str'];

        yield 'null' => [null, null];

        yield 'array' => [['a'], null];

        yield 'object' => [new stdClass(), null];
    }

    public function testToStrUsesDefault(): void
    {
        $this->assertSame('fallback', Filter::toStr(null, 'fallback'));
        $this->assertSame('hi', Filter::toStr('hi', 'fallback'));
    }

    #[DataProvider('toIntProvider')]
    public function testToInt(mixed $value, ?int $expected): void
    {
        $this->assertSame($expected, Filter::toInt($value));
    }

    /**
     * @return iterable<string, array{mixed, ?int}>
     */
    public static function toIntProvider(): iterable
    {
        yield 'int' => [42, 42];

        yield 'string' => ['42', 42];

        yield 'padded' => [' 42 ', 42];

        yield 'negative' => ['-7', -7];

        yield 'plus' => ['+7', 7];

        yield 'decimal str' => ['3.14', null];

        yield 'word' => ['abc', null];

        yield 'empty' => ['', null];

        yield 'float whole' => [3.0, 3];

        yield 'float frac' => [3.5, null];

        yield 'infinite' => [INF, null];

        yield 'bool' => [true, null];

        yield 'null' => [null, null];

        yield 'array' => [[1], null];
    }

    public function testToIntUsesDefault(): void
    {
        $this->assertSame(99, Filter::toInt('nope', 99));
        $this->assertSame(5, Filter::toInt(5, 99));
    }

    #[DataProvider('toFloatProvider')]
    public function testToFloat(mixed $value, ?float $expected): void
    {
        $this->assertSame($expected, Filter::toFloat($value));
    }

    /**
     * @return iterable<string, array{mixed, ?float}>
     */
    public static function toFloatProvider(): iterable
    {
        yield 'int' => [42, 42.0];

        yield 'float' => [1.5, 1.5];

        yield 'string' => ['3.14', 3.14];

        yield 'padded' => [' 3.14 ', 3.14];

        yield 'exponent' => ['1e3', 1000.0];

        yield 'word' => ['abc', null];

        yield 'empty' => ['', null];

        yield 'bool' => [true, null];

        yield 'null' => [null, null];

        yield 'array' => [[1.0], null];
    }

    public function testToFloatUsesDefault(): void
    {
        $this->assertSame(1.0, Filter::toFloat('nope', 1.0));
        $this->assertSame(2.5, Filter::toFloat(2.5, 1.0));
    }

    #[DataProvider('toBoolProvider')]
    public function testToBool(mixed $value, ?bool $expected): void
    {
        $this->assertSame($expected, Filter::toBool($value));
    }

    /**
     * @return iterable<string, array{mixed, ?bool}>
     */
    public static function toBoolProvider(): iterable
    {
        yield 'true' => [true, true];

        yield 'false' => [false, false];

        yield 'int 1' => [1, true];

        yield 'int 0' => [0, false];

        yield 'int 2' => [2, null];

        yield 'str 1' => ['1', true];

        yield 'str true' => ['true', true];

        yield 'str upper' => ['TRUE', true];

        yield 'str on' => ['on', true];

        yield 'str yes' => ['yes', true];

        yield 'str padded' => [' yes ', true];

        yield 'str 0' => ['0', false];

        yield 'str false' => ['false', false];

        yield 'str off' => ['off', false];

        yield 'str no' => ['no', false];

        yield 'str empty' => ['', false];

        yield 'str maybe' => ['maybe', null];

        yield 'float' => [1.0, null];

        yield 'null' => [null, null];

        yield 'array' => [[true], null];
    }

    public function testToBoolUsesDefault(): void
    {
        $this->assertTrue(Filter::toBool('maybe', true));
        $this->assertFalse(Filter::toBool('on', false) === null);
        $this->assertTrue(Filter::toBool('on', false));
    }

    public function testToIntToFloatPreferParsedValueOverDefault(): void
    {
        $this->assertSame(42, Filter::toInt('42', 5));
        $this->assertSame(4.2, Filter::toFloat('4.2', 9.9));
    }
}
