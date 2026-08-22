<?php

use GustavPHP\Gustav\Http\Exception\ValidationException;
use GustavPHP\Gustav\Validation\Common\{Boolean, Decimal, Email, IP, Integer, Nullable, Text, URL};

describe('built-in validation rules', function () {
    it('accepts integer zero and inclusive boundaries', function (mixed $value, Integer $rule) {
        expect($rule->validate($value))->toBeTrue();
    })->with([
        'integer zero' => [0, new Integer()],
        'string zero' => ['0', new Integer()],
        'minimum' => [-2, new Integer(min: -2, max: 2)],
        'maximum' => [2, new Integer(min: -2, max: 2)],
    ]);

    it('rejects invalid and out-of-range integers', function (mixed $value, string $code) {
        try {
            (new Integer(min: -2, max: 2))->validate($value);
        } catch (ValidationException $exception) {
            expect($exception->getViolations()[0]->code)->toBe($code);

            return;
        }

        test()->fail('Expected integer validation to fail');
    })->with([
        'invalid' => ['1.2', 'invalid_integer'],
        'below minimum' => [-3, 'min_value'],
        'above maximum' => [3, 'max_value'],
    ]);

    it('accepts decimal zero, negative values, and inclusive boundaries', function (
        mixed $value,
        ?float $min,
        ?float $max,
    ) {
        $rule = $min === null || $max === null
            ? new Decimal()
            : new Decimal($min, $max);

        expect($rule->validate($value))->toBeTrue();
    })->with([
        'float zero' => [0.0, null, null],
        'string zero' => ['0.0', null, null],
        'negative default' => [-1.5, null, null],
        'minimum' => [-2.0, -2.0, 2.0],
        'maximum' => [2.0, -2.0, 2.0],
    ]);

    it('rejects invalid and out-of-range decimals', function (mixed $value, string $code) {
        try {
            (new Decimal(min: -2, max: 2))->validate($value);
        } catch (ValidationException $exception) {
            expect($exception->getViolations()[0]->code)->toBe($code);

            return;
        }

        test()->fail('Expected decimal validation to fail');
    })->with([
        'invalid' => ['value', 'invalid_decimal'],
        'below minimum' => [-2.1, 'min_value'],
        'above maximum' => [2.1, 'max_value'],
    ]);

    it('validates booleans', function () {
        $rule = new Boolean();

        expect($rule->validate(true))->toBeTrue()
            ->and($rule->validate(false))->toBeTrue()
            ->and($rule->validate('true'))->toBeTrue()
            ->and($rule->validate('false'))->toBeTrue();

        $rule->validate('sometimes');
    })->throws(ValidationException::class);

    it('validates text and includes both length boundaries', function () {
        $rule = new Text(minLength: 2, maxLength: 4);

        expect($rule->validate('ab'))->toBeTrue()
            ->and($rule->validate('abcd'))->toBeTrue();

        $rule->validate('a');
    })->throws(ValidationException::class);

    it('validates email addresses', function () {
        $rule = new Email();

        expect($rule->validate('ada@example.com'))->toBeTrue();
        $rule->validate('invalid');
    })->throws(ValidationException::class);

    it('validates IPv4 and IPv6 addresses', function () {
        expect((new IP(onlyV4: true))->validate('127.0.0.1'))->toBeTrue()
            ->and((new IP(onlyV6: true))->validate('2001:db8::1'))->toBeTrue();

        (new IP(onlyV4: true))->validate('2001:db8::1');
    })->throws(ValidationException::class);

    it('validates URLs', function () {
        $rule = new URL();

        expect($rule->validate('https://gustav-php.github.io'))->toBeTrue();
        $rule->validate('not a URL');
    })->throws(ValidationException::class);

    it('allows null only through the nullable wrapper', function () {
        $rule = new Nullable(new Email());

        expect($rule->validate(null))->toBeTrue()
            ->and($rule->validate('ada@example.com'))->toBeTrue();

        $rule->validate('invalid');
    })->throws(ValidationException::class);

    it('rejects invalid rule ranges', function () {
        new Decimal(min: 2, max: -2);
    })->throws(InvalidArgumentException::class);
});
