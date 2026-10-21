<?php

namespace Tests\Unit\Rules;

use App\Rules\Isbn;
use PHPUnit\Framework\TestCase;

class IsbnTest extends TestCase
{
    private Isbn $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Isbn;
    }

    public function test_accepts_valid_isbn_10_and_isbn_13(): void
    {
        foreach ([
            '0306406152',
            '0-306-40615-2',
            '0 306 40615 2',
            '0-8044-2957-X',
            '080442957X',
            '0 8044 2957 x',
            '9780306406157',
            '978-0-306-40615-7',
            '978 0 306 40615 7',
            '979-10-90636-07-1',
        ] as $isbn) {
            $this->assertTrue($this->rule->checksumIsValid($isbn), $isbn);
            $this->assertNull($this->failureFor($isbn), $isbn);
        }
    }

    public function test_rejects_invalid_isbn_10_and_isbn_13(): void
    {
        foreach ([
            '0306406153',
            '0-306-40615-3',
            '0-8044-2957-0',
            '9780306406158',
            '978-0-306-40615-8',
            '979-10-90636-07-2',
            '1234567890128',
            '030640615',
            '978030640615',
            'ISBN 0-306-40615-2',
            '0-306-40615-Y',
            '',
        ] as $isbn) {
            $this->assertFalse($this->rule->checksumIsValid($isbn), $isbn);
            $this->assertSame(
                'The isbn must be a valid ISBN-10 or ISBN-13 number.',
                $this->failureFor($isbn),
                $isbn
            );
        }
    }

    private function failureFor(string $value): ?string
    {
        $message = null;

        $this->rule->validate('isbn', $value, function (string $failure) use (&$message): void {
            $message = str_replace(':attribute', 'isbn', $failure);
        });

        return $message;
    }
}
