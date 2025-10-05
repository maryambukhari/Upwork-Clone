<?php
session_start();
include 'db.php';

$category = $_GET['category'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$min_budget = $_GET['min_budget'] ?? 0;

$query = "SELECT * FROM jobs WHERE 1=1";
$params = [];
if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}
if ($job_type) {
    $query .= " AND job_type = ?";
    $params[] = $job_type;
}
if ($min_budget) {
    $query .= " AND budget >= ?";
    $params[] = $min_budget;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            margin: 0;
        }
        .filter-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            color: #333;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .job-list {
            max-width: 800px;
            margin: 20px auto;
        }
        .job-card {
            background: #fff;
            color: #333;
            padding: 20px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.3s;
        }
        .job-card:hover {
            transform: translateY(-5px);
        }
        input, select, button {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #2a5298;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #1e3c72;
        }
    </style>
</head>
<body>
    <div class="filter-container">
        <h2>Filter Jobs</h2>
        <form method="GET">
            <input type="text" name="category" placeholder="Category" value="<?php echo htmlspecialchars($category); ?>">
            <select name="job_type">
                <option value="">All Types</option>
                <option value="hourly" <?php if ($job_type == 'hourly') echo 'selected'; ?>>Hourly</option>
                <option value="fixed" <?php if ($job_type == 'fixed') echo 'selected'; ?>>Fixed</option>
            </select>
            <input type="number" name="min_budget" placeholder="Min Budget" value="<?php echo htmlspecialchars($min_budget); ?>">
            <button type="submit">Filter</button>
        </form>
    </div>
    <div class="job-list">
        <h2>Available Jobs</h2>
        <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                <p><?php echo htmlspecialchars($job['description']); ?></p>
                <p>Category: <?php echo htmlspecialchars($job['category']); ?></p>
                <p>Budget: $<?php echo $job['budget']; ?> (<?php echo $job['job_type']; ?>)</p>
                <p>Deadline: <?php echo $job['deadline']; ?></p>
                <a href="apply_job.php?job_id=<?php echo $job['id']; ?>">Apply Now</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
