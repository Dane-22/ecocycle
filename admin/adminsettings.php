<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- BACKUP/RESTORE LOGIC FIRST (before any output) ---

// Database credentials
require_once '../config/database.php';

// Reuse variables from config/database.php if they exist, otherwise use defaults
$db_host = $host ?? 'localhost';
$db_user = $username ?? 'root';
$db_pass = $password ?? '';
$db_name = $dbname ?? 'ecocycledb';

// CHANGED: Updated path for XAMPP
$mysql_bin_path = 'C:\\xampp\\mysql\\bin\\';

$backup_message = '';
$restore_message = '';
$backup_success = null;
$restore_success = null;

// Check for session-based backup success message
if (isset($_SESSION['backup_success_msg'])) {
    $backup_message = $_SESSION['backup_success_msg'];
    $backup_success = true;
    unset($_SESSION['backup_success_msg']);
}

// Create PDO connection for fallback restore
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Handle backup and force download
if (isset($_POST['backup'])) {
    $mysqldump = $mysql_bin_path . 'mysqldump.exe';
    $backup_dir = __DIR__ . '/../backup/';
    
    // Ensure backup directory exists
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $backup_file = $backup_dir . 'ecocycle_backup_' . date('Ymd_His') . '.sql';
    
    // FIXED: Proper password handling and error redirection
    $pass_part = !empty($db_pass) ? "--password=\"{$db_pass}\"" : "";
    $command = "\"$mysqldump\" --user={$db_user} {$pass_part} --host={$db_host} {$db_name} > \"{$backup_file}\" 2>&1";
    
    exec($command, $output, $result);
    
    if ($result === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
        $_SESSION['backup_success_msg'] = "Backup created and downloaded successfully!";
        
        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . basename($backup_file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file));
        flush();
        readfile($backup_file);
        exit;
    } else {
        $error_msg = "Backup failed. Command: " . htmlspecialchars($command) . "<br>";
        $error_msg .= "Output: " . htmlspecialchars(implode("\n", $output)) . "<br>";
        $error_msg .= "Result code: " . $result;
        $backup_message = $error_msg;
        $backup_success = false;
    }
}

// Handle restore
if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $restore_file = $_FILES['restore_file']['tmp_name'];
    $original_name = $_FILES['restore_file']['name'];
    
    if (is_uploaded_file($restore_file)) {
        try {
            $mysql = $mysql_bin_path . 'mysql.exe';
            
            // Ensure the backup directory exists for a temporary processing file
            $temp_dir = __DIR__ . '/../backup/temp/';
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            
            $temp_restore_file = $temp_dir . 'restore_' . time() . '.sql';
            
            // Move the uploaded file to our temp location to ensure access
            if (!copy($restore_file, $temp_restore_file)) {
                throw new Exception("Could not process the uploaded file for restoration.");
            }

            // Method 1: Try Shell Restore (Standard way)
            $pass_part = !empty($db_pass) ? "--password=\"{$db_pass}\"" : "";
            $command = "cmd /c \"\"$mysql\" --user=$db_user $pass_part --host=$db_host $db_name < \"$temp_restore_file\"\" 2>&1";
            
            $output = [];
            $result = -1;
            exec($command, $output, $result);
            
            if ($result === 0) {
                $restore_message = "<b>Success!</b><br>The database has been restored successfully from <b>" . htmlspecialchars($original_name) . "</b>!";
                $restore_success = true;
            } else {
                // Method 2: Fallback to Direct PDO (More reliable if shell fails)
                error_log("Shell restore failed (Code $result), trying PDO fallback...");
                
                if (!$pdo) {
                    throw new Exception("PDO connection not available for fallback.");
                }
                
                $sql_content = file_get_contents($temp_restore_file);
                if ($sql_content === false) {
                    throw new Exception("Could not read SQL content for fallback.");
                }
                
                // Disable foreign key checks to avoid issues during drop/create
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Execute the SQL commands
                $pdo->exec($sql_content);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                $restore_message = "<b>Success!</b><br>The database has been restored successfully using fallback method from <b>" . htmlspecialchars($original_name) . "</b>!";
                $restore_success = true;
            }
            
            // Clean up the temp file
            if (file_exists($temp_restore_file)) {
                unlink($temp_restore_file);
            }
            
        } catch (Exception $e) {
            $restore_message = "<b>Restore failed!</b><br>Error: " . htmlspecialchars($e->getMessage());
            $restore_success = false;
            error_log("Database restore failed: " . $e->getMessage());
            
            // Clean up if file exists
            if (isset($temp_restore_file) && file_exists($temp_restore_file)) {
                unlink($temp_restore_file);
            }
            
            // Ensure foreign keys are re-enabled
            if ($pdo) {
                try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch (Exception $ex) {}
            }
        }
    } else {
        $restore_message = "No file uploaded or file upload failed.";
        $restore_success = false;
    }
}

// --- NOW INCLUDE HEADER AND OUTPUT HTML ---
include 'adminheader.php';
include 'adminsidebar.php';
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="main-content">
  <div class="container-fluid mt-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 d-flex justify-content-center align-items-center" style="height: 200px;">
        <form method="post" style="display:inline;" id="backupForm">
          <input type="hidden" name="backup" value="1">
          <button type="button" class="btn btn-primary mx-3 px-4 py-2" id="backupBtn">
            <i class="fas fa-download me-2"></i>Backup Database
          </button>
        </form>
        <form method="post" enctype="multipart/form-data" style="display:inline;" id="restoreForm">
          <input type="hidden" name="restore" value="1">
          <input type="file" name="restore_file" id="restore_file" style="display:none;" accept=".sql" required>
          <button type="button" class="btn btn-success mx-3 px-4 py-2" id="restoreBtn">
            <i class="fas fa-upload me-2"></i>Restore Database
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// SweetAlert for Backup Confirmation
document.getElementById('backupBtn').addEventListener('click', function(e) {
  Swal.fire({
    title: 'Are you sure?',
    text: "Do you want to back up the database now?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, back up!'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('backupForm').submit();
    }
  });
});

// SweetAlert for Restore Confirmation
document.getElementById('restoreBtn').addEventListener('click', function(e) {
  Swal.fire({
    title: 'Are you sure?',
    text: "This will overwrite all current data. Continue?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, restore it!'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('restore_file').click();
    }
  });
});

// When file selected, submit form
document.getElementById('restore_file').addEventListener('change', function() {
  if (this.files.length > 0) {
    document.getElementById('restoreForm').submit();
  }
});

// SweetAlert for PHP messages
<?php if ($backup_message): ?>
Swal.fire({
  icon: '<?= $backup_success ? "success" : "error" ?>',
  title: '<?= $backup_success ? "Backup Successful" : "Backup Failed" ?>',
  html: <?= json_encode($backup_message) ?>,
});
<?php endif; ?>

<?php if ($restore_message): ?>
Swal.fire({
  icon: '<?= $restore_success ? "success" : "error" ?>',
  title: '<?= $restore_success ? "Restore Successful" : "Restore Failed" ?>',
  html: <?= json_encode($restore_message) ?>,
});
<?php endif; ?>
</script>

<style>
</style>

</body>
</html>