<?php
session_start();
include 'db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    if (empty($username) || empty($email) || empty($password) || empty($user_type)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check for duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username '$username' is already taken.";
        } else {
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email '$email' is already registered.";
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $user_type]);
                    $user_id = $conn->lastInsertId();

                    // Save freelancer categories
                    if ($user_type == 'freelancer') {
                        $categories = $_POST['categories'] ?? [];
                        if (empty($categories)) {
                            $error = "Freelancers must select at least one category.";
                        } else {
                            foreach ($categories as $category_id) {
                                $stmt = $conn->prepare("INSERT INTO user_categories (user_id, category_id) VALUES (?, ?)");
                                $stmt->execute([$user_id, $category_id]);
                            }
                        }
                    }

                    if (!$error) {
                        $success = "Registration successful! Redirecting to login...";
                        echo "<script>setTimeout(() => { window.location.href='login.php'; }, 2000);</script>";
                    }
                } catch (PDOException $e) {
                    $error = "Registration error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch categories
try {
    $stmt = $conn->prepare("SELECT * FROM categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching categories: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        input, select, button {
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
        .category-list {
            margin: 10px 0;
        }
        .category-list label {
            display: block;
            margin: 5px 0;
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
        <h2>Sign Up</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="user_type" id="user_type" required onchange="toggleCategories()">
                <option value="">Select User Type</option>
                <option value="client" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                <option value="freelancer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'freelancer') ? 'selected' : ''; ?>>Freelancer</option>
            </select>
            <div id="categories" class="category-list" style="display: none;">
                <h3>Select Your Skills</h3>
                <?php if (empty($categories)): ?>
                    <p class="error">No categories available. Contact admin.</p>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <label>
                            <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="submit">Sign Up</button>
        </form>
    </div>
    <script>
        function toggleCategories() {
            const userType = document.getElementById('user_type').value;
            document.getElementById('categories').style.display = userType === 'freelancer' ? 'block' : 'none';
        }
        <?php if (isset($_POST['user_type']) && $_POST['user_type'] == 'freelancer'): ?>
            toggleCategories();
        <?php endif; ?>
    </script>
</body>
</html>
