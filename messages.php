<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_id = $_POST['job_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, job_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $job_id, $message]);
    $success = "Message sent successfully!";
    echo "<script>window.location.href='messages.php?job_id=$job_id&receiver_id=$receiver_id';</script>";
}

// Fetch conversations
$conversations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT m.job_id, m.receiver_id, j.title, u.username 
    FROM messages m 
    JOIN jobs j ON m.job_id = j.id 
    JOIN users u ON m.receiver_id = u.id 
    WHERE m.sender_id = ? OR m.receiver_id = ?
    UNION
    SELECT DISTINCT m.job_id, m.sender_id AS receiver_id, j.title, u.username 
    FROM messages m 
    JOIN jobs j ON m.job_id = j.id 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = ?
");
$stmt->execute([$user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch messages for selected conversation
$messages = [];
$job_id = $_GET['job_id'] ?? null;
$receiver_id = $_GET['receiver_id'] ?? null;
if ($job_id && $receiver_id) {
    $stmt = $conn->prepare("
        SELECT m.*, u.username 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ? OR m.sender_id = ? AND m.receiver_id = ?) 
        AND m.job_id = ? 
        ORDER BY m.sent_at
    ");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id, $job_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch available jobs/users to start a new conversation
$available_jobs = [];
if ($user_type == 'client') {
    $stmt = $conn->prepare("SELECT j.id, j.title, c.name AS category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.client_id = ?");
    $stmt->execute([$user_id]);
    $available_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT j.id, j.title, c.name AS category_name FROM jobs j JOIN proposals p ON j.id = p.job_id LEFT JOIN categories c ON j.category_id = c.id WHERE p.freelancer_id = ? AND p.status = 'accepted'");
    $stmt->execute([$user_id]);
    $available_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch submitted proposals for the freelancer
$freelancer_proposals = [];
if ($user_type == 'freelancer') {
    $stmt = $conn->prepare("SELECT p.*, j.title, u.username AS client, c.name AS category_name FROM proposals p JOIN jobs j ON p.job_id = j.id JOIN users u ON j.client_id = u.id LEFT JOIN categories c ON j.category_id = c.id WHERE p.freelancer_id = ?");
    $stmt->execute([$user_id]);
    $freelancer_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            margin: 0;
        }
        .message-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: fadeIn 1s ease-in-out;
        }
        .conversation-list, .proposal-list {
            margin-bottom: 20px;
        }
        .conversation-item, .proposal-card {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            transition: background 0.3s;
        }
        .conversation-item:hover, .proposal-card:hover {
            background: #f9fafb;
        }
        .conversation-item a {
            color: #2a5298;
            text-decoration: none;
            font-weight: bold;
        }
        .conversation-item a:hover {
            color: #1e3c72;
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .message.sent {
            background: #2a5298;
            color: #fff;
            margin-left: 20%;
        }
        .message.received {
            background: #e5e7eb;
            margin-right: 20%;
        }
        .form-container {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f9fafb;
        }
        select, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #2a5298;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        button:hover {
            background: #1e3c72;
            transform: translateY(-3px);
        }
        .error {
            color: #dc2626;
            margin: 10px 0;
        }
        .success {
            color: #16a34a;
            margin: 10px 0;
        }
        .no-data {
            text-align: center;
            margin: 20px 0;
            font-size: 1.2em;
        }
        .no-data a {
            color: #2a5298;
            text-decoration: none;
            font-weight: bold;
        }
        .no-data a:hover {
            color: #1e3c72;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .message-container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="message-container">
        <h2>Messages</h2>
        <div class="conversation-list">
            <h3>Your Conversations</h3>
            <?php if (empty($conversations)): ?>
                <p class="no-data">
                    No conversations yet. 
                    <?php if ($user_type == 'client'): ?>
                        Post a <a href="post_job.php">new job</a> to start messaging freelancers.
                    <?php else: ?>
                        Browse <a href="browse_jobs.php">jobs</a> and apply. You need an accepted proposal to message clients.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item">
                        <a href="messages.php?job_id=<?php echo $conv['job_id']; ?>&receiver_id=<?php echo $conv['receiver_id']; ?>">
                            <?php echo htmlspecialchars($conv['title'] . ' (' . ($conv['category_name'] ?? 'No Category') . ') - ' . $conv['username']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($user_type == 'freelancer'): ?>
            <div class="proposal-list">
                <h3>Your Submitted Proposals</h3>
                <?php if (empty($freelancer_proposals)): ?>
                    <p class="no-data">No proposals submitted yet. Browse <a href="browse_jobs.php">jobs</a> and apply.</p>
                <?php else: ?>
                    <?php foreach ($freelancer_proposals as $proposal): ?>
                        <div class="proposal-card">
                            <h4><?php echo htmlspecialchars($proposal['title']); ?></h4>
                            <p>Client: <?php echo htmlspecialchars($proposal['client']); ?></p>
                            <p>Category: <?php echo htmlspecialchars($proposal['category_name'] ?? 'None'); ?></p>
                            <p>Proposal: <?php echo htmlspecialchars($proposal['proposal_text']); ?></p>
                            <p>Bid: $<?php echo $proposal['bid_amount']; ?></p>
                            <p>Status: <?php echo $proposal['status']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($job_id && $receiver_id): ?>
            <div>
                <h3>Conversation for <?php echo htmlspecialchars($messages[0]['username'] ?? 'Unknown User'); ?></h3>
                <?php if ($success): ?>
                    <p class="success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>
                <?php if (empty($messages)): ?>
                    <p class="no-data">No messages yet in this conversation.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <strong><?php echo htmlspecialchars($msg['username']); ?>:</strong>
                            <p><?php echo htmlspecialchars($msg['message']); ?></p>
                            <small><?php echo $msg['sent_at']; ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                        <textarea name="message" placeholder="Type your message" required></textarea>
                        <button type="submit">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <h3>Start a New Conversation</h3>
                    <?php if (empty($available_jobs)): ?>
                        <p class="no-data">
                            No jobs available to message. 
                            <?php if ($user_type == 'client'): ?>
                                Post a <a href="post_job.php">new job</a>.
                            <?php else: ?>
                                Browse <a href="browse_jobs.php">jobs</a> and apply. You need an accepted proposal to message clients.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <form method="GET">
                            <select name="job_id" id="job_id" required onchange="loadReceivers()">
                                <option value="">Select Job</option>
                                <?php foreach ($available_jobs as $job): ?>
                                    <option value="<?php echo $job['id']; ?>" <?php if (isset($_GET['job_id']) && $_GET['job_id'] == $job['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($job['title'] . ' (' . ($job['category_name'] ?? 'No Category') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="receiver_id" id="receiver_id" required>
                                <option value="">Select User</option>
                                <?php if (isset($_GET['receiver_id'])): ?>
                                    <?php
                                    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
                                    $stmt->execute([$_GET['receiver_id']]);
                                    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <option value="<?php echo $receiver['id']; ?>" selected><?php echo htmlspecialchars($receiver['username']); ?></option>
                                <?php endif; ?>
                            </select>
                            <button type="submit">Start Conversation</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
    </div>
    <script>
        function loadReceivers() {
            const jobId = document.getElementById('job_id').value;
            const receiverSelect = document.getElementById('receiver_id');
            receiverSelect.innerHTML = '<option value="">Select User</option>';

            if (jobId) {
                fetch('get_receivers.php?job_id=' + jobId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            receiverSelect.innerHTML = '<option value="">No users available</option>';
                        } else {
                            data.forEach(user => {
                                const option = document.createElement('option');
                                option.value = user.id;
                                option.textContent = user.username;
                                receiverSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching users:', error));
            }
        }
        <?php if (isset($_GET['job_id'])): ?>
            loadReceivers();
        <?php endif; ?>
    </script>
</body>
</html>
