<?php
$c = file_get_contents('handlers/ministry_handler.php');
$c = str_replace('ministry', 'family', $c);
$c = str_replace('Ministry', 'Family', $c);
$c = str_replace('ministries', 'families', $c);
$c = str_replace('Ministries', 'Families', $c);
file_put_contents('handlers/family_handler.php', $c);
echo "family_handler.php created\n";

$c = file_get_contents('includes/helpers.php');
if(strpos($c, 'broadcastFamilyMessage') === false) {
    // Find the function and everything until its end.
    // To do this simply, let's just use regex.
    if (preg_match('/function broadcastMinistryMessage.*?\n}\n/s', $c, $matches)) {
        $fn = $matches[0];
        $fn = str_replace('Ministry', 'Family', $fn);
        $fn = str_replace('ministry', 'family', $fn);
        $fn = str_replace('ministries', 'families', $fn);
        file_put_contents('includes/helpers.php', "\n" . $fn . "\n", FILE_APPEND);
        echo "broadcastFamilyMessage added to helpers.php\n";
    }
}
