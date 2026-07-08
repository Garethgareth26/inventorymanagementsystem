<?php
require 'vendor/autoload.php';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load('C:\inventory-management-system-akuna\docs\Perhitungan_Lengkap_Inventory_2025.xlsx');
$result = [];
foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
    $sheet = $spreadsheet->getSheet($index);
    $highestRow = min($sheet->getHighestRow(), 10);
    $highestColumn = $sheet->getHighestColumn();
    $data = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, true, true, true);
    $result[$sheetName] = $data;
}
echo json_encode($result, JSON_PRETTY_PRINT);
