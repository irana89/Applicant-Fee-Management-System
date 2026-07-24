<?php
// Database configuration
$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'PSC_TMK';

// Backup directory (local path and web path)
$backupDir = 'F:\\xampp\\htdocs\\psctmk\\SQLBackup\\';
$webBackupDir = '/psctmk/SQLBackup/';

$date = date('Y-m-d');
$backupFile = $backupDir . "{$database}_backup_{$date}.sql";

// Command to execute mysqldump
$mysqldump = '"F:\\xampp\\mysql\\bin\\mysqldump.exe"';

$command = $mysqldump .
    " --user={$username}" .
    " --password={$password}" .
    " --host={$host}" .
    " {$database}" .
    " --result-file=\"{$backupFile}\"";

// Run the backup
system($command, $return_var);

if ($return_var === 0) {
    echo "✅ Backup successful: $backupFile<br>";

    // ZIP the SQL file
    $zipFile = $backupFile . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($backupFile, basename($backupFile));
        $zip->close();
        unlink($backupFile); // Delete original .sql file
        echo "🔒 Compressed backup: $zipFile<br><br>";
    } else {
        echo "⚠️ Failed to create ZIP file.<br><br>";
    }
} else {
    echo "❌ Backup failed!<br><br>";
}

// Delete backups older than 30 days
$files = glob($backupDir . '*.zip');
$now   = time();

foreach ($files as $file) {
    if (is_file($file) && $now - filemtime($file) >= 30 * 24 * 60 * 60) {
        unlink($file);
        echo "🗑️ Deleted old backup: " . basename($file) . "<br>";
    }
}

// 🔽 List available backups
echo "<h3>📁 Available Backups:</h3>";
$backups = glob($backupDir . '*.zip');

if (count($backups) > 0) {
    foreach ($backups as $file) {
        $filename = basename($file);
        $url = $webBackupDir . $filename;
        echo "<a href='$url' download>$filename</a><br>";
    }
} else {
    echo "No backups found.<br>";
}

// 📂 Open folder link (works only on local systems with browser permissions)
echo "<br><h3>📂 Open Backup Folder:</h3>";
echo "<a href='file:///F:/xampp/htdocs/psctmk/SQLBackup/' target='_blank'>Open Folder in Explorer</a><br>";
?>
