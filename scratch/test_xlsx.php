<?php
require 'C:/xampp/htdocs/hog-ccr/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$s = new Spreadsheet();
$s->getActiveSheet()->setTitle('Test');
$s->getActiveSheet()->setCellValue('A1', 'Hello');
$w = new Xlsx($s);
ob_start();
$w->save('php://output');
$out = ob_get_clean();
echo strlen($out) > 0 ? 'XLSX_OK: ' . strlen($out) . ' bytes' : 'XLSX_EMPTY';
