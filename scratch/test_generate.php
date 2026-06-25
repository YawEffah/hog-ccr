<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'C:\xampp\htdocs\hog-ccr\vendor\autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
try {
    $s = new Spreadsheet();
    $w = new Xlsx($s);
    $w->save('C:\xampp\htdocs\hog-ccr\scratch\test3.xlsx');
    echo "OK";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
