<?php

namespace Frd\MathUtils;

use Frd\MathUtils\Exception\MathException;
use JsonException;

class Math
{
    /**
     * @param float[] $values
     */
    public static function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        sort($values, SORT_NUMERIC);

        $middleIndex = (int) floor($count / 2);

        if ($count % 2) {
            return $values[$middleIndex];
        }

        return ($values[$middleIndex - 1] + $values[$middleIndex]) / 2;
    }

    /**
     * @param float[] $values
     */
    public static function average(array $values): float
    {
        return array_sum($values) / count($values);
    }

    /**
     * @param float[] $values
     */
    public static function sum(array $values): float
    {
        return array_sum($values);
    }

    /**
     * @param string $formula Formula to process
     * @param array $params Array of int or int[]
     * @param ?array &$steps Array of steps to be filled with each step of the calculation
     *
     * It needs to support + - * / and methods average(array), sum(array) and median(array)
     * Example "average([average([sum($0), sum($1)]),median($3)]) * 2".
     * Uses user input so eval MUST be protected against code injection.
     *
     * @SuppressWarnings(PHPMD.EvalExpression)
     */
    public static function process(string $formula, array $params, array &$steps = null): float
    {
        $steps[] = $formula;

        $formula = preg_replace('/\s+/', '', $formula);

        // replace params with their values
        foreach ($params as $index => $param) {
            $formula = str_replace('$' . $index, json_encode($param, JSON_THROW_ON_ERROR), $formula);
        }

        $steps[] = $formula;

        // recursively proccess inner functions working outwards replacing them with their results
        while (preg_match('/(?<func>[a-z]+)\((?<params>[^\(\)]+)\)/i', $formula, $matches)) {
            $func = $matches['func'];
            if (!method_exists(self::class, $func)) {
                throw new MathException('Invalid function: ' . $func);
            }

            $params = self::processParamsJson($matches['params'], $formula, $func);

            $result = self::$func($params);

            $formula = str_replace($matches[0], $result, $formula);

            $steps[] = $formula;
        }

        if (is_numeric($formula)) {
            return (float) $formula;
        }

        // error if formula contains anything other than numbers and /*-+() and whitespace
        if (preg_match('/[^0-9\.\+\-\*\/\(\)]/', $formula)) {
            throw new MathException('Invalid formula. Calculated formula is: ' . $formula);
        }

        try {
            $result = eval('return ' . $formula . ';');
        } catch (\ParseError $e) {
            throw new MathException('Error parsing formula. Calculated formula is: ' . $formula);
        }

        return $result;
    }

    /**
     * If params contains a basic math operation, process its inputs and replace it with the result
     */
    private static function processParamsJson(string $params, $formula, $func): array
    {
        // process left and right sides of the operation
        // replace the operation with the result
        // repeat until params contains no more operations
        while (preg_match('/(?<left>[^\+\-\*\/\[\],]+)(?<op>[\+\-\*\/])(?<right>[^\+\-\*\/\[\],]+)/', $params, $matches)) {
            $left = self::process($matches['left'], []);
            $right = self::process($matches['right'], []);

            $result = self::processOperation($left, $matches['op'], $right);

            $params = str_replace($matches[0], $result, $params);
        }

        try {
            return json_decode($params, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MathException(sprintf(
                'Invalid argument for %s(). Calculated formula is: %s. Argument for %s() must be an array. Given: (%s) %s',
                $func,
                $formula,
                $func,
                gettype($params),
                $params
            ));
        }
    }

    private static function processOperation($left, $operator, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            throw new MathException(sprintf(
                'Invalid operation. Left side is: %s Right side is: %s',
                $left,
                $right
            ));
        }

        return match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $left / $right,
            default => throw new MathException('Invalid operation: ' . $operator)
        };
    }
}
