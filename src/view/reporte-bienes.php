<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->SetCreator("yp")->setLastModifiedBy("yo")->setDescription("yo");
$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle("hoja 1");
$activeWorksheet->setCellValue('A1', 'hola mundo');
$activeWorksheet->setCellValue('A2','DNI');
//activeWorksheet->setCellValue('B2','71750680');
for ($i=1; $i <= 10;$i++){
    $activeWorksheet->setCellValue('A'.$i,$i);
}
for($i=1; $i <= 30;$i++){
$columna=\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
$activeWorksheet->setCellValue($columna.'1', $i);
}
$n1=1;
for ($n2=1;$n2<= 12; $n2++){
    $activeWorksheet->serCellValue('A'.$n2,$n1);
    $activeWorksheet->serCellValue('B'.$n2,'X');
    $activeWorksheet->serCellValue('C'.$n2,$n2);
    $activeWorksheet->serCellValue('D'.$n2,'=');
    $activeWorksheet->serCellValue('E'.$n2,$n1*$n2);

   
}

$writer = new Xlsx($spreadsheet);
$writer->save('hello world.xlsx');
?>


//utilizando bucles realiza tabla de la 1