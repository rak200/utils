<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use ArrayIterator;
use BcMath\Number;
use Countable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Type;
use stdClass;
use Stringable;

/**
 * @internal
 *
 * @coversNothing
 */
final class TypeTest extends TestCase
{
    public function testOf(): void
    {
        $this->assertSame('int', Type::of(42));
        $this->assertSame('float', Type::of(1.5));
        $this->assertSame('string', Type::of('hello'));
        $this->assertSame('bool', Type::of(true));
        $this->assertSame('null', Type::of(null));
        $this->assertSame('array', Type::of([1, 2, 3]));
        $this->assertSame('stdClass', Type::of(new stdClass()));
        $this->assertSame(ArrayIterator::class, Type::of(new ArrayIterator([])));
        $this->assertSame('Closure', Type::of(static fn (): int => 1));

        $fp = fopen('php://memory', 'rb');
        $this->assertNotFalse($fp);
        $this->assertSame('resource (stream)', Type::of($fp));
        fclose($fp);
        $this->assertSame('resource (closed)', Type::of($fp));
    }

    #[DataProvider('isStrProvider')]
    public function testIsStr(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isStr($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isStrProvider(): iterable
    {
        yield 'empty' => ['', true];

        yield 'word' => ['hello', true];

        yield 'int' => [42, false];

        yield 'null' => [null, false];

        yield 'array' => [['a'], false];
    }

    #[DataProvider('isBoolProvider')]
    public function testIsBool(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isBool($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isBoolProvider(): iterable
    {
        yield 'true' => [true, true];

        yield 'false' => [false, true];

        yield 'int 0' => [0, false];

        yield 'int 1' => [1, false];

        yield 'string' => ['true', false];

        yield 'null' => [null, false];
    }

    #[DataProvider('isIntProvider')]
    public function testIsInt(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isInt($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isIntProvider(): iterable
    {
        yield 'zero' => [0, true];

        yield 'negative' => [-7, true];

        yield 'max' => [PHP_INT_MAX, true];

        yield 'float' => [1.5, false];

        yield 'string' => ['42', false];

        yield 'bool' => [true, false];
    }

    #[DataProvider('isFloatProvider')]
    public function testIsFloat(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isFloat($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isFloatProvider(): iterable
    {
        yield '1.5' => [1.5, true];

        yield 'zero' => [0.0, true];

        yield 'inf' => [INF, true];

        yield 'nan' => [NAN, true];

        yield 'int' => [1, false];

        yield 'string' => ['1.5', false];
    }

    #[DataProvider('isArrayProvider')]
    public function testIsArray(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isArray($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isArrayProvider(): iterable
    {
        yield 'empty' => [[], true];

        yield 'list' => [[1, 2, 3], true];

        yield 'assoc' => [['a' => 1], true];

        yield 'iterator' => [new ArrayIterator([1, 2]), false];

        yield 'string' => ['a,b,c', false];
    }

    #[DataProvider('isObjectProvider')]
    public function testIsObject(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isObject($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isObjectProvider(): iterable
    {
        yield 'stdClass' => [new stdClass(), true];

        yield 'closure' => [static fn (): int => 1, true];

        yield 'string' => ['stdClass', false];

        yield 'array' => [[], false];

        yield 'null' => [null, false];
    }

    #[DataProvider('isEnumProvider')]
    public function testIsEnum(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isEnum($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isEnumProvider(): iterable
    {
        yield 'pure case' => [Suit::Hearts, true];

        yield 'backed case' => [Status::Active, true];

        yield 'class-string' => [Suit::class, false];

        yield 'string' => ['Hearts', false];

        yield 'object' => [new stdClass(), false];

        yield 'int' => [42, false];

        yield 'null' => [null, false];
    }

    public function testUsesTrait(): void
    {
        $this->assertTrue(Type::usesTrait(new UsesGreet(), GreetTrait::class));
        $this->assertTrue(Type::usesTrait(UsesGreet::class, GreetTrait::class));
        $this->assertTrue(Type::usesTrait(new UsesComposed(), ComposedTrait::class));

        // Inherited from a parent class — only matched recursively.
        $this->assertFalse(Type::usesTrait(new ChildUsesGreet(), GreetTrait::class));
        $this->assertTrue(Type::usesTrait(new ChildUsesGreet(), GreetTrait::class, recursive: true));

        // Nested (a trait used by another trait) — only matched recursively.
        $this->assertFalse(Type::usesTrait(new UsesComposed(), NestedTrait::class));
        $this->assertTrue(Type::usesTrait(new UsesComposed(), NestedTrait::class, recursive: true));

        $this->assertFalse(Type::usesTrait(new stdClass(), GreetTrait::class));
        $this->assertFalse(Type::usesTrait('NotAClass_xyz123', GreetTrait::class));
        $this->assertFalse(Type::usesTrait(42, GreetTrait::class));
        $this->assertFalse(Type::usesTrait(null, GreetTrait::class));
    }

    #[DataProvider('isCallableProvider')]
    public function testIsCallable(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isCallable($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isCallableProvider(): iterable
    {
        yield 'closure' => [static fn (): int => 1, true];

        yield 'function' => ['strlen', true];

        yield 'method array' => [[DateTimeImmutable::class, 'createFromFormat'], true];

        yield 'missing' => ['this_function_does_not_exist_xyz', false];

        yield 'int' => [42, false];
    }

    #[DataProvider('isIterableProvider')]
    public function testIsIterable(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isIterable($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isIterableProvider(): iterable
    {
        yield 'empty array' => [[], true];

        yield 'list' => [[1, 2], true];

        yield 'iterator' => [new ArrayIterator([1]), true];

        yield 'object' => [new stdClass(), false];

        yield 'string' => ['abc', false];
    }

    #[DataProvider('isNullProvider')]
    public function testIsNull(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isNull($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isNullProvider(): iterable
    {
        yield 'null' => [null, true];

        yield 'false' => [false, false];

        yield 'int 0' => [0, false];

        yield 'empty str' => ['', false];

        yield 'array' => [[], false];
    }

    #[DataProvider('isScalarProvider')]
    public function testIsScalar(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isScalar($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isScalarProvider(): iterable
    {
        yield 'int' => [1, true];

        yield 'float' => [1.5, true];

        yield 'string' => ['hello', true];

        yield 'bool' => [true, true];

        yield 'null' => [null, false];

        yield 'array' => [[], false];

        yield 'object' => [new stdClass(), false];
    }

    #[DataProvider('isNumericProvider')]
    public function testIsNumeric(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isNumeric($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isNumericProvider(): iterable
    {
        yield 'int' => [42, true];

        yield 'float' => [1.5, true];

        yield 'numeric string' => ['1.5', true];

        yield 'exponent string' => ['-1e3', true];

        yield 'Number' => [new Number('123.45'), true];

        yield 'non-numeric string' => ['hello', false];

        yield 'null' => [null, false];

        yield 'bool' => [true, false];

        yield 'leading+trailing ws' => [' 42 ', false];

        yield 'leading ws' => [' 42', false];

        yield 'trailing newline' => ["42\n", false];

        yield 'trailing tab' => ["1.5\t", false];
    }

    #[DataProvider('isResourceProvider')]
    public function testIsResource(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isResource($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isResourceProvider(): iterable
    {
        $open = fopen('php://memory', 'rb');
        $closed = fopen('php://memory', 'rb');
        if ($closed !== false) {
            fclose($closed);
        }

        yield 'open stream' => [$open, true];

        yield 'closed stream' => [$closed, false];

        yield 'string' => ['php://memory', false];

        yield 'int' => [42, false];
    }

    #[DataProvider('isNumericStrProvider')]
    public function testIsNumericStr(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isNumericStr($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isNumericStrProvider(): iterable
    {
        yield 'int string' => ['42', true];

        yield 'negative decimal' => ['-1.5', true];

        yield 'exponent' => ['1e3', true];

        yield 'native int' => [42, false];

        yield 'non-numeric' => ['hello', false];

        yield 'empty' => ['', false];

        yield 'leading+trailing ws' => [' 42 ', false];

        yield 'leading ws' => [' 42', false];

        yield 'trailing newline' => ["42\n", false];

        yield 'trailing tab' => ["1.5\t", false];
    }

    #[DataProvider('isIntLikeProvider')]
    public function testIsIntLike(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isIntLike($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isIntLikeProvider(): iterable
    {
        yield 'native int' => [42, true];

        yield 'negative int' => [-7, true];

        yield 'int string' => ['42', true];

        yield 'negative string' => ['-7', true];

        yield 'leading plus zero' => ['+0', true];

        yield 'decimal string' => ['1.0', false];

        yield 'exponent string' => ['1e3', false];

        yield 'surrounding ws' => [' 42 ', false];

        yield 'empty' => ['', false];

        yield 'float' => [1.5, false];

        yield 'bool' => [true, false];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('isInstanceOfProvider')]
    public function testIsInstance(mixed $value, string $class, bool $expected): void
    {
        $this->assertSame($expected, Type::isInstance($value, $class));
    }

    /**
     * @return iterable<string, array{mixed, class-string, bool}>
     */
    public static function isInstanceOfProvider(): iterable
    {
        yield 'stdClass matches' => [new stdClass(), stdClass::class, true];

        yield 'interface matches' => [new ArrayIterator([]), Countable::class, true];

        yield 'class-string arg' => [stdClass::class, stdClass::class, false];

        yield 'wrong class' => [new stdClass(), ArrayIterator::class, false];

        yield 'null' => [null, stdClass::class, false];
    }

    #[DataProvider('isClassNameProvider')]
    public function testIsClassName(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isClassName($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isClassNameProvider(): iterable
    {
        yield 'stdClass' => [stdClass::class, true];

        yield 'ArrayIterator' => [ArrayIterator::class, true];

        yield 'interface' => [Countable::class, false];

        yield 'missing' => ['NotAClass_xyz123', false];

        yield 'int' => [42, false];

        yield 'null' => [null, false];

        yield 'array' => [[], false];

        yield 'object' => [new stdClass(), false];
    }

    #[DataProvider('isInterfaceNameProvider')]
    public function testIsInterfaceName(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Type::isInterfaceName($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function isInterfaceNameProvider(): iterable
    {
        yield 'Countable' => [Countable::class, true];

        yield 'Stringable' => [Stringable::class, true];

        yield 'class' => [stdClass::class, false];

        yield 'missing' => ['NotAnInterface_xyz123', false];

        yield 'int' => [42, false];

        yield 'null' => [null, false];

        yield 'array' => [[], false];

        yield 'object' => [new stdClass(), false];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('isAProvider')]
    public function testIsA(mixed $value, string $class, bool $expected): void
    {
        $this->assertSame($expected, Type::isA($value, $class));
    }

    /**
     * @return iterable<string, array{mixed, class-string, bool}>
     */
    public static function isAProvider(): iterable
    {
        yield 'instance same' => [new stdClass(), stdClass::class, true];

        yield 'instance iface' => [new ArrayIterator([]), Countable::class, true];

        yield 'class-string same' => [stdClass::class, stdClass::class, true];

        yield 'class-string iface' => [ArrayIterator::class, Countable::class, true];

        yield 'wrong class' => [new stdClass(), ArrayIterator::class, false];

        yield 'missing class str' => ['NotAClass_xyz123', stdClass::class, false];

        yield 'int' => [42, stdClass::class, false];

        yield 'null' => [null, stdClass::class, false];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('isSubclassOfProvider')]
    public function testIsSubclass(mixed $value, string $class, bool $expected): void
    {
        $this->assertSame($expected, Type::isSubclass($value, $class));
    }

    /**
     * @return iterable<string, array{mixed, class-string, bool}>
     */
    public static function isSubclassOfProvider(): iterable
    {
        yield 'child instance' => [new ChildUsesGreet(), UsesGreet::class, true];

        yield 'child class-string' => [ChildUsesGreet::class, UsesGreet::class, true];

        yield 'iface instance' => [new ArrayIterator([]), Countable::class, true];

        yield 'iface class-string' => [ArrayIterator::class, Countable::class, true];

        // A class is not its own subclass — this is what sets it apart from isA().
        yield 'same instance' => [new UsesGreet(), UsesGreet::class, false];

        yield 'same class-string' => [UsesGreet::class, UsesGreet::class, false];

        yield 'unrelated' => [new stdClass(), ArrayIterator::class, false];

        yield 'missing class str' => ['NotAClass_xyz123', UsesGreet::class, false];

        yield 'int' => [42, UsesGreet::class, false];

        yield 'null' => [null, UsesGreet::class, false];
    }
}

trait GreetTrait {}
trait NestedTrait {}
trait ComposedTrait
{
    use NestedTrait;
}

class UsesGreet
{
    use GreetTrait;
}

class ChildUsesGreet extends UsesGreet {}

class UsesComposed
{
    use ComposedTrait;
}

enum Suit
{
    case Hearts;
    case Spades;
}

enum Status: string
{
    case Active = 'active';
}
