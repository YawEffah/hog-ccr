<?php
echo 'gd:' . (extension_loaded('gd') ? 'OK' : 'MISSING') . ' ';
echo 'zip:' . (extension_loaded('zip') ? 'OK' : 'MISSING') . ' ';
echo 'php:' . phpversion();
