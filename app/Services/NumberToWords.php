<?php

namespace App\Services;

class NumberToWords
{
    public static function convert(float $amount): string
    {
        $number = floor($amount);
        $decimal = round(($amount - $number) * 100);

        if ($number == 0) {
            return "Zero Pesos" . ($decimal > 0 ? " and {$decimal}/100" : "");
        }

        $words = self::numberToWords($number);
        
        $result = $words . " Pesos";
        
        if ($decimal > 0) {
            $result .= " and {$decimal}/100";
        }

        return $result;
    }

    private static function numberToWords(int $number): string
    {
        if ($number < 0) {
            return "Negative " . self::numberToWords(-$number);
        }

        if ($number == 0) {
            return "";
        }

        $words = "";

        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $words .= self::numberToWords($millions) . " Million ";
            $number = $number % 1000000;
        }

        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $words .= self::numberToWords($thousands) . " Thousand ";
            $number = $number % 1000;
        }

        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $words .= self::hundredsToWords($hundreds) . " Hundred ";
            $number = $number % 100;
        }

        if ($number > 0) {
            $words .= self::onesToWords($number);
        }

        return trim($words);
    }

    private static function hundredsToWords(int $number): string
    {
        return self::onesToWords($number);
    }

    private static function onesToWords(int $number): string
    {
        $ones = [
            1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five",
            6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten",
            11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen",
            15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen",
            19 => "Nineteen", 20 => "Twenty", 30 => "Thirty", 40 => "Forty",
            50 => "Fifty", 60 => "Sixty", 70 => "Seventy", 80 => "Eighty", 90 => "Ninety"
        ];

        if ($number <= 20) {
            return $ones[$number] ?? "";
        }

        $tens = floor($number / 10) * 10;
        $ones_digit = $number % 10;

        if ($ones_digit == 0) {
            return $ones[$tens];
        }

        return $ones[$tens] . " " . $ones[$ones_digit];
    }
}