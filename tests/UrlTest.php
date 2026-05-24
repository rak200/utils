<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Url;
use RuntimeException;

final class UrlTest extends TestCase {
    public function testParseReturnsAllComponents(): void {
        $parsed = Url::parse('https://user:pass@example.com:8080/path/to?x=1&y=2#frag');
        $this->assertSame('https', $parsed['scheme']);
        $this->assertSame('user', $parsed['user']);
        $this->assertSame('pass', $parsed['pass']);
        $this->assertSame('example.com', $parsed['host']);
        $this->assertSame(8080, $parsed['port']);
        $this->assertSame('/path/to', $parsed['path']);
        $this->assertSame('x=1&y=2', $parsed['query']);
        $this->assertSame('frag', $parsed['fragment']);
    }

    public function testParseAcceptsRelativeUrl(): void {
        $parsed = Url::parse('/just/a/path?q=1');
        $this->assertArrayNotHasKey('scheme', $parsed);
        $this->assertSame('/just/a/path', $parsed['path']);
        $this->assertSame('q=1', $parsed['query']);
    }

    public function testParseThrowsOnMalformed(): void {
        $this->expectException(RuntimeException::class);
        Url::parse('http:///bad-url');
    }

    public function testParseOrNullReturnsNullOnMalformed(): void {
        $this->assertNull(Url::parseOrNull('http:///bad-url'));
    }

    public function testBuildRoundTripsParse(): void {
        $url = 'https://user:pass@example.com:8080/path?x=1#frag';
        $this->assertSame($url, Url::build(Url::parse($url)));
    }

    public function testBuildOmitsMissingComponents(): void {
        $this->assertSame('https://example.com/path', Url::build([
            'scheme' => 'https',
            'host'   => 'example.com',
            'path'   => '/path',
        ]));
    }

    public function testBuildSkipsEmptyQuery(): void {
        $this->assertSame('https://example.com/path', Url::build([
            'scheme' => 'https',
            'host'   => 'example.com',
            'path'   => '/path',
            'query'  => '',
        ]));
    }

    public function testBuildIncludesUserOnly(): void {
        $this->assertSame('https://user@example.com', Url::build([
            'scheme' => 'https',
            'user'   => 'user',
            'host'   => 'example.com',
        ]));
    }

    public function testEncodeProducesRfc3986(): void {
        $this->assertSame('hello%20world', Url::encode('hello world'));
        $this->assertSame('a%2Fb%2Bc', Url::encode('a/b+c'));
    }

    public function testDecodeReverses(): void {
        $this->assertSame('hello world', Url::decode('hello%20world'));
        $this->assertSame('a/b+c', Url::decode('a%2Fb%2Bc'));
    }

    public function testEncodeQueryHandlesArraysAndSpaces(): void {
        $this->assertSame(
            'name=John%20Doe&tag%5B0%5D=php&tag%5B1%5D=web',
            Url::encodeQuery(['name' => 'John Doe', 'tag' => ['php', 'web']]),
        );
    }

    public function testDecodeQueryRebuildsArrays(): void {
        $this->assertSame(
            ['name' => 'John Doe', 'tag' => ['php', 'web']],
            Url::decodeQuery('name=John%20Doe&tag%5B0%5D=php&tag%5B1%5D=web'),
        );
    }

    public function testEncodeAndDecodeQueryRoundTrip(): void {
        $params = ['filter' => ['status' => 'active', 'tags' => ['a', 'b']], 'q' => 'x y'];
        $this->assertSame($params, Url::decodeQuery(Url::encodeQuery($params)));
    }

    public function testIsAbsoluteRecognisesScheme(): void {
        $this->assertTrue(Url::isAbsolute('https://example.com'));
        $this->assertTrue(Url::isAbsolute('mailto:a@b.com'));
        $this->assertTrue(Url::isAbsolute('file:///etc/hosts'));
    }

    public function testIsAbsoluteRejectsRelative(): void {
        $this->assertFalse(Url::isAbsolute('/path/only'));
        $this->assertFalse(Url::isAbsolute('relative/path'));
        $this->assertFalse(Url::isAbsolute(''));
    }
}
