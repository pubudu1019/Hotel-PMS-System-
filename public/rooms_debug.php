<?php
// check_images.php - Image Path Check
include '../includes/db.php';

echo "<h2>🔍 Image Path Check</h2>";

// Check if folder exists
$folder = '../uploads/rooms/';
if (is_dir($folder)) {
    echo "<p style='color:green;'>✅ Folder exists: $folder</p>";
} else {
    echo "<p style='color:red;'>❌ Folder NOT found: $folder</p>";
}

// List all files in folder
echo "<h3>Files in folder:</h3>";
$files = scandir($folder);
echo "<ul>";
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "<li>$file - " . filesize($folder . $file) . " bytes</li>";
    }
}
echo "</ul>";

// Check each image
for($i = 1; $i <= 8; $i++) {
    $webp = $folder . $i . '.webp';
    $jpg = $folder . $i . '.jpg';
    
    echo "<h4>Image $i:</h4>";
    if(file_exists($webp)) {
        echo "<p style='color:green;'>✅ WebP exists: $webp</p>";
        echo "<img src='$webp' width='200' style='border:1px solid #ddd;'>";
    } elseif(file_exists($jpg)) {
        echo "<p style='color:green;'>✅ JPG exists: $jpg</p>";
        echo "<img src='$jpg' width='200' style='border:1px solid #ddd;'>";
    } else {
        echo "<p style='color:red;'>❌ Image $i NOT found!</p>";
    }
}
?>