<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Rand;
use RuntimeException;

final class RandTest extends TestCase {
    public function testIntInRange(): void {
        for ($i = 0; $i < 100; $i++) {
            $value = Rand::int(5, 10);
            $this->assertGreaterThanOrEqual(5, $value);
            $this->assertLessThanOrEqual(10, $value);
        }
    }

    public function testIntRejectsMinGreaterThanMax(): void {
        $this->expectException(RuntimeException::class);
        Rand::int(10, 5);
    }

    public function testFloatInRange(): void {
        for ($i = 0; $i < 100; $i++) {
            $value = Rand::float(0.0, 1.0);
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(1.0, $value);
        }
    }

    public function testFloatRejectsMinGreaterThanMax(): void {
        $this->expectException(RuntimeException::class);
        Rand::float(1.0, 0.0);
    }

    public function testBytesProducesRequestedLength(): void {
        $this->assertSame(16, strlen(Rand::bytes(16)));
        $this->assertSame(1, strlen(Rand::bytes(1)));
    }

    public function testBytesRejectsZeroLength(): void {
        $this->expectException(RuntimeException::class);
        Rand::bytes(0);
    }

    public function testStringDefaultAlphabet(): void {
        $value = Rand::string(20);
        $this->assertSame(20, strlen($value));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $value);
    }

    public function testStringWithCustomAlphabet(): void {
        $value = Rand::string(10, Rand::HEX);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $value);
    }

    public function testStringRejectsEmptyAlphabet(): void {
        $this->expectException(RuntimeException::class);
        Rand::string(10, '');
    }

    public function testStringRejectsZeroLength(): void {
        $this->expectException(RuntimeException::class);
        Rand::string(0);
    }

    public function testMaskedReplacesHashCharacters(): void {
        $value = Rand::masked('###-###', Rand::NUM);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{3}$/', $value);
    }

    public function testMaskedPreservesLiteralCharacters(): void {
        $value = Rand::masked('user_###', Rand::HEX);
        $this->assertMatchesRegularExpression('/^user_[0-9a-f]{3}$/', $value);
    }

    public function testMaskedRejectsEmptyPattern(): void {
        $this->expectException(RuntimeException::class);
        Rand::masked('', Rand::NUM);
    }

    public function testUuidV4Format(): void {
        $uuid = Rand::uuid();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuidsAreUnique(): void {
        $a = Rand::uuid();
        $b = Rand::uuid();
        $this->assertNotSame($a, $b);
    }

    public function testUuidV7Format(): void {
        $uuid = Rand::uuidV7();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuidV7IsTimeOrdered(): void {
        $a = Rand::uuidV7();
        usleep(2000);
        $b = Rand::uuidV7();
        $this->assertGreaterThan(0, strcmp($b, $a));
    }

    public function testUlidIsTwentySixChars(): void {
        $ulid = Rand::ulid();
        $this->assertSame(26, strlen($ulid));
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $ulid);
    }

    public function testUlidIsTimeOrdered(): void {
        $a = Rand::ulid();
        usleep(2000);
        $b = Rand::ulid();
        $this->assertGreaterThan(0, strcmp($b, $a));
    }

    public function testNanoidDefaultLength(): void {
        $id = Rand::nanoid();
        $this->assertSame(21, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $id);
    }

    public function testNanoidCustomLength(): void {
        $this->assertSame(10, strlen(Rand::nanoid(10)));
    }

    public function testNanoidRejectsZeroLength(): void {
        $this->expectException(RuntimeException::class);
        Rand::nanoid(0);
    }
}
