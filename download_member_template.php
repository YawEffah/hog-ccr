<?php
/**
 * Member Bulk Import Template Generator
 * Generates and downloads a pre-formatted .xlsx template file.
 */
require_once 'includes/auth.php';
requireAuth();

// Check required PHP extensions
if (!extension_loaded('zip')) {
    die('Error: The PHP "zip" extension is required but not enabled. Please enable it in php.ini and restart Apache.');
}

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();

// ══════════════════════════════════════════════════════
// SHEET 1 — DATA ENTRY
// ══════════════════════════════════════════════════════
$dataSheet = $spreadsheet->getActiveSheet();
$dataSheet->setTitle('Members Import');

// Column definitions: [header, width, note]
$columns = [
    'A' => ['First Name *',        20, 'Required. Member\'s first name.'],
    'B' => ['Last Name *',         20, 'Required. Member\'s last name.'],
    'C' => ['Gender *',            12, 'Required. Enter: Male | Female'],
    'D' => ['Phone *',             18, 'Required. Primary phone number.'],
    'E' => ['Secondary Phone',     18, 'Optional. Alternate phone number.'],
    'F' => ['Email',               28, 'Optional. Email address.'],
    'G' => ['Date of Birth',       18, 'Optional. Format: YYYY-MM-DD (e.g. 1990-04-15)'],
    'H' => ['Joined Date',         18, 'Optional. Format: YYYY-MM-DD. Defaults to today.'],
    'I' => ['Status',              22, 'Optional. Enter: Active | Inactive | Affiliate Community Member. Defaults to Active.'],
    'J' => ['Address',             30, 'Optional. Residential address.'],
    'K' => ['Home Town',           20, 'Optional. Town of origin.'],
    'L' => ['Occupation',          22, 'Optional. Member\'s occupation.'],
    'M' => ['Marital Status',      18, 'Optional. Enter: Single | Married | Widowed | Divorced'],
    'N' => ['Children Count',      16, 'Optional. Number of children. Defaults to 0.'],
    'O' => ['Baptised',            12, 'Optional. Enter: Yes | No. Defaults to No.'],
    'P' => ['Communicant',         14, 'Optional. Enter: Yes | No. Defaults to No.'],
    'Q' => ['Group Memberships',   25, 'Optional. Free text (e.g. Bible Study, Youth Group).'],
    'R' => ['Next of Kin Name',    25, 'Optional.'],
    'S' => ['Next of Kin Relation',22, 'Optional. e.g. Spouse, Parent, Sibling.'],
    'T' => ['Next of Kin Address', 28, 'Optional.'],
    'U' => ['Next of Kin Phone',   20, 'Optional.'],
    'V' => ['Ministries',          30, 'Optional. Comma-separated ministry names (see Instructions sheet).'],
    'W' => ['Sacraments Needed',   28, 'Optional. Comma-separated. See Instructions sheet for valid values.'],
    'X' => ['Programmes Attended', 28, 'Optional. Comma-separated. See Instructions sheet for valid values.'],
];

// ── Header row styles ──────────────────────────────────
$headerStyle = [
    'font' => [
        'bold'  => true,
        'color' => ['argb' => 'FFFFFFFF'],
        'size'  => 11,
    ],
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF1E3A8A'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
        'wrapText'   => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => 'FFBFDBFE'],
        ],
    ],
];

// Required column header style (gold tint)
$reqStyle = $headerStyle;
$reqStyle['fill']['startColor']['argb'] = 'FF92400E'; // amber-800

// ── Write headers ──────────────────────────────────────
$dataSheet->getRowDimension(1)->setRowHeight(36);
foreach ($columns as $col => [$header, $width, $note]) {
    $cell = $col . '1';
    $dataSheet->setCellValue($cell, $header);
    $dataSheet->getColumnDimension($col)->setWidth($width);

    $isRequired = str_contains($header, '*');
    $dataSheet->getStyle($cell)->applyFromArray($isRequired ? $reqStyle : $headerStyle);

    // Add comment/note
    $comment = $dataSheet->getComment($cell);
    $comment->getText()->createTextRun($note);
    $comment->setWidth('200pt');
    $comment->setHeight('60pt');
}

// ── Freeze pane & auto-filter ─────────────────────────
$dataSheet->freezePane('A2');
$dataSheet->setAutoFilter('A1:' . array_key_last($columns) . '1');

// ── Data rows style ────────────────────────────────────
$dataRowStyle = [
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_HAIR,
            'color'       => ['argb' => 'FFD1D5DB'],
        ],
    ],
];

// Pre-format 1000 rows
$dataSheet->getStyle('A2:X1001')->applyFromArray($dataRowStyle);
for ($r = 2; $r <= 1001; $r++) {
    $dataSheet->getRowDimension($r)->setRowHeight(20);
}

// ── Example row ────────────────────────────────────────
$example = [
    'A' => 'Kwame',
    'B' => 'Asante',
    'C' => 'Male',
    'D' => '0244123456',
    'E' => '0201987654',
    'F' => 'kwame.asante@email.com',
    'G' => '1988-03-22',
    'H' => '2024-01-07',
    'I' => 'Active',
    'J' => '12 Rose Street, Kumasi',
    'K' => 'Kumasi',
    'L' => 'Teacher',
    'M' => 'Married',
    'N' => '2',
    'O' => 'Yes',
    'P' => 'Yes',
    'Q' => 'Bible Study',
    'R' => 'Abena Asante',
    'S' => 'Spouse',
    'T' => '12 Rose Street, Kumasi',
    'U' => '0244987654',
    'V' => 'Music Ministry',
    'W' => '',
    'X' => 'Life in the Spirit Seminar',
];

$exampleStyle = [
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFFFF7ED'],
    ],
    'font'  => ['italic' => true, 'color' => ['argb' => 'FF78350F']],
];

foreach ($example as $col => $val) {
    $dataSheet->setCellValue($col . '2', $val);
}
$dataSheet->getStyle('A2:X2')->applyFromArray($exampleStyle);

// Add "EXAMPLE ROW" label
$dataSheet->getComment('A2')->getText()->createTextRun('⚠ This is an example row — please delete it before uploading.');


// ══════════════════════════════════════════════════════
// SHEET 2 — INSTRUCTIONS
// ══════════════════════════════════════════════════════
$instrSheet = $spreadsheet->createSheet();
$instrSheet->setTitle('Instructions');

$instrSheet->getColumnDimension('A')->setWidth(30);
$instrSheet->getColumnDimension('B')->setWidth(65);

$titleStyle = [
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E3A8A']],
];
$sectionStyle = [
    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF92400E']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7ED']],
];
$labelStyle = ['font' => ['bold' => true]];

$instrSheet->setCellValue('A1', 'HOG-CCR Bulk Member Import — Instructions');
$instrSheet->mergeCells('A1:B1');
$instrSheet->getStyle('A1')->applyFromArray($titleStyle);
$instrSheet->getRowDimension(1)->setRowHeight(28);

$instrSheet->setCellValue('A2', 'Go to the "Members Import" sheet to enter member data.');
$instrSheet->mergeCells('A2:B2');
$instrSheet->getStyle('A2')->getFont()->setItalic(true)->setColor(new Color('FF6B7280'));
$instrSheet->getRowDimension(2)->setRowHeight(20);

$rows = [
    [4,  'REQUIRED FIELDS',          '',                                     true,  false],
    [5,  'First Name',               'Member\'s first name. Cannot be empty.',false, true],
    [6,  'Last Name',                'Member\'s last name. Cannot be empty.', false, true],
    [7,  'Gender',                   'Must be exactly: Male  OR  Female',     false, true],
    [8,  'Phone',                    'Primary phone number. Cannot be empty.', false, true],

    [10, 'ACCEPTED VALUES',          '',                                     true,  false],

    [11, 'Gender',                   "Male\nFemale",                         false, true],
    [12, 'Status',                   "Active\nInactive\nAffiliate Community Member",    false, true],
    [13, 'Marital Status',           "Single\nMarried\nWidowed\nDivorced",   false, true],
    [14, 'Baptised / Communicant',   "Yes\nNo",                              false, true],

    [16, 'MINISTRIES (comma-separated)', '', true, false],
    [17, 'Valid Ministry Names',     "Music Ministry\nYouth Wing\nEvangelism\nIntercessory\nPrayer Group\nExecutives\n(Any other ministry name that exists in the system)",
                                                                              false, true],

    [19, 'SACRAMENTS NEEDED (comma-separated)', '', true, false],
    [20, 'Valid Values',             "First Communion\nConfirmation\nHoly Matrimony\nHoly Orders", false, true],

    [22, 'PROGRAMMES ATTENDED (comma-separated)', '', true, false],
    [23, 'Valid Values',             "Life in the Spirit Seminar\nGrowth in the Spirit Seminar\nCharisms Session\nCatholic Alpha", false, true],

    [25, 'DATE FORMAT',              '',                                      true,  false],
    [26, 'Date of Birth & Joined Date', "Use YYYY-MM-DD format.\nExample: 1990-04-15\nIf left blank, Joined Date defaults to today's date.", false, true],

    [28, 'IMPORT RULES',             '',                                      true,  false],
    [29, 'Duplicates',               'If a phone number already exists in the system, that row will be skipped and reported as an error.',  false, true],
    [30, 'Unknown Ministries',       'Ministry names not found in the system will be skipped with a warning. All other data for that member will still be saved.', false, true],
    [31, 'Partial Errors',           'If some rows have errors, valid rows are still imported. You will receive a downloadable error report for failed rows.', false, true],
    [32, 'Example Row',              'The orange row in the "Members Import" sheet is an EXAMPLE — delete it before uploading.', false, true],
    [33, 'Member Codes',             'CCR-XXX codes are auto-generated by the system. Do not add a Member ID column.', false, true],
];

foreach ($rows as [$row, $label, $value, $isSection, $isData]) {
    $instrSheet->getRowDimension($row)->setRowHeight($isData && str_contains($value, "\n") ? max(20, substr_count($value, "\n") * 16 + 10) : 20);
    $instrSheet->setCellValue('A' . $row, $label);
    if ($isSection) {
        $instrSheet->mergeCells('A' . $row . ':B' . $row);
        $instrSheet->getStyle('A' . $row)->applyFromArray($sectionStyle);
    } else {
        $instrSheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $instrSheet->setCellValue('B' . $row, $value);
        $instrSheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }
}

// ── Set active sheet back to data sheet ───────────────
$spreadsheet->setActiveSheetIndex(0);

// ── Stream download ────────────────────────────────────
$filename = 'HOG_CCR_Member_Import_Template.xlsx';

try {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
} catch (Throwable $e) {
    error_log('Template generation error: ' . $e->getMessage());
    // If headers already sent, just output text; otherwise redirect back
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    die('Error generating template: ' . htmlspecialchars($e->getMessage()));
}
exit;
