<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: login.php");
    exit();
}
require_once 'core/dbConfig.php';

// Fetch messages sent to HR
$stmt = $pdo->prepare("SELECT m.id, m.from_user_id, m.message, m.created_at, u.username AS sender_username 
                        FROM messages m
                        JOIN users u ON m.from_user_id = u.id
                        WHERE m.to_user_id = ? 
                        ORDER BY m.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Handle the reply form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $messageContent = $_POST['message'];
    $applicantId = $_POST['applicant_id'];  // Applicant who sent the original message

    // Insert the reply into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $applicantId, $messageContent]);

    // Redirect back to the messages page after sending the reply
    header("Location: messages.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
</head>
<body>
    <h1>Messages</h1>
    <a href="hr_dashboard.php">HR Dashboard</a> | <a href="createJobPost.php">Create Job Post</a> | <a href="viewApplications.php">View Applications</a>
    <a href="core/handleForms.php?logoutAUser=1">Logout</a>

    <h2>Received Messages</h2>
    <?php if (!empty($messages)): ?>
        <ul>
            <?php foreach ($messages as $message): ?>
                <li>
                    <strong>From: <?php echo htmlspecialchars($message['sender_username']); ?></strong>
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <p><small>Received on: <?php echo htmlspecialchars($message['created_at']); ?></small></p>

                    <!-- Form to reply to the message -->
                    <form method="POST" action="">
                        <textarea name="message" rows="4" cols="50" required placeholder="Your reply..."></textarea><br>
                        <input type="hidden" name="applicant_id" value="<?php echo $message['from_user_id']; ?>">
                        <button type="submit" name="reply_message">Send Reply</button>
                    </form>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No messages available.</p>
    <?php endif; ?>

</body>
</html>
