<?php

namespace Frd\MathUtils\Tests;

use Frd\MathUtils\Exception\MathException;
use Frd\MathUtils\Math;
use Generator;
use PHPUnit\Framework\TestCase;

class MathTest extends TestCase
{
    public function testMedianIsCorrectlyCalculated()
    {
        $this->assertEquals(3, Math::median([1, 2, 3, 4, 5]));
        $this->assertEquals(3, Math::median([3, 4, 1, 2, 5]));
        $this->assertEquals(3.5, Math::median([1, 2, 3, 4, 5, 6]));
        $this->assertEquals(5.5, Math::median([3, 9, 7, 4]));
    }

    public function testAverageIsCorrectlyCalculated()
    {
        $this->assertEquals(3, Math::average([1, 2, 3, 4, 5]));
        $this->assertEquals(3.5, Math::average([1, 2, 3, 4, 5, 6]));
        $this->assertEquals(5.75, Math::average([3, 9, 7, 4]));
    }

    public function testAverageDivisionByZero()
    {
        $this->expectException(\DivisionByZeroError::class);
        Math::average([]);
    }

    public function testSumIsCorrectlyCalculated()
    {
        $this->assertEquals(15, Math::sum([1, 2, 3, 4, 5]));
        $this->assertEquals(21, Math::sum([1, 2, 3, 4, 5, 6]));
        $this->assertEquals(23, Math::sum([3, 9, 7, 4]));
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($formula, $params, $expectedResult)
    {
        $this->assertEquals($expectedResult, Math::process($formula, $params));
    }

    public static function processProvider(): Generator
    {
        yield 'Simple' => [
            '1 + 1',
            [],
            2
        ];

        yield 'Simple with params' => [
            '$0 * $1',
            [2, 2],
            4
        ];

        yield 'Simple with params 2' => [
            '(2+$0)/2',
            [3],
            2.5
        ];

        yield 'Sum' => [
            'sum([1, 2, 3, 4])',
            [],
            10
        ];

        yield '1 + sum' => [
            '1 + sum([1, 2, 3, 4])',
            [],
            11
        ];

        yield 'Sum with params' => [
            'sum($0)',
            [[1, 2, 3, 4]],
            10
        ];

        yield 'Average' => [
            'average([0, 10])',
            [],
            5
        ];

        yield 'Average with params' => [
            'sum([1,2,3,average([0,8])])',
            [],
            10
        ];

        yield 'Complex' => [
            'average([average([sum($0), sum($1)]),median($3)]) * 2',
            [
                [1, 2, 3, 4],
                [5, 6, 7, 8],
                null,
                [13, 14, 15, 16]
            ],
            32.5
        ];

        yield 'Multiline formula' => [
            'average([
                2,
                4
            ])',
            [],
            3
        ];

        yield 'Multiline formula 2' => [
            'average([
                2,
                4,
                average([
                    1,
                    2,
                    3,
                    4
                ])
            ])',
            [],
            2.8333333333333
        ];

        yield 'Multiline formula 3' => [
            'average([
                2,
                4,
                average([
                    1,
                    2,
                    3,
                    4
                ]),
                average([
                    sum($0),
                    sum($1)
                ])
            ])',
            [
                [1, 2, 3, 4],
                [5, 6, 7, 8]
            ],
            6.625
        ];

        yield 'Formula with spaces' => [
            ' ( 51.5 + 93.5 )/2 ',
            [],
            72.5
        ];

        yield 'Formula with inner calculation' => [
            '( 51.5 + median([ 30, 45, 42, 536, 48/3, 158, 126 ]) )/2',
            [],
            48.25
        ];

        // (avg([10, 26]) + median([10, 25, 12, 78, 2.5]))/2 = (18 + 12)/2 = 15
        yield 'Nested formula with inner simple math' => [
            '(
                average([
                  sum($0),
                  sum($1)
                ])
                +
                median([
                  sum($2),
                  sum($3) - 1,
                  sum($4) + 2,
                  sum($5) * 3,
                  sum($6) / 4
                ])
            )/2',
            [
                [1, 2, 3, 4], // 10
                [5, 6, 7, 8], // 26
                [1, 2, 3, 4],
                [5, 6, 7, 8],
                [1, 2, 3, 4],
                [5, 6, 7, 8],
                [1, 2, 3, 4]
            ],
            15
        ];

        // avg([3, 2*7/5.5, 2]) + 1 + sum([sum([7, 8]), 4-5]) ~= 2.51 + 1 + 14 ~= 17.51
        yield 'Multiple nesting levels with inner math' => [
            'average([
                sum($0),
                2 * sum($1) / median($2),
                1+1
            ])
            + 1
            + sum([
                sum($3),
                4 - 5
            ])',
            [
                [1, 2],
                [3, 4],
                [5, 6],
                [7, 8]
            ],
            17.5151515151515
        ];
    }

    /**
     * @dataProvider processErrorProvider
     */
    public function testProcessError($formula, $params, $expectedMessage)
    {
        $this->expectException(MathException::class);
        $this->expectExceptionMessage($expectedMessage);
        Math::process($formula, $params);
    }

    public static function processErrorProvider(): Generator
    {
        yield 'Invalid formula' => [
            '1 +',
            [],
            'Error parsing formula. Calculated formula is: 1+'
        ];

        yield 'Invalid formula text' => [
            '1 + a',
            [],
            'Invalid formula. Calculated formula is: 1+a'
        ];

        yield 'Code injection' => [
            '1 + 1; echo "code injection";',
            [],
            'Invalid formula. Calculated formula is: 1+1;echo"codeinjection";'
        ];

        yield 'Invalid params' => [
            '1 + $2',
            [[1]],
            'Invalid formula. Calculated formula is: 1+$2'
        ];

        yield 'Invalid params for method' => [
            '1 + sum($2)',
            [[1]],
            'Invalid argument for sum(). Calculated formula is: 1+sum($2). Argument for sum() must be an array. Given: (string) $2'
        ];

        yield 'Construct' => [
            '1 + __construct($0)',
            [[1]],
            'Invalid function: construct'
        ];

        yield 'Invalid function number' => [
            '1 + sum2($0)',
            [[1]],
            'Invalid formula. Calculated formula is: 1+sum2([1])'
        ];

        yield 'Invalid function' => [
            '1 + asdf($0)',
            [[1]],
            'Invalid function: asdf'
        ];

        yield 'Invalid formula multiline' => [
            '1 + sum([
                1,
                2
            ]))',
            [],
            'Error parsing formula. Calculated formula is: 1+3)'
        ];
    }

    public function testStepsPopulated()
    {
        $formula = "average([average([sum($0), sum($1)]),median($3)]) * 2";
        $params = [[1, 2, 3], [4, 5, 6], [7, 8, 9], [10, 11, 12]];
        $expectedSteps = [
            "average([average([sum($0), sum($1)]),median($3)]) * 2",
            "average([average([sum([1,2,3]),sum([4,5,6])]),median([10,11,12])])*2",
            "average([average([6,sum([4,5,6])]),median([10,11,12])])*2",
            "average([average([6,15]),median([10,11,12])])*2",
            "average([10.5,median([10,11,12])])*2",
            "average([10.5,11])*2",
            "10.75*2"
        ];

        $steps = [];
        $result = Math::process($formula, $params, $steps);

        $this->assertEquals(21.5, $result);
        $this->assertEquals($expectedSteps, $steps);
    }
}
