<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($user_type == 'freelancer') {
        $skills = $_POST['skills'];
        $experience = $_POST['experience'];
        $portfolio = $_POST['portfolio'];
        $hourly_rate = $_POST['hourly_rate'];

        $stmt = $conn->prepare("UPDATE freelancer_profiles SET skills = ?, experience = ?, portfolio = ?, hourly_rate = ? WHERE user_id = ?");
        $stmt->execute([$skills, $experience, $portfolio, $hourly_rate, $user_id]);
    } else {
        $company_name = $_POST['company_name'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("UPDATE client_profiles SET company_name = ?, description = ? WHERE user_id = ?");
        $stmt->execute([$company_name, $description, $user_id]);
    }
    echo "<script>window.location.href='profile.php';</script>";
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_type == 'freelancer') {
    $stmt = $conn->prepare("SELECT * FROM freelancer_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            margin: 0;
        }
        .profile-container {
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
        }
        button {
            background: #2a5298;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #1e3c72;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>
        <form method="POST">
            <?php if ($user_type == 'freelancer'): ?>
                <input type="text" name="skills" placeholder="Skills" value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>">
                <textarea name="experience" placeholder="Experience"><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                <input type="text" name="portfolio" placeholder="Portfolio URL" value="<?php echo htmlspecialchars($profile['portfolio'] ?? ''); ?>">
                <input type="number" name="hourly_rate" placeholder="Hourly Rate" value="<?php echo htmlspecialchars($profile['hourly_rate'] ?? ''); ?>">
            <?php else: ?>
                <input type="text" name="company_name" placeholder="Company Name" value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>">
                <textarea name="description" placeholder="Description"><?php echo htmlspecialchars($profile['description'] ?? ''); ?></textarea>
            <?php endif; ?>
            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>
