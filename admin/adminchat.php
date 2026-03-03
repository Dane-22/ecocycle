<?php
// adminchat.php - Admin chat with a specific buyer
include 'adminheader.php';
include 'adminsidebar.php';
require_once __DIR__ . '/../config/database.php';

$buyer_id = isset($_GET['buyer_id']) ? intval($_GET['buyer_id']) : 0;
if (!$buyer_id) {
    echo '<div class="alert alert-danger m-4">Invalid buyer selected.</div>';
    exit;
}

// Get buyer info
$buyerStmt = $pdo->prepare('SELECT fullname, email FROM buyers WHERE buyer_id = ? LIMIT 1');
$buyerStmt->execute([$buyer_id]);
$buyer = $buyerStmt->fetch();
if (!$buyer) {
    echo '<div class="alert alert-danger m-4">Buyer not found.</div>';
    exit;
}

// Handle admin reply
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $reply = trim($_POST['reply_message']);
    if ($reply) {
        try {
            $admin_id = $_SESSION['admin_id'] ?? null;
            $stmt = $pdo->prepare('INSERT INTO messages (buyer_id, admin_id, sender_type, message_text, status) VALUES (?, ?, \'admin\', ?, \'unread\')');
            $stmt->execute([$buyer_id, $admin_id, $reply]);
            $successMsg = 'Reply sent!';
        } catch (Exception $e) {
            $errorMsg = 'Failed to send reply.';
        }
    } else {
        $errorMsg = 'Please enter a message.';
    }
}

// Fetch all messages for this buyer (threaded)
$stmt = $pdo->prepare('SELECT * FROM messages WHERE buyer_id = ? ORDER BY created_at ASC');
$stmt->execute([$buyer_id]);
$messages = $stmt->fetchAll();
?>
<style>
.messenger-container {
    max-width: 700px;
    margin: 40px auto 0 auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 32px rgba(44,120,108,0.08);
    display: flex;
    flex-direction: column;
    height: 80vh;
    min-height: 500px;
    overflow: hidden;
}
.messenger-header {
    background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 100%);
    color: #fff;
    padding: 18px 32px;
    font-size: 1.2rem;
    font-weight: 600;
    letter-spacing: 1px;
    border-bottom: 1px solid #e0e0e0;
}
.messenger-messages {
    flex: 1 1 auto;
    padding: 32px 24px 24px 24px;
    overflow-y: auto;
    background: #f7fafb;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.messenger-bubble {
    max-width: 65%;
    padding: 14px 18px;
    border-radius: 18px;
    font-size: 1rem;
    position: relative;
    word-break: break-word;
    box-shadow: 0 2px 8px rgba(44,120,108,0.06);
    margin-bottom: 2px;
}
.messenger-buyer {
    align-self: flex-start;
    background: #e9ecef;
    color: #222;
    border-bottom-left-radius: 6px;
}
.messenger-admin {
    align-self: flex-end;
    background: linear-gradient(135deg, #28bf4b 0%, #2c786c 100%);
    color: #fff;
    border-bottom-right-radius: 6px;
}
.messenger-meta {
    font-size: 0.85rem;
    color: #888;
    margin-top: 2px;
    margin-bottom: 0;
    text-align: right;
}
.messenger-meta.admin {
    text-align: left;
}
.messenger-input-area {
    background: #f7fafb;
    padding: 18px 24px 18px 24px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.messenger-input-area textarea {
    flex: 1 1 auto;
    resize: none;
    border-radius: 12px;
    border: 1px solid #cfd8dc;
    padding: 10px 14px;
    font-size: 1rem;
    min-height: 44px;
    max-height: 120px;
    background: #fff;
}
.messenger-input-area button {
    flex: 0 0 auto;
    border-radius: 12px;
    padding: 10px 24px;
    font-size: 1rem;
    font-weight: 600;
    background: linear-gradient(135deg, #28bf4b 0%, #2c786c 100%);
    color: #fff;
    border: none;
    transition: background 0.2s;
}
.messenger-input-area button:hover {
    background: linear-gradient(135deg, #2c786c 0%, #28bf4b 100%);
}
@media (max-width: 768px) {
    .messenger-container { max-width: 100vw; border-radius: 0; }
    .messenger-header { padding: 14px 10px; font-size: 1rem; }
    .messenger-messages { padding: 16px 6px 12px 6px; }
    .messenger-input-area { padding: 10px 6px; }
}
</style>
<main class="admin-main-content" style="margin-left:280px; padding:40px 20px 20px 20px; min-height:100vh; background:#f8f9fa;">
    <div class="messenger-container">
        <div class="messenger-header">Chat with <?php echo htmlspecialchars($buyer['fullname']); ?> (<?php echo htmlspecialchars($buyer['email']); ?>)</div>
        <?php if ($successMsg): ?>
            <div class="alert alert-success m-0 text-center"><?php echo $successMsg; ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger m-0 text-center"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        <div class="messenger-messages" id="messengerMessages">
            <?php if (empty($messages)): ?>
                <p class="text-center text-muted">No messages yet.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="messenger-bubble <?php echo $msg['sender_type'] === 'admin' ? 'messenger-admin' : 'messenger-buyer'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                        <div class="messenger-meta <?php echo $msg['sender_type'] === 'buyer' ? 'admin' : ''; ?>">
                            <?php echo $msg['sender_type'] === 'admin' ? 'You (Admin)' : htmlspecialchars($buyer['fullname']); ?> &middot; <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                        </div>
                        <?php if ($msg['sender_type'] === 'admin' && !empty($msg['feedback_text'])): ?>
                            <div class="mt-2 alert alert-info p-2 small">Feedback: <?php echo htmlspecialchars($msg['feedback_text']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="post" class="messenger-input-area">
            <textarea name="reply_message" class="form-control" rows="1" placeholder="Type your message to customer..." required></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
</main>
<script>
// Auto-scroll to bottom
window.onload = function() {
    var msgBox = document.getElementById('messengerMessages');
    if (msgBox) msgBox.scrollTop = msgBox.scrollHeight;
};
</script>
