<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_topic') {
        $topic = trim($_POST['topic'] ?? '');

        if (empty($topic)) {
            echo json_encode(['success' => false, 'message' => 'Topic cannot be empty.']);
            exit;
        }

        // Example: Save topic to DB (pseudo-code)
        // $db->insertTopic($topic);
        $newTopic = [
            'id' => 101, // example topic ID
            'title' => $topic,
            'created_at' => date("Y-m-d H:i")
        ];

        echo json_encode(['success' => true, 'topic' => $newTopic]);
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'add_note') {
    $user_id = $_SESSION['user_id'];
    $topic_id = $input['topic_id'] ?? null;
    $content = trim($input['content'] ?? '');

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Note content cannot be empty.']);
        exit;
    }

    if (strlen($content) > 1500) {
        echo json_encode(['success' => false, 'message' => 'Note cannot exceed 1500 characters.']);
        exit;
    }

    try {
        $pdo = new PDO($attr, $db_user, $db_pwd, $options);

        // Check access
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM Topics t
            LEFT JOIN Access a ON t.topic_id = a.topic_id AND a.user_id = ? AND a.status = 1
            WHERE t.topic_id = ? AND (t.user_id = ? OR a.user_id IS NOT NULL)
        ");
        $stmt->execute([$user_id, $topic_id, $user_id]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        // Insert the note
        $stmt = $pdo->prepare("INSERT INTO Notes (topic_id, user_id, content, created_at)
                               VALUES (?, ?, ?, NOW())");
        $stmt->execute([$topic_id, $user_id, htmlspecialchars($content)]);

        // Fetch newly added note(s)
        $stmt = $pdo->prepare("
            SELECT n.content, n.created_at, u.screenname, u.avatar_url
            FROM Notes n
            JOIN Users u ON n.user_id = u.user_id
            WHERE n.topic_id = ?
            ORDER BY n.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$topic_id]);
        $note = $stmt->fetch();

        $note['content'] = nl2br(htmlspecialchars($note['content']));
        $note['created_at'] = date('d/m/Y H:i', strtotime($note['created_at']));
        $note['avatar_url'] = htmlspecialchars($note['avatar_url'] ?? 'images/default-avatar.png');
        $note['screenname'] = htmlspecialchars($note['screenname']);

        echo json_encode(['success' => true, 'new_notes' => [$note]]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
