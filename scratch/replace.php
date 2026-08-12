<?php
$files = [
    'handlers/user_handler.php',
    'users.php',
    'ministries.php',
    'members.php',
    'login.php',
    'includes/sidebar.php',
    'finance.php',
    'families.php',
    'events.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = str_replace('index.php', 'dashboard.php', $content);
        file_put_contents($path, $content);
        echo "Replaced in $file\n";
    }
}
echo "Done.\n";
