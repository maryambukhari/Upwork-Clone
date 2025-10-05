<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'freelancer') {
    echo "<script>window.location.href='login.php';</script>";
}

$user_id = $_SESSION['user_id'];
$job_id = $_GET['job_id'] ?? 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $proposal_text = $_POST['proposal_text'];
    $bid_amount = $_POST['bid_amount'];

    if (empty($proposal_text) || empty($bid_amount)) {
        $error = "All fields are required.";
    } else {
        // Check if job exists and get its category
        $stmt = $conn->prepare("SELECT category_id FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            // Check if freelancer has matching category
            $stmt = $conn->prepare("SELECT * FROM user_categories WHERE user_id = ? AND category_id = ?");
            $stmt->execute([$user_id, $job['category_id']]);
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("INSERT INTO proposals (job_id, freelancer_id, proposal_text, bid_amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$job_id, $user_id, $proposal_text, $bid_amount]);
                $success = "Proposal submitted successfully!";
                echo "<script>window.location.href='browse_jobs.php';</script>";
            } else {
                $error = "You do not have the required skills for this job's category.";
            }
        } else {
            $error = "Invalid job ID.";
        }
    }
}

// Fetch job details
$stmt = $conn->prepare("SELECT j.*, c.name AS category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            margin: 0;
        }
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: fadeIn 1s ease-in-out;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .form-container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Apply for Job: <?php echo htmlspecialchars($job['title'] ?? ''); ?></h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($job): ?>
            <p>Category: <?php echo htmlspecialchars($job['category_name'] ?? 'None'); ?></p>
            <p>Description: <?php echo htmlspecialchars($job['description']); ?></p>
            <p>Budget: $<?php echo $job['budget']; ?></p>
            <p>Type: <?php echo $job['job_type']; ?></p>
            <form method="POST">
                <textarea name="proposal_text" placeholder="Your Proposal" required></textarea>
                <input type="number" name="bid_amount" placeholder="Your Bid Amount" step="0.01" required>
                <button type="submit">Submit Proposal</button>
            </form>
        <?php else: ?>
            <p class="error">Job not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
