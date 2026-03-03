<?php
include 'adminheader.php';
include 'adminsidebar.php';
require_once __DIR__ . '/../config/database.php';

// Get all messages including contact form submissions
$stmt = $pdo->query('
    SELECT m1.* FROM messages m1
    INNER JOIN (
        SELECT COALESCE(buyer_id, 0) as buyer_id, MAX(created_at) AS max_created
        FROM messages
        GROUP BY COALESCE(buyer_id, 0)
    ) m2 ON COALESCE(m1.buyer_id, 0) = m2.buyer_id AND m1.created_at = m2.max_created
    ORDER BY m1.created_at DESC
');
$messages = $stmt->fetchAll();
?>
<main class="admin-main-content" style="margin-left:280px; padding:40px 20px 20px 20px; min-height:100vh; background:#f8f9fa;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Received Messages & Contact Forms</h2>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($messages)): ?>
                            <tr><td colspan="5" class="text-center">No messages found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Check if this is a contact form submission (buyer_id = 0 or NULL)
                                        if (empty($msg['buyer_id'])) {
                                            // Extract name from contact form submission message
                                            if (preg_match('/Name: (.+?)\n/', $msg['message_text'], $matches)) {
                                                echo htmlspecialchars($matches[1]);
                                            } else {
                                                echo 'Contact Form';
                                            }
                                        } else {
                                            // Get buyer name from database
                                            $buyerStmt = $pdo->prepare('SELECT fullname, email FROM buyers WHERE buyer_id = ? LIMIT 1');
                                            $buyerStmt->execute([$msg['buyer_id']]);
                                            $buyer = $buyerStmt->fetch();
                                            echo htmlspecialchars($buyer['fullname'] ?? 'N/A');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (empty($msg['buyer_id'])) {
                                            // Extract email from contact form submission
                                            if (preg_match('/Email: (.+?)\n/', $msg['message_text'], $matches)) {
                                                echo htmlspecialchars($matches[1]);
                                            } else {
                                                echo 'N/A';
                                            }
                                        } else {
                                            $buyerStmt = $pdo->prepare('SELECT email FROM buyers WHERE buyer_id = ? LIMIT 1');
                                            $buyerStmt->execute([$msg['buyer_id']]);
                                            $buyer = $buyerStmt->fetch();
                                            echo htmlspecialchars($buyer['email'] ?? 'N/A');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $text = htmlspecialchars($msg['message_text']);
                                        // Show first 100 characters for contact forms, full text for others
                                        if (empty($msg['buyer_id'])) {
                                            echo substr($text, 0, 100) . '...';
                                        } else {
                                            echo $text;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($msg['created_at']); ?></td>
                                    <td>
                                        <?php if (!empty($msg['buyer_id'])): ?>
                                            <?php
                                            $buyerStmt = $pdo->prepare('SELECT email FROM buyers WHERE buyer_id = ? LIMIT 1');
                                            $buyerStmt->execute([$msg['buyer_id']]);
                                            $buyer = $buyerStmt->fetch();
                                            if (!empty($buyer) && !empty($buyer['email'])):
                                            ?>
                                                <a href="adminchat.php?buyer_id=<?php echo urlencode($msg['buyer_id']); ?>" class="btn btn-success btn-sm"><i class="fas fa-reply"></i> Reply</a>
                                            <?php else: ?>
                                                <span class="text-muted">No Email</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Contact form submission - view button -->
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewContactModal" onclick="viewContactDetails(<?php echo htmlspecialchars(json_encode($msg['message_text']), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars($msg['created_at'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- View Contact Form Modal -->
<div class="modal fade" id="viewContactModal" tabindex="-1" aria-labelledby="viewContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%); color: white;">
                <h5 class="modal-title" id="viewContactModalLabel">Contact Form Submission Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: #ffffff; color: #000;">
                <div id="contactDetails" style="color: #000; font-family: Arial, sans-serif;"></div>
            </div>
            <div class="modal-footer" style="background-color: #f8f9fa;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
#contactDetails {
    color: #333;
}
.contact-details-container strong {
    color: #1a5f7a;
}
</style>


<script>
function viewContactDetails(messageText, createdAt) {
    console.log('Message Text:', messageText);
    console.log('Created At:', createdAt);
    
    const contentDiv = document.getElementById('contactDetails');
    
    if (!messageText) {
        contentDiv.innerHTML = '<div style="color: #000; padding: 20px; text-align: center;"><p>❌ No message data available</p></div>';
        return;
    }
    
    // Parse message details
    const lines = messageText.split('\n');
    let html = '<div style="color: #000; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6;">';
    
    // Add submission date/time
    if (createdAt) {
        html += '<div style="background: #e8f4f8; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #1a5f7a;">';
        html += '<strong style="color: #1a5f7a;">📅 Submitted:</strong> <span style="color: #333;">' + escapeHtml(createdAt) + '</span>';
        html += '</div>';
    }
    
    // Display all lines
    let inMessageSection = false;
    
    lines.forEach((line) => {
        const trimmed = line.trim();
        
        if (!trimmed) return;
        
        if (trimmed === 'Message:') {
            inMessageSection = true;
            html += '<div style="margin-top: 15px; margin-bottom: 10px;"><strong style="color: #1a5f7a; font-size: 16px;">📝 Message Content:</strong></div>';
            html += '<div style="background: #f0f8ff; border: 1px solid #28bf4b; border-left: 4px solid #28bf4b; padding: 15px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; color: #000;">';
            return;
        }
        
        if (inMessageSection) {
            html += escapeHtml(line) + '<br>';
        } else {
            // Display header fields (Name, Email, etc.)
            if (trimmed.includes(':')) {
                const [key, ...valueParts] = trimmed.split(':');
                const value = valueParts.join(':').trim();
                
                if (key && value) {
                    let backgroundColor = '#f8f9fa';
                    let borderColor = '#ddd';
                    
                    if (key.includes('User Type')) {
                        backgroundColor = '#e3f2fd';
                        borderColor = '#007bff';
                    } else if (key.includes('Urgency')) {
                        backgroundColor = '#fff3e0';
                        borderColor = '#ff9800';
                    } else if (key.includes('Issue')) {
                        backgroundColor = '#f3e5f5';
                        borderColor = '#9c27b0';
                    }
                    
                    html += '<div style="background: ' + backgroundColor + '; border-left: 4px solid ' + borderColor + '; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px;">';
                    html += '<strong style="color: #333; display: inline-block; min-width: 120px;">' + escapeHtml(key) + ':</strong>';
                    html += '<span style="color: #000;">' + escapeHtml(value) + '</span>';
                    html += '</div>';
                }
            }
        }
    });
    
    if (inMessageSection) {
        html += '</div>';
    }
    
    html += '</div>';
    
    if (contentDiv) {
        contentDiv.innerHTML = html;
        console.log('Contact details displayed successfully');
    } else {
        console.error('contactDetails div not found');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}
</script>

<!-- Sidebar and header already included above -->
