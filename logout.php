@'
<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
'@ | Out-File -FilePath "logout.php" -Encoding UTF8