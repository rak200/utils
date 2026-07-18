<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Rand;
use UnderflowException;

/**
 * @internal
 *
 * @coversNothing
 */
final class RandTest extends TestCase
{
    public function testIntInRange(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $value = Rand::int(5, 10);
            $this->assertGreaterThanOrEqual(5, $value);
            $this->assertLessThanOrEqual(10, $value);
        }
    }

    public function testIntRejectsMinGreaterThanMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::int(10, 5);
    }

    public function testFloatInRange(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $value = Rand::float(0.0, 1.0);
            $this->assertGreaterThanOrEqual(0.0, $value);
            $this->assertLessThanOrEqual(1.0, $value);
        }
    }

    public function testFloatRejectsMinGreaterThanMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::float(1.0, 0.0);
    }

    public function testBytesProducesRequestedLength(): void
    {
        $this->assertSame(16, strlen(Rand::bytes(16)));
        $this->assertSame(1, strlen(Rand::bytes(1)));
    }

    public function testBytesRejectsZeroLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::bytes(0);
    }

    public function testStringDefaultAlphabet(): void
    {
        $value = Rand::string(20);
        $this->assertSame(20, strlen($value));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $value);
    }

    public function testStringWithCustomAlphabet(): void
    {
        $value = Rand::string(10, Rand::HEX);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $value);
    }

    public function testStringRejectsEmptyAlphabet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::string(10, '');
    }

    public function testStringRejectsZeroLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::string(0);
    }

    public function testMaskedReplacesHashCharacters(): void
    {
        $value = Rand::masked('###-###', Rand::NUM);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{3}$/', $value);
    }

    public function testMaskedPreservesLiteralCharacters(): void
    {
        $value = Rand::masked('user_###', Rand::HEX);
        $this->assertMatchesRegularExpression('/^user_[0-9a-f]{3}$/', $value);
    }

    public function testMaskedRejectsEmptyPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::masked('', Rand::NUM);
    }

    public function testMaskedRejectsEmptyAlphabet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::masked('###', '');
    }

    public function testUuidV4Format(): void
    {
        $uuid = Rand::uuidV4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuidsAreUnique(): void
    {
        $a = Rand::uuidV4();
        $b = Rand::uuidV4();
        $this->assertNotSame($a, $b);
    }

    public function testUuidV7Format(): void
    {
        $uuid = Rand::uuidV7();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuidV7IsTimeOrdered(): void
    {
        $a = Rand::uuidV7();
        usleep(2000);
        $b = Rand::uuidV7();
        $this->assertGreaterThan(0, strcmp($b, $a));
    }

    public function testUlidIsTwentySixChars(): void
    {
        $ulid = Rand::ulid();
        $this->assertSame(26, strlen($ulid));
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $ulid);
    }

    public function testUlidIsTimeOrdered(): void
    {
        $a = Rand::ulid();
        usleep(2000);
        $b = Rand::ulid();
        $this->assertGreaterThan(0, strcmp($b, $a));
    }

    public function testNanoidDefaultLength(): void
    {
        $id = Rand::nanoid();
        $this->assertSame(21, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $id);
    }

    public function testNanoidCustomLength(): void
    {
        $this->assertSame(10, strlen(Rand::nanoid(10)));
    }

    public function testNanoidRejectsZeroLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::nanoid(0);
    }

    public function testBoolEventuallyProducesBothValues(): void
    {
        $seen = [];
        for ($i = 0; $i < 100; ++$i) {
            $seen[Rand::bool() ? 'true' : 'false'] = true;
        }
        $this->assertCount(2, $seen);
    }

    public function testChoicePicksFromArray(): void
    {
        $items = [10, 20, 30, 40];
        for ($i = 0; $i < 50; ++$i) {
            $this->assertContains(Rand::choice($items), $items);
        }
    }

    public function testChoicePreservesAssocValues(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        for ($i = 0; $i < 30; ++$i) {
            $this->assertContains(Rand::choice($items), [1, 2, 3]);
        }
    }

    public function testChoiceRejectsEmpty(): void
    {
        $this->expectException(UnderflowException::class);
        Rand::choice([]);
    }

    public function testShufflePreservesMultisetAndReindexes(): void
    {
        $shuffled = Rand::shuffle([1, 2, 3, 4, 5]);
        $this->assertCount(5, $shuffled);
        $this->assertSame([0, 1, 2, 3, 4], array_keys($shuffled));
        sort($shuffled);
        $this->assertSame([1, 2, 3, 4, 5], $shuffled);
    }

    public function testShuffleEmptyReturnsEmpty(): void
    {
        $this->assertSame([], Rand::shuffle([]));
    }

    public function testIsUuid(): void
    {
        $this->assertTrue(Rand::isUuid(Rand::uuidV4()));
        $this->assertTrue(Rand::isUuid(Rand::uuidV7()));
        $this->assertTrue(Rand::isUuid(Rand::uuidV4(), 4));
        $this->assertTrue(Rand::isUuid(Rand::uuidV7(), 7));
        $this->assertFalse(Rand::isUuid(Rand::uuidV4(), 7));
        $this->assertFalse(Rand::isUuid('not-a-uuid'));
        $this->assertFalse(Rand::isUuid(''));
        $this->assertTrue(Rand::isUuid('550E8400-E29B-41D4-A716-446655440000'));   // uppercase
    }

    public function testIsUlid(): void
    {
        $this->assertTrue(Rand::isUlid(Rand::ulid()));
        $this->assertTrue(Rand::isUlid('01HXP2K8FJM5E7R3Q6Y2N0V1WB'));
        $this->assertFalse(Rand::isUlid('nope'));
        $this->assertFalse(Rand::isUlid('81HXP2K8FJM5E7R3Q6Y2N0V1WB'));   // first char > 7
        $this->assertFalse(Rand::isUlid('01HXP2K8FJM5E7R3Q6Y2N0V1WI'));   // 'I' not in alphabet
    }

    public function testUuidV7Time(): void
    {
        $before = time();
        $dt = Rand::uuidV7Time(Rand::uuidV7());
        $after = time();
        $this->assertGreaterThanOrEqual($before, $dt->getTimestamp());
        $this->assertLessThanOrEqual($after + 1, $dt->getTimestamp());
    }

    public function testUuidV7TimeOrNullReturnsNullForNonV7(): void
    {
        $this->assertNull(Rand::uuidV7TimeOrNull(Rand::uuidV4()));
        $this->assertNull(Rand::uuidV7TimeOrNull('not-a-uuid'));
    }

    public function testUuidV7TimeThrowsOnInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::uuidV7Time(Rand::uuidV4());
    }

    public function testUlidTime(): void
    {
        $before = time();
        $dt = Rand::ulidTime(Rand::ulid());
        $after = time();
        $this->assertGreaterThanOrEqual($before, $dt->getTimestamp());
        $this->assertLessThanOrEqual($after + 1, $dt->getTimestamp());
    }

    public function testUlidTimeOrNullReturnsNullForInvalid(): void
    {
        $this->assertNull(Rand::ulidTimeOrNull('nope'));
    }

    public function testUlidTimeThrowsOnInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rand::ulidTime('nope');
    }

    public function testIntAllowsMinEqualToMax(): void
    {
        $this->assertSame(5, Rand::int(5, 5));
    }

    public function testFloatAllowsMinEqualToMax(): void
    {
        $this->assertSame(2.5, Rand::float(2.5, 2.5));
    }

    public function testFloatStaysWithinPositiveRange(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $value = Rand::float(2.0, 4.0);
            $this->assertGreaterThanOrEqual(2.0, $value);
            $this->assertLessThanOrEqual(4.0, $value);
        }
    }

    public function testStringAcceptsLengthOne(): void
    {
        $this->assertSame(1, strlen(Rand::string(1)));
    }

    public function testStringReachesWholeAlphabet(): void
    {
        $value = Rand::string(200, 'xy');
        $this->assertMatchesRegularExpression('/^[xy]{200}$/', $value);
        $this->assertStringContainsString('x', $value);
        $this->assertStringContainsString('y', $value);
    }

    public function testMaskedReachesWholeAlphabet(): void
    {
        $value = Rand::masked(str_repeat('#', 200), 'xy');
        $this->assertMatchesRegularExpression('/^[xy]{200}$/', $value);
        $this->assertStringContainsString('x', $value);
        $this->assertStringContainsString('y', $value);
    }

    public function testNanoidAcceptsLengthOne(): void
    {
        $this->assertSame(1, strlen(Rand::nanoid(1)));
    }

    public function testUuidV4NibblesAreIndependent(): void
    {
        $this->assertUuidNibblesIndependent(Rand::uuidV4(...));
    }

    public function testUuidV7NibblesAreIndependent(): void
    {
        $this->assertUuidNibblesIndependent(Rand::uuidV7(...));
    }

    public function testUuidV7TimeIsMillisecondAccurate(): void
    {
        for ($i = 0; $i < 20; ++$i) {
            $before = (int) floor(microtime(true) * 1000);
            $ms = (int) Rand::uuidV7Time(Rand::uuidV7())->format('Uv');
            $after = (int) ceil(microtime(true) * 1000) + 1;
            $this->assertGreaterThanOrEqual($before, $ms);
            $this->assertLessThanOrEqual($after, $ms);
        }
    }

    public function testIsUuidRejectsInvalidVariantNibble(): void
    {
        $this->assertFalse(Rand::isUuid('aaaaaaaa-aaaa-4aaa-caaa-aaaaaaaaaaaa', 4));
    }

    public function testChoiceReachesAllElements(): void
    {
        $seen = [];
        for ($i = 0; $i < 100; ++$i) {
            $seen[Rand::choice(['a', 'b'])] = true;
        }
        $this->assertCount(2, $seen);
    }

    public function testShuffleMovesEveryPosition(): void
    {
        $input = [1, 2, 3, 4, 5];
        $firstMoved = false;
        $lastMoved = false;
        for ($i = 0; $i < 30; ++$i) {
            $shuffled = Rand::shuffle($input);
            $sorted = $shuffled;
            sort($sorted);
            $this->assertSame($input, $sorted);
            $firstMoved = $firstMoved || $shuffled[0] !== 1;
            $lastMoved = $lastMoved || $shuffled[4] !== 5;
        }
        $this->assertTrue($firstMoved);
        $this->assertTrue($lastMoved);
    }

    /**
     * The version/variant masks must read exactly their own byte: across draws
     * the nibble right of the version (hex 13) and right of the variant (hex 17)
     * take both parities (their low bit stays random) and decorrelate from the
     * neighbouring bytes' nibbles (hex 11/15 and 15/19).
     *
     * @param callable(): string $generate
     */
    private function assertUuidNibblesIndependent(callable $generate): void
    {
        $h13Parities = [];
        $h17Parities = [];
        $h13DiffersFromH11 = false;
        $h13DiffersFromH15 = false;
        $h17DiffersFromH15 = false;
        $h17DiffersFromH19 = false;
        for ($i = 0; $i < 40; ++$i) {
            $hex = str_replace('-', '', $generate());
            $h13Parities[hexdec($hex[13]) % 2] = true;
            $h17Parities[hexdec($hex[17]) % 2] = true;
            $h13DiffersFromH11 = $h13DiffersFromH11 || $hex[13] !== $hex[11];
            $h13DiffersFromH15 = $h13DiffersFromH15 || $hex[13] !== $hex[15];
            $h17DiffersFromH15 = $h17DiffersFromH15 || $hex[17] !== $hex[15];
            $h17DiffersFromH19 = $h17DiffersFromH19 || $hex[17] !== $hex[19];
        }
        $this->assertCount(2, $h13Parities);
        $this->assertCount(2, $h17Parities);
        $this->assertTrue($h13DiffersFromH11);
        $this->assertTrue($h13DiffersFromH15);
        $this->assertTrue($h17DiffersFromH15);
        $this->assertTrue($h17DiffersFromH19);
    }
}
