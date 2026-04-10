<?php
/**
 * Shared head component
 * @var string $pageTitle The title of the current page
 */
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?> | CCR House of Grace</title>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <!-- Tailwind CSS (Unused but kept per original structure if needed by backend, though plan suggested removal, I will follow the plan's recommendation to remove it to reduce overhead) -->
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
