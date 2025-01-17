<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelperController extends Controller
{
    public static function convert($x)
    {
        $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($x < 12)
            return $abil[$x];
        elseif ($x < 20)
            return HelperController::convert($x - 10) . " belas ";
        elseif ($x < 100)
            return HelperController::convert($x / 10) . " puluh " . HelperController::convert($x % 10);
        elseif ($x < 200)
            return " seratus " . HelperController::convert($x - 100);
        elseif ($x < 1000)
            return HelperController::convert($x / 100) . " ratus " . HelperController::convert($x % 100);
        elseif ($x < 2000)
            return " seribu " . HelperController::convert($x - 1000);
        elseif ($x < 1000000)
            return HelperController::convert($x / 1000) . " ribu " . HelperController::convert($x % 1000);
        elseif ($x < 1000000000)
            return HelperController::convert($x / 1000000) . " juta " . HelperController::convert($x % 1000000);
    }

    public static function format_number($number)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 3, '.', '.') . ' M';  // Miliar
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 3, '.', '.') . ' J';  // Juta
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 3, '.', '.') . ' R';  // Ribu
        } else {
            return number_format($number, 0, ',', '.');  // Format biasa untuk angka kecil
        }
    }
}
