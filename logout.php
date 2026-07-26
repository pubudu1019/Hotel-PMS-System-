<?php
session_start();
session_unset(); // සියලු session variables ඉවත් කරයි
session_destroy(); // session එක සම්පූර්ණයෙන්ම විනාශ කරයි
header("Location: index.php"); // ලොගින් පේජ් එකට යවයි
exit();
?>