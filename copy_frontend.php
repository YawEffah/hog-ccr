<?php
$c = file_get_contents('ministries.php');
$c = str_replace('ministry', 'family', $c);
$c = str_replace('Ministry', 'Family', $c);
$c = str_replace('ministries', 'families', $c);
$c = str_replace('Ministries', 'Families', $c);
$c = str_replace('ministry_modals.php', 'family_modals.php', $c);
$c = str_replace('manageMinistryModal', 'manageFamilyModal', $c);
// More robust replacements if needed
file_put_contents('families.php', $c);
echo "families.php created\n";

$c = file_get_contents('includes/modals/ministry_modals.php');
$c = str_replace('ministry', 'family', $c);
$c = str_replace('Ministry', 'Family', $c);
$c = str_replace('ministries', 'families', $c);
$c = str_replace('Ministries', 'Families', $c);
file_put_contents('includes/modals/family_modals.php', $c);
echo "family_modals.php created\n";
