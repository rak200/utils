<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use ArrayIterator;
use BcMath\Number;
use Countable;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Rak200\Utils\Type;
use stdClass;
use Stringable;

final class TypeTest extends TestCase {
    public function testIsStr(): void {
        $this->assertTrue(Type::isStr(''));
        $this->assertTrue(Type::isStr('hello'));
        $this->assertFalse(Type::isStr(42));
        $this->assertFalse(Type::isStr(null));
        $this->assertFalse(Type::isStr(['a']));
    }

    public function testIsBool(): void {
        $this->assertTrue(Type::isBool(true));
        $this->assertTrue(Type::isBool(false));
        $this->assertFalse(Type::isBool(0));
        $this->assertFalse(Type::isBool(1));
        $this->assertFalse(Type::isBool('true'));
        $this->assertFalse(Type::isBool(null));
    }

    public function testIsInt(): void {
        $this->assertTrue(Type::isInt(0));
        $this->assertTrue(Type::isInt(-7));
        $this->assertTrue(Type::isInt(PHP_INT_MAX));
        $this->assertFalse(Type::isInt(1.5));
        $this->assertFalse(Type::isInt('42'));
        $this->assertFalse(Type::isInt(true));
    }

    public function testIsFloat(): void {
        $this->assertTrue(Type::isFloat(1.5));
        $this->assertTrue(Type::isFloat(0.0));
        $this->assertTrue(Type::isFloat(INF));
        $this->assertTrue(Type::isFloat(NAN));
        $this->assertFalse(Type::isFloat(1));
        $this->assertFalse(Type::isFloat('1.5'));
    }

    public function testIsArray(): void {
        $this->assertTrue(Type::isArray([]));
        $this->assertTrue(Type::isArray([1, 2, 3]));
        $this->assertTrue(Type::isArray(['a' => 1]));
        $this->assertFalse(Type::isArray(new ArrayIterator([1, 2])));
        $this->assertFalse(Type::isArray('a,b,c'));
    }

    public function testIsObject(): void {
        $this->assertTrue(Type::isObject(new stdClass()));
        $this->assertTrue(Type::isObject(static fn(): int => 1));
        $this->assertFalse(Type::isObject('stdClass'));
        $this->assertFalse(Type::isObject([]));
        $this->assertFalse(Type::isObject(null));
    }

    public function testIsCallable(): void {
        $this->assertTrue(Type::isCallable(static fn(): int => 1));
        $this->assertTrue(Type::isCallable('strlen'));
        $this->assertTrue(Type::isCallable([DateTimeImmutable::class, 'createFromFormat']));
        $this->assertFalse(Type::isCallable('this_function_does_not_exist_xyz'));
        $this->assertFalse(Type::isCallable(42));
    }

    public function testIsIterable(): void {
        $this->assertTrue(Type::isIterable([]));
        $this->assertTrue(Type::isIterable([1, 2]));
        $this->assertTrue(Type::isIterable(new ArrayIterator([1])));
        $this->assertFalse(Type::isIterable(new stdClass()));
        $this->assertFalse(Type::isIterable('abc'));
    }

    public function testIsNull(): void {
        $this->assertTrue(Type::isNull(null));
        $this->assertFalse(Type::isNull(false));
        $this->assertFalse(Type::isNull(0));
        $this->assertFalse(Type::isNull(''));
        $this->assertFalse(Type::isNull([]));
    }

    public function testIsScalar(): void {
        $this->assertTrue(Type::isScalar(1));
        $this->assertTrue(Type::isScalar(1.5));
        $this->assertTrue(Type::isScalar('hello'));
        $this->assertTrue(Type::isScalar(true));
        $this->assertFalse(Type::isScalar(null));
        $this->assertFalse(Type::isScalar([]));
        $this->assertFalse(Type::isScalar(new stdClass()));
    }

    public function testIsNumeric(): void {
        $this->assertTrue(Type::isNumeric(42));
        $this->assertTrue(Type::isNumeric(1.5));
        $this->assertTrue(Type::isNumeric('1.5'));
        $this->assertTrue(Type::isNumeric('-1e3'));
        $this->assertTrue(Type::isNumeric(new Number('123.45')));
        $this->assertFalse(Type::isNumeric('hello'));
        $this->assertFalse(Type::isNumeric(null));
        $this->assertFalse(Type::isNumeric(true));
    }

    public function testIsResource(): void {
        $fp = fopen('php://memory', 'rb');
        $this->assertNotFalse($fp);
        $this->assertTrue(Type::isResource($fp));
        fclose($fp);
        $this->assertFalse(Type::isResource($fp));
        $this->assertFalse(Type::isResource('php://memory'));
    }

    public function testIsNumericStr(): void {
        $this->assertTrue(Type::isNumericStr('42'));
        $this->assertTrue(Type::isNumericStr('-1.5'));
        $this->assertTrue(Type::isNumericStr('1e3'));
        $this->assertFalse(Type::isNumericStr(42));
        $this->assertFalse(Type::isNumericStr('hello'));
        $this->assertFalse(Type::isNumericStr(''));
    }

    public function testIsIntLike(): void {
        $this->assertTrue(Type::isIntLike(42));
        $this->assertTrue(Type::isIntLike(-7));
        $this->assertTrue(Type::isIntLike('42'));
        $this->assertTrue(Type::isIntLike('-7'));
        $this->assertTrue(Type::isIntLike('+0'));
        $this->assertFalse(Type::isIntLike('1.0'));
        $this->assertFalse(Type::isIntLike('1e3'));
        $this->assertFalse(Type::isIntLike(' 42 '));
        $this->assertFalse(Type::isIntLike(''));
        $this->assertFalse(Type::isIntLike(1.5));
        $this->assertFalse(Type::isIntLike(true));
    }

    public function testIsInstanceOf(): void {
        $this->assertTrue(Type::isInstanceOf(new stdClass(), stdClass::class));
        $this->assertTrue(Type::isInstanceOf(new ArrayIterator([]), Countable::class));
        $this->assertFalse(Type::isInstanceOf(stdClass::class, stdClass::class));
        $this->assertFalse(Type::isInstanceOf(new stdClass(), ArrayIterator::class));
        $this->assertFalse(Type::isInstanceOf(null, stdClass::class));
    }

    public function testIsClassName(): void {
        $this->assertTrue(Type::isClassName(stdClass::class));
        $this->assertTrue(Type::isClassName(ArrayIterator::class));
        $this->assertFalse(Type::isClassName(Countable::class));
        $this->assertFalse(Type::isClassName('NotAClass_xyz123'));
        $this->assertFalse(Type::isClassName(new stdClass()));
    }

    public function testIsInterfaceName(): void {
        $this->assertTrue(Type::isInterfaceName(Countable::class));
        $this->assertTrue(Type::isInterfaceName(Stringable::class));
        $this->assertFalse(Type::isInterfaceName(stdClass::class));
        $this->assertFalse(Type::isInterfaceName('NotAnInterface_xyz123'));
    }

    public function testIsA(): void {
        $this->assertTrue(Type::isA(new stdClass(), stdClass::class));
        $this->assertTrue(Type::isA(new ArrayIterator([]), Countable::class));
        $this->assertTrue(Type::isA(stdClass::class, stdClass::class));
        $this->assertTrue(Type::isA(ArrayIterator::class, Countable::class));
        $this->assertFalse(Type::isA(new stdClass(), ArrayIterator::class));
        $this->assertFalse(Type::isA('NotAClass_xyz123', stdClass::class));
        $this->assertFalse(Type::isA(42, stdClass::class));
        $this->assertFalse(Type::isA(null, stdClass::class));
    }
}
