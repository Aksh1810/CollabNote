<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$topic_id = $_GET['topic_id'] ?? null;

if (!$topic_id) {
    die("Error: Topic ID is missing.");
}

try {
    $pdo = new PDO($attr, $db_user, $db_pwd, $options);

    $query = "SELECT title, user_id FROM Topics WHERE topic_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$topic) {
        die("Error: Topic not found.");
    }

    if ($topic['user_id'] != $_SESSION['user_id']) {
        die("Unauthorized: You do not own this topic.");
    }

    $topic_title = $topic['title'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submitted_users = $_POST['users'] ?? [];

        $query = "SELECT user_id, status FROM Access WHERE topic_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$topic_id]);
        $current_access = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($submitted_users as $user_id => $value) {
            if (!isset($current_access[$user_id])) {

                $query = "INSERT INTO Access (topic_id, user_id, status) VALUES (?, ?, 1)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$topic_id, $user_id]);
            } elseif ($current_access[$user_id] == 0) {

                $query = "UPDATE Access SET status = 1 WHERE topic_id = ? AND user_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$topic_id, $user_id]);
            }
        }

        foreach ($current_access as $user_id => $status) {
            if (!isset($submitted_users[$user_id]) && $status == 1) {
                $query = "UPDATE Access SET status = 0 WHERE topic_id = ? AND user_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$topic_id, $user_id]);
            }
        }
        header("Location: topiclist.php");
        exit();
    }

        $query = "SELECT u.user_id, u.screenname, u.avatar_url,
                     COALESCE(a.status, 0) AS access_status
              FROM Users u
              LEFT JOIN Access a ON u.user_id = a.user_id AND a.topic_id = ?
              WHERE u.user_id != ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$topic_id, $_SESSION['user_id']]);
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grant/Revoke Access Page</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body id="access">
    <div class="container">
        <div>
            <h2><?= htmlspecialchars($topic_title) ?></h2>
        </div>
        <hr width="100%" size="1" color="black">

        <form method="post">
            <div class="access-row">
                <?php foreach ($users as $user): ?>
                <div class="users">
                        <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                         alt="Avatar" 
                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;">
                    <label>
                         <?= htmlspecialchars($user['screenname']) ?>
                        <input type="checkbox" 
                               name="users[<?= $user['user_id'] ?>]"
                               <?= $user['access_status'] ? 'checked' : '' ?>>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="save-chng-btn" type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>