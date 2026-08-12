<?php
/**
 * Shared head component
 * @var string $pageTitle The title of the current page
 */
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Adom Fie CCR Community — church member management portal for House of Grace.">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?> | Adom Fie CCR Community</title>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
