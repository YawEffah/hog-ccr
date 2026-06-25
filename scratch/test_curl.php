<?php
$ch = curl_init('http://localhost/hog-ccr/download_member_template.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
echo "Response:\n";
echo $res;
