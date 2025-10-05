<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upwork Clone - Homepage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
        }
        .navbar {
            background: #fff;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: #333;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #2a5298;
        }
        .hero {
            text-align: center;
            padding: 50px;
            background: url('https://source.unsplash.com/random/1600x400?freelance') no-repeat center;
            background-size: cover;
            animation: fadeIn 2s ease-in-out;
        }
        .hero h1 {
            font-size: 3em;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .job-list, .freelancer-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
        }
        .job-card, .freelancer-card {
            background: #fff;
            color: #333;
            margin: 10px;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .job-card:hover, .freelancer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .job-card, .freelancer-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div>
            <a href="index.php">Home</a>
            <a href="browse_jobs.php">Browse Jobs</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Profile</a>
                <a href="post_job.php">Post Job</a>
                <a href="messages.php">Messages</a>
                <a href="contracts.php">Contracts</a>
                <a href="#" onclick="logout()">Logout</a>
            <?php else: ?>
                <a href="signup.php">Sign Up</a>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero">
        <h1>Find the Perfect Freelancer for Your Project</h1>
        <p>Explore top talent and exciting projects!</p>
    </div>
    <div class="job-list">
        <h2>Featured Jobs</h2>
        <?php
        $stmt = $conn->query("SELECT * FROM jobs ORDER BY posted_at DESC LIMIT 3");
        while ($job = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<div class='job-card'>";
            echo "<h3>" . htmlspecialchars($job['title']) . "</h3>";
            echo "<p>" . htmlspecialchars($job['description']) . "</p>";
            echo "<p>Budget: $" . $job['budget'] . " (" . $job['job_type'] . ")</p>";
            echo "<a href='apply_job.php?job_id=" . $job['id'] . "'>Apply Now</a>";
            echo "</div>";
        }
        ?>
    </div>
    <div class="freelancer-list">
        <h2>Top Freelancers</h2>
        <?php
        $stmt = $conn->query("SELECT u.username, f.skills FROM users u JOIN freelancer_profiles f ON u.id = f.user_id LIMIT 3");
        while ($freelancer = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<div class='freelancer-card'>";
            echo "<h3>" . htmlspecialchars($freelancer['username']) . "</h3>";
            echo "<p>Skills: " . htmlspecialchars($freelancer['skills']) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
    <script>
        function logout() {
            window.location.href = 'login.php?logout=true';
        }
    </script>
</body>
</html>
