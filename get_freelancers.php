<?php
// get_freelancers.php
header('Content-Type: application/json');
include 'db.php';

$job_id = $_GET['job_id'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN proposals p ON u.id = p.freelancer_id WHERE p.job_id = ? AND p.status = 'pending'");
    $stmt->execute([$job_id]);
    $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($freelancers);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
