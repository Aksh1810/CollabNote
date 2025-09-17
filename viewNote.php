<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$topic_id = $_GET['topic_id'] ?? null;
$content = '';

try {
    $pdo = new PDO($attr, $db_user, $db_pwd, $options);

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM Topics t
        LEFT JOIN Access a ON t.topic_id = a.topic_id AND a.user_id = ? AND a.status = 1
        WHERE t.topic_id = ? AND (t.user_id = ? OR a.user_id IS NOT NULL)
    ");
    $stmt->execute([$_SESSION['user_id'], $topic_id, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        die("Access denied");
    }

    $stmt = $pdo->prepare("SELECT title, created_at FROM Topics WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();

    if (!$topic) {
        die("Topic not found.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = trim($_POST['content'] ?? '');

        if (empty($content)) {
            $error = "Note content cannot be empty.";
        } elseif (strlen($content) > 1500) {
            $error = "Note content cannot exceed 1500 characters.";
        } else {
            $insertQuery = "INSERT INTO Notes (topic_id, user_id, content, created_at)
                            VALUES (?, ?, ?, NOW())";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$topic_id, $_SESSION['user_id'], htmlspecialchars($content)]);
            header("Location: viewNote.php?topic_id=$topic_id");
            exit();
        }
    }

    $stmt = $pdo->prepare("SELECT n.content, n.created_at, u.screenname, u.avatar_url
                          FROM Notes n
                          JOIN Users u ON n.user_id = u.user_id
                          WHERE n.topic_id = ?
                          ORDER BY n.created_at ASC");
    $stmt->execute([$topic_id]);
    $notes = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Note</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/eventHandlers.js"></script>
</head>
<body id="view-note">
   <div class="container">
    <div class="header1">
        <div>
            <h1><?= htmlspecialchars($topic['title'] ?? 'Untitled Topic') ?></h1>
        </div>
        <div>
            <p>Created at: <?= date('d/m/Y H:i', strtotime($topic['created_at'])) ?></p>
            <p>Last edited: <?= !empty($notes) ? date('d/m/Y H:i', strtotime(end($notes)['created_at'])) : 'Never' ?></p>
        </div>
    </div>

    <div class="header2">
        <h2>Notes: </h2>
    </div>

    <div class="topics-container">
        <?php foreach ($notes as $note): ?>
        <div class="topic-card">
            <p class="metadata">
                <b><img src="<?= htmlspecialchars($note['avatar_url'] ?? 'images/default-avatar.png') ?>" 
         alt="Avatar" 
         style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;"> 
         <?= htmlspecialchars($note['screenname']) ?>
        </b> 
                @ <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?>
            </p>
            <div class="user-note">
                <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form class="add-note" method="post" id="noteForm">
    <div class="input-container">
        <textarea id="noteInput" name="content" 
                  placeholder="Add your notes.."
                  <?= !empty($error) ? 'class="input-error"' : '' ?>><?= htmlspecialchars($content) ?></textarea>
        <p id="char-count"><?= strlen($content) ?>/1500</p>
        <?php if (!empty($error)): ?>
            <div id="error-message" class="error-inside"><?= $error ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="submit-arrow-btn">➤</button>
</form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const noteInput = document.getElementById('noteInput');
    const charCount = document.getElementById('char-count');
    const errorMessage = document.getElementById('error-message');
    const noteForm = document.getElementById('noteForm');
    const topicId = <?= json_encode($topic_id) ?>;

document.getElementById('noteInput').addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault(); 
            document.getElementById('noteForm').submit(); 
        }
    });

    // AJAX submission
    noteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const content = noteInput.value.trim();
        if (content.length === 0 || content.length > 1500) {
            errorMessage.textContent = content.length === 0 ? 'Note content cannot be empty.' : 'Note cannot exceed 1500 characters.';
            errorMessage.style.display = 'block';
            return;
        }

        fetch('ajax_backend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add_note',
                topic_id: topicId,
                content: content
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                errorMessage.style.display = 'none';
                noteInput.value = '';
                charCount.textContent = '0/1500';

                // Append new notes to the container
                const container = document.querySelector('.topics-container');
                data.new_notes.forEach(note => {
                    const card = document.createElement('div');
                    card.className = 'topic-card';
                    card.innerHTML = `
                        <p class="metadata">
                            <b><img src="${note.avatar_url}" 
                                    alt="Avatar" 
                                    style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;"> 
                                ${note.screenname}</b> 
                            @ ${note.created_at}
                        </p>
                        <div class="user-note">
                            <p>${note.content}</p>
                        </div>`;
                    container.appendChild(card);
                });
            } else {
                errorMessage.textContent = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch(() => {
            errorMessage.textContent = 'Failed to submit note. Please try again.';
            errorMessage.style.display = 'block';
        });
    });
});
</script>
</body>
</html>