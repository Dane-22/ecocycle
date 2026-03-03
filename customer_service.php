<?php
// Include session check for buyers
require_once 'config/session_check.php';

// Check if user is a buyer
if (!isBuyer()) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

include 'homeheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/mobile.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        /* Scoped styles for fullscreen behaviour on this page only */
        .ec-fullscreen-root { min-height: 100vh; display: flex; flex-direction: column; }
        .ec-fullscreen-root .page-content { flex: 1 0 auto; }
        /* Make footer stick to bottom when there's extra space */
        .ec-fullscreen-footer { margin-top: auto; }
        /* Small adjustments for the fullscreen button to sit nicely */
        #fullscreenBtn { white-space: nowrap; }
        /* Page content styles (no card) */
        .page-content { padding: 0; }
        .page-header { margin-bottom: 1rem; }
        .page-body { padding: 1rem; }
        /* Constrain and center the contact form on larger screens */
        .contact-form-wrapper { max-width: 1800px; margin: 0 auto; }
        @media (max-width: 767.98px) {
            .contact-form-wrapper { padding: 0 12px; }
        }
    </style>
</head>
<body>


        <div class="page-body">
            <section class="mb-4">
                <h4>Contact Us</h4>
                <p>If you have any questions, concerns, or need assistance, please reach out to our customer service team using the form below or via email at <a href="mailto:ecsd@dmmmsu.edu.ph">ecsd@dmmmsu.edu.ph</a>.</p>
                <div class="contact-form-wrapper">
                <form action="#" method="post" class="row g-3" style="padding: 24px;">
                    <div class="col-md-6">
                        <?php
                        // Include database connection
                        require_once __DIR__ . '/config/database.php';

                        // Create messages table if it doesn't exist
                        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                            message_id INT AUTO_INCREMENT PRIMARY KEY,
                            buyer_id INT DEFAULT NULL,
                            admin_id INT DEFAULT NULL,
                            sender_type ENUM('buyer','admin') NOT NULL,
                            message_text TEXT NOT NULL,
                            feedback_text TEXT DEFAULT NULL,
                            status ENUM('unread','read','replied') DEFAULT 'unread',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )");

                        // Handle form submission
                        $successMsg = '';
                        $errorMsg = '';
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $name = trim($_POST['name'] ?? '');
                            $email = trim($_POST['email'] ?? '');
                            $message = trim($_POST['message'] ?? '');
                            if ($name && $email && $message) {
                                // Always try to get buyer_id from session or by email
                                $buyer_id = null;
                                if (isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'buyer') {
                                    $buyer_id = $_SESSION['user_id'];
                                }
                                if (!$buyer_id && $email) {
                                    $buyerStmt = $pdo->prepare('SELECT buyer_id FROM buyers WHERE email = ? LIMIT 1');
                                    $buyerStmt->execute([$email]);
                                    $buyer = $buyerStmt->fetch();
                                    if ($buyer) {
                                        $buyer_id = $buyer['buyer_id'];
                                    }
                                }
                                try {
                                    $stmt = $pdo->prepare('INSERT INTO messages (buyer_id, admin_id, sender_type, message_text, status) VALUES (?, NULL, \'buyer\', ?, \'unread\')');
                                    $stmt->execute([$buyer_id, $message]);
                                    $successMsg = 'Your message has been sent!';
                                } catch (Exception $e) {
                                    $errorMsg = 'Failed to send message. Please try again later.';
                                }
                            } else {
                                $errorMsg = 'Please fill in all fields.';
                            }
                        }
                        ?>
                        <label for="name" class="form-label">Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label for="message" class="form-label">Message:</label>
                        <textarea id="message" name="message" rows="5" class="form-control" required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Send Message</button>
                    </div>
                </form>
                </div>
            </section>
            <section>
                <h4>Frequently Asked Questions</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 12px;"><strong>How do I create an account?</strong> Visit the signup page and fill out the registration form.</li>
                    <li style="margin-bottom: 12px;"><strong>How can I track my orders?</strong> Go to the 'My Orders' page after logging in.</li>
                    <li style="margin-bottom: 12px;"><strong>How do I contact support?</strong> Use the form above or email us directly.</li>
                </ul>
            </section>
        </div>
    
    <div class="row justify-content-center mt-4 ec-fullscreen-footer">
        <div class="col-md-6 text-center">
            <p>© 2025 Ecocycle. All rights reserved.</p>
        </div>
    </div>
</div>
                                        <?php if ($successMsg): ?>
                                            <div class="alert alert-success" style="display:none;">Message sent!</div>
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Message Sent',
                                                    text: 'Your message has been sent successfully!',
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });
                                            </script>
                                        <?php elseif ($errorMsg): ?>
                                            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                                        <?php endif; ?>
<!-- Font Awesome for icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
    // Fullscreen toggle for modern browsers with graceful fallback
    (function() {
        const fsBtn = document.getElementById('fullscreenBtn');
        const fsLabel = document.getElementById('fsLabel');
        const fsIcon = document.getElementById('fsIcon');
        const root = document.documentElement; // make the whole document fullscreen

        function isFullscreen() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        }

        function updateUI() {
            if (isFullscreen()) {
                fsLabel.textContent = 'Exit Fullscreen';
                fsIcon.className = 'fa-solid fa-compress';
                fsBtn.classList.remove('btn-outline-light');
                fsBtn.classList.add('btn-light');
            } else {
                fsLabel.textContent = 'Fullscreen';
                fsIcon.className = 'fa-solid fa-expand';
                fsBtn.classList.remove('btn-light');
                fsBtn.classList.add('btn-outline-light');
            }
        }

        async function requestFs() {
            try {
                if (root.requestFullscreen) await root.requestFullscreen();
                else if (root.webkitRequestFullscreen) await root.webkitRequestFullscreen();
                else if (root.mozRequestFullScreen) await root.mozRequestFullScreen();
                else if (root.msRequestFullscreen) await root.msRequestFullscreen();
            } catch (err) {
                console.warn('Fullscreen request failed:', err);
            }
            updateUI();
        }

        async function exitFs() {
            try {
                if (document.exitFullscreen) await document.exitFullscreen();
                else if (document.webkitExitFullscreen) await document.webkitExitFullscreen();
                else if (document.mozCancelFullScreen) await document.mozCancelFullScreen();
                else if (document.msExitFullscreen) await document.msExitFullscreen();
            } catch (err) {
                console.warn('Exiting fullscreen failed:', err);
            }
            updateUI();
        }

        fsBtn.addEventListener('click', function () {
            if (isFullscreen()) exitFs();
            else requestFs();
        });

        // Listen to fullscreen change to update UI when user presses ESC etc.
        ['fullscreenchange','webkitfullscreenchange','mozfullscreenchange','MSFullscreenChange'].forEach(evt => {
            document.addEventListener(evt, updateUI);
        });

        // Initial UI state
        updateUI();
    })();
</script>
</body>
</html>
