<?php
header('Content-Type: application/json');
include 'db.php';

$job_id = $_GET['job_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['user_type'] ?? '';

$users = [];
if ($user_type == 'client') {
    // Fetch freelancers with accepted proposals for the job
    $stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN proposals p ON u.id = p.freelancer_id WHERE p.job_id = ? AND p.status = 'accepted'");
    $stmt->execute([$job_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch the client who posted the job
    $stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN jobs j ON u.id = j.client_id WHERE j.id = ?");
    $stmt->execute([$job_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($users);
?>
