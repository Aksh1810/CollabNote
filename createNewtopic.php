<?php
session_start();
require_once("db.php");

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = array();
$dataOK = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = test_input($_POST["topic"] ?? '');
    if (empty($topic)) {
        $errors["Topic Error"] = "Topic name is required.";
        $dataOK = false;
    } elseif (strlen($topic) > 255) { // Updated to match the database schema
        $errors["Topic Error"] = "Topic name must not exceed 255 characters.";
        $dataOK = false;
    }

    if ($dataOK) {
        try {
            $pdo = new PDO($attr, $db_user, $db_pwd, $options);

            // Debugging: Check values before query execution
            var_dump($topic, $_SESSION['user_id']);

            // Updated query to include last_edited
            $query = "INSERT INTO Topics (title, user_id, created_at, last_edited) VALUES (:title, :user_id, NOW(), NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':title', $topic, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                $pdo = null;
                header("Location: topiclist.php");
                exit();
            } else {
                $errors["Database Error"] = "Failed to create a new topic. Please try again.";
            }
        } catch (PDOException $e) {
            // Log the exact error for debugging
            error_log("Database error: " . $e->getMessage());
            $errors["Database Error"] = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Topic Page</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/eventHandlers.js"></script>
</head>

<body>
    <div class="container" id="topic-form">
        <h2>New Topic</h2>

        <!-- Display error message if validation fails -->
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Form to create a new topic -->
        <form action="createNewtopic.php" method="post" novalidate>
            <div>
                <input id="topic" name="topic" type="text" placeholder="Topic" class="input-field" maxlength="256" required>
                <div id="error-text-topic" class="error-text hidden">
                    Topic name required (max 256 characters).
                </div>
            </div>
            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>
    <script src="js/eventRegisterNewtopic.js"></script>
</body>

</html>