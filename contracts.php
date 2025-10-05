<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_type == 'client') {
        $job_id = $_POST['job_id'] ?? 0;
        $freelancer_id = $_POST['freelancer_id'] ?? 0;
        $terms = trim($_POST['terms'] ?? '');
        $client_id = $user_id;

        if (!$job_id || !$freelancer_id || !$terms) {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM proposals WHERE job_id = ? AND freelancer_id = ? AND status = 'pending'");
            $stmt->execute([$job_id, $freelancer_id]);
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
                $stmt->execute([$job_id, $client_id]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("INSERT INTO contracts (job_id, freelancer_id, client_id, terms) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$job_id, $freelancer_id, $client_id, $terms]);
                    $success = "Contract created successfully!";
                    $stmt = $conn->prepare("UPDATE proposals SET status = 'accepted' WHERE job_id = ? AND freelancer_id = ?");
                    $stmt->execute([$job_id, $freelancer_id]);
                } else {
                    $error = "You can only create contracts for your own jobs.";
                }
            } else {
                $error = "Selected freelancer has not applied to this job or the proposal is not pending.";
            }
        }
    }

    $stmt = $conn->prepare("SELECT c.*, j.title, u1.username AS freelancer, u2.username AS client 
                            FROM contracts c 
                            JOIN jobs j ON c.job_id = j.id 
                            JOIN users u1 ON c.freelancer_id = u1.id 
                            JOIN users u2 ON c.client_id = u2.id 
                            WHERE c.freelancer_id = ? OR c.client_id = ?");
    $stmt->execute([$user_id, $user_id]);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $jobs = [];
    $category_id = $_GET['category_id'] ?? null;
    if ($user_type == 'client') {
        $query = "SELECT j.id, j.title, c.name AS category_name 
                  FROM jobs j 
                  LEFT JOIN categories c ON j.category_id = c.id 
                  WHERE j.client_id = ?";
        if ($category_id) {
            $query .= " AND j.category_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $category_id]);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
        }
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $proposals = [];
    if ($user_type == 'client') {
        $query = "SELECT p.*, j.title, u.username, c.name AS category_name 
                  FROM proposals p 
                  JOIN jobs j ON p.job_id = j.id 
                  JOIN users u ON p.freelancer_id = u.id 
                  LEFT JOIN categories c ON j.category_id = c.id 
                  WHERE j.client_id = ? AND p.status = 'pending'";
        if ($category_id) {
            $query .= " AND j.category_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $category_id]);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
        }
        $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $freelancer_proposals = [];
    if ($user_type == 'freelancer') {
        $stmt = $conn->prepare("SELECT p.*, j.title, u.username AS client, c.name AS category_name 
                                FROM proposals p 
                                JOIN jobs j ON p.job_id = j.id 
                                JOIN users u ON j.client_id = u.id 
                                LEFT JOIN categories c ON j.category_id = c.id 
                                WHERE p.freelancer_id = ?");
        $stmt->execute([$user_id]);
        $freelancer_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #1e3c72, #2a5298); color: #fff; margin: 0; }
        .contract-container { max-width: 800px; margin: 50px auto; background: #fff; color: #333; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .contract-card, .proposal-card { padding: 15px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; transition: transform 0.3s; }
        .contract-card:hover, .proposal-card:hover { transform: translateY(-5px); }
        .form-container { margin-top: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background: #f9fafb; }
        select, textarea, button { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; }
        button { background: #2a5298; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #1e3c72; }
        .error { color: #dc2626; margin: 10px 0; }
        .success { color: #16a34a; margin: 10px 0; }
        .no-data { text-align: center; margin: 20px 0; font-size: 1.2em; }
        .no-data a, .proposal-card a { color: #2a5298; text-decoration: none; font-weight: bold; }
        .no-data a:hover, .proposal-card a:hover { color: #1e3c72; }
    </style>
</head>
<body>
    <div class="contract-container">
        <h2>Your Contracts</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (empty($contracts)): ?>
            <p class="no-data">
                No contracts found. 
                <?php if ($user_type == 'client'): ?>
                    Post a <a href="post_job.php">new job</a> or check pending proposals below.
                <?php else: ?>
                    Browse <a href="browse_jobs.php">jobs</a> and apply.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <?php foreach ($contracts as $contract): ?>
                <div class="contract-card">
                    <h3><?php echo htmlspecialchars($contract['title']); ?></h3>
                    <p>Freelancer: <?php echo htmlspecialchars($contract['freelancer']); ?></p>
                    <p>Client: <?php echo htmlspecialchars($contract['client']); ?></p>
                    <p>Terms: <?php echo htmlspecialchars($contract['terms'] ?? 'No terms specified'); ?></p>
                    <p>Status: <?php echo $contract['status']; ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($user_type == 'freelancer'): ?>
            <h3>Your Submitted Proposals</h3>
            <?php if (empty($freelancer_proposals)): ?>
                <p class="no-data">No proposals submitted. Browse <a href="browse_jobs.php">jobs</a>.</p>
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
        <?php endif; ?>

        <?php if ($user_type == 'client'): ?>
            <h3>Filter by Category</h3>
            <form method="GET">
                <select name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <h3>Pending Proposals</h3>
            <?php if (empty($proposals)): ?>
                <p class="no-data">No pending proposals. Post a <a href="post_job.php">new job</a>.</p>
            <?php else: ?>
                <?php foreach ($proposals as $proposal): ?>
                    <div class="proposal-card">
                        <h4><?php echo htmlspecialchars($proposal['title']); ?></h4>
                        <p>Freelancer: <?php echo htmlspecialchars($proposal['username']); ?></p>
                        <p>Category: <?php echo htmlspecialchars($proposal['category_name'] ?? 'None'); ?></p>
                        <p>Proposal: <?php echo htmlspecialchars($proposal['proposal_text']); ?></p>
                        <p>Bid: $<?php echo $proposal['bid_amount']; ?></p>
                        <a href="contracts.php?job_id=<?php echo $proposal['job_id']; ?>&freelancer_id=<?php echo $proposal['freelancer_id']; ?>">Create Contract</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-container">
                <h3>Create New Contract</h3>
                <?php if (empty($jobs)): ?>
                    <p class="no-data">No jobs posted. <a href="post_job.php">Post a job</a>.</p>
                <?php else: ?>
                    <form method="POST">
                        <select name="job_id" id="job_id" required onchange="loadFreelancers()">
                            <option value="">Select Job</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job['id']; ?>" <?php echo (isset($_GET['job_id']) && $_GET['job_id'] == $job['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($job['title'] . ' (' . ($job['category_name'] ?? 'No Category') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="freelancer_id" id="freelancer_id" required>
                            <option value="">Select Freelancer</option>
                            <?php if (isset($_GET['freelancer_id'])): ?>
                                <?php
                                $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
                                $stmt->execute([$_GET['freelancer_id']]);
                                $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($freelancer): ?>
                                    <option value="<?php echo $freelancer['id']; ?>" selected><?php echo htmlspecialchars($freelancer['username']); ?></option>
                                <?php endif; ?>
                            <?php endif; ?>
                        </select>
                        <textarea name="terms" placeholder="Contract Terms (e.g., milestones, deliverables)" required></textarea>
                        <button type="submit">Create Contract</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
        function loadFreelancers() {
            const jobId = document.getElementById('job_id').value;
            const freelancerSelect = document.getElementById('freelancer_id');
            freelancerSelect.innerHTML = '<option value="">Select Freelancer</option>';

            if (jobId) {
                fetch('get_freelancers.php?job_id=' + jobId)
                    .then(response => {
                        if (!response.ok) throw new Error('Network error: ' + response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            console.error('Server error:', data.error);
                            freelancerSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                        } else if (data.length === 0) {
                            freelancerSelect.innerHTML = '<option value="">No freelancers applied</option>';
                        } else {
                            data.forEach(freelancer => {
                                const option = document.createElement('option');
                                option.value = freelancer.id;
                                option.textContent = freelancer.username;
                                freelancerSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        freelancerSelect.innerHTML = '<option value="">Error loading freelancers</option>';
                    });
            }
        }
        <?php if (isset($_GET['job_id'])): ?>
            loadFreelancers();
        <?php endif; ?>
    </script>
</body>
</html>
