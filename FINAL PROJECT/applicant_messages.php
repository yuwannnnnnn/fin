<?php
session_start();
require_once 'core/dbConfig.php';

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    header("Location: login.php");
    exit();
}

// Fetch messages sent to the applicant by HR
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
    $hrUserId = $_POST['hr_user_id'];  // HR who sent the original message

    // Insert the reply into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $hrUserId, $messageContent]);

    // Redirect back to the messages page after sending the reply
    header("Location: applicant_messages.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Messages</title>
</head>
<body>
    <h1>Messages</h1>
    <a href="applicant_dashboard.php">Back to Dashboard</a> | <a href="core/handleForms.php?logoutAUser=1">Logout</a>

    <h2>Received Messages</h2>
    <?php if (!empty($messages)): ?>
        <ul>
            <?php foreach ($messages as $message): ?>
                <li>
                    <strong>From: <?php echo htmlspecialchars($message['sender_username']); ?></strong>
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <p><small>Received on: <?php echo htmlspecialchars($message['created_at']); ?></small></p>

                    <!-- Form to reply to the message -->
                    <form method="POST" action="applicant_messages.php">
                        <textarea name="message" rows="4" cols="50" required placeholder="Your reply..."></textarea><br>
                        <input type="hidden" name="hr_user_id" value="<?php echo $message['from_user_id']; ?>">
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
