<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$screenname = $_SESSION['screenname'] ?? 'User';

// Ensure avatar URL is valid or fallback to default
$avatarPath = !empty($_SESSION['avatar_url']) && file_exists($_SESSION['avatar_url'])
    ? $_SESSION['avatar_url']
    : 'images/default.jpeg';

try {
    $pdo = new PDO($attr, $db_user, $db_pwd, $options);

    $ownQuery = "SELECT t.topic_id, t.title, t.created_at,
                MAX(n.created_at) AS last_edited,
                COUNT(n.note_id) AS total_notes
                FROM Topics t
                LEFT JOIN Notes n ON t.topic_id = n.topic_id
                WHERE t.user_id = ?
                GROUP BY t.topic_id
                ORDER BY t.created_at DESC";

    $ownStmt = $pdo->prepare($ownQuery);
    $ownStmt->execute([$_SESSION['user_id']]);
    $ownTopics = $ownStmt->fetchAll();

    $sharedQuery = "SELECT t.topic_id, t.title, t.created_at,
                   MAX(n.created_at) AS last_edited,
                   COUNT(n.note_id) AS total_notes
                   FROM Topics t
                   JOIN Access a ON t.topic_id = a.topic_id
                   LEFT JOIN Notes n ON t.topic_id = n.topic_id
                   WHERE a.user_id = ? AND a.status = 1 AND t.user_id != ?
                   GROUP BY t.topic_id
                   ORDER BY t.created_at DESC";

    $sharedStmt = $pdo->prepare($sharedQuery);
    $sharedStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $sharedTopics = $sharedStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic List Page</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body id="topic-list">
    <div class="container">
        <div class="header">
            <img src="images/notes.png" />
            <div>
                <h1>Welcome, <?= htmlspecialchars($screenname) ?></h1>
            </div>
            <div class="user-info">
                <div>
                    <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar"
                        style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;">
                </div>
                <div class="vertical-line"></div>
                <div>
                    <form action="logout.php" method="post">
                        <button class="logout-button" type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="section-title">
            <p>My Topics:</p>
        </div>
        <div class="topics-container">
            <?php foreach ($ownTopics as $topic): ?>
                <div class="topic-card">
                    <div class="topic-title">
                        <h3><?= htmlspecialchars($topic['title']) ?></h3>
                    </div>
                    <div>
                        <div class="topic-card-info">
                            <p>Created: <?= date('d/m/Y H:i', strtotime($topic['created_at'])) ?></p>
                            <p>Last Edited:
                                <?= $topic['last_edited'] ? date('d/m/Y H:i', strtotime($topic['last_edited'])) : 'Never' ?>
                            </p>
                            <p>Total Notes: <?= $topic['total_notes'] ?></p>
                        </div>
                        <div class="topic-card-btn">
                            <form action="viewNote.php" method="get">
                                <input type="hidden" name="topic_id" value="<?= $topic['topic_id'] ?>">
                                <button class="view-btn" type="submit">View</button>
                            </form>
                            <form action="Access.php" method="get">
                                <input type="hidden" name="topic_id" value="<?= $topic['topic_id'] ?>">
                                <button class="access-btn" type="submit">Manage Access</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-title">
            <p>Shared with me:</p>
        </div>
        <div class="topics-container">
            <?php foreach ($sharedTopics as $topic): ?>
                <div class="topic-card">
                    <div class="topic-title">
                        <h3><?= htmlspecialchars($topic['title']) ?></h3>
                    </div>
                    <div>
                        <div class="topic-card-info">
                            <p>Created: <?= date('d/m/Y H:i', strtotime($topic['created_at'])) ?></p>
                            <p>Last Edited:
                                <?= $topic['last_edited'] ? date('d/m/Y H:i', strtotime($topic['last_edited'])) : 'Never' ?>
                            </p>
                            <p>Total Notes: <?= $topic['total_notes'] ?></p>
                        </div>
                        <div class="topic-card-btn">
                            <form action="viewNote.php" method="get">
                                <input type="hidden" name="topic_id" value="<?= $topic['topic_id'] ?>">
                                <button class="view-btn" type="submit">View</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form action="createNewtopic.php" method="get">
            <button class="new-topic-btn">+ New Topic</button>
        </form>
    </div>
</body>

</html>