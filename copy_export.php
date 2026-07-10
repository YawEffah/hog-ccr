<?php
$c = file_get_contents('export_ministry_report.php');
$c = str_replace('ministry', 'family', $c);
$c = str_replace('Ministry', 'Family', $c);
$c = str_replace('ministries', 'families', $c);
$c = str_replace('Ministries', 'Families', $c);
file_put_contents('export_family_report.php', $c);
echo "export_family_report.php created\n";
