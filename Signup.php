<?php
session_start();
require_once("db.php");

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$errors = array();
$email = "";
$screenname = "";
$password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = test_input($_POST["Email"]);
    $screenname = test_input($_POST["Screenname"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm-password"];

    $emailRegex = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    $screennameRegex = "/^[a-zA-Z0-9_]+$/";
    $passwordRegex = "/^.{8,}$/";

    if (!preg_match($emailRegex, $email)) {
        $errors["Email"] = "Invalid email format";
    }
    if (!preg_match($screennameRegex, $screenname)) {
        $errors["Screenname"] = "Only letters, numbers, and underscores are allowed";
    }
    if (!preg_match($passwordRegex, $password)) {
        $errors["password"] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirm_password) {
        $errors["confirm"] = "Passwords do not match";
    }

    try {
        $db = new PDO($attr, $db_user, $db_pwd, $options);
    } catch (PDOException $e) {
        $errors["Database Error"] = "Connection failed: " . $e->getMessage();
    }

    if (empty($errors)) {
        $query = "SELECT user_id FROM Users WHERE screenname = :screenname";
        $stmt = $db->prepare($query);
        $stmt->execute([':screenname' => $screenname]);
        if ($stmt->fetch()) {
            $errors["Screenname Taken"] = "Screenname is already taken";
        }
    }

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO Users (email, password, screenname, avatar_url) 
                  VALUES (:email, :password, :screenname, '')";
        $stmt = $db->prepare($query);
        if (!$stmt->execute([':email' => $email, ':password' => $hashed_password, ':screenname' => $screenname])) {
            $errors["Database Error"] = "Failed to insert user: " . implode(" ", $stmt->errorInfo());
        } else {
            $uid = $db->lastInsertId();
            $target_dir = "uploads/";
            $avatar_url = "images/default-avatar.png"; // Default avatar

            if (isset($_FILES["profile-picture"]) && $_FILES["profile-picture"]["error"] == 0) {
                $imageFileType = strtolower(pathinfo($_FILES["profile-picture"]["name"], PATHINFO_EXTENSION));
                $target_file = $target_dir . $uid . "." . $imageFileType;

                if ($_FILES["profile-picture"]["size"] > 1000000) {
                    $errors["profile-picture"] = "File is too large. Maximum 1MB allowed";
                } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
                    $errors["profile-picture"] = "Only JPG, JPEG, PNG, and GIF files are allowed";
                } else {
                    if (move_uploaded_file($_FILES["profile-picture"]["tmp_name"], $target_file)) {
                        $avatar_url = $target_file;
                    } else {
                        $errors["Server Error"] = "Failed to save uploaded file";
                    }
                }
            }

            // Always update avatar_url, either with the uploaded file or the default
            if (empty($errors)) {
                $query = "UPDATE Users SET avatar_url = :avatar_url WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':avatar_url' => $avatar_url, ':user_id' => $uid]);

                $_SESSION['user_id'] = $uid;
                $_SESSION['screenname'] = $screenname;
                $_SESSION['avatar_url'] = $avatar_url;

                header("Location: topiclist.php");
                exit();
            } else {
                // If there were errors after insertion (like file upload failed), we might want to delete the user or just continue
                // For now, let's keep the user but show the errors
            }
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $type => $message) {
            echo "$type: $message<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/styles.css" />
    <title>SignUp Page</title>
    <script src="js/eventHandlers.js"></script>
</head>

<body id="signup-page">
    <div class="container">
        <div class="profile-picture">
            <div class="pro-pic-container">
                <img id="img-preview" alt="Avatar" src="images/default.jpeg" />
            </div>
            <form action="Signup.php" method="post" enctype="multipart/form-data">
                <div class="pro-pic-btn" <?= isset($errors['profile-picture']) ? 'input-error' : '' ?>>
                    <button type="button" class="pro-pic-btn-remove">Remove</button>
                    <input type="file" id="uploadBtn" name="profile-picture" accept="image/*" />
                    <label for="uploadBtn">Choose</label>
                </div>
        </div>
        <div>
            <input type="email" id="Email2" name="Email" placeholder="Email"
                class="input-field <?= isset($errors['Email']) ? 'input-error' : '' ?>" value="<?= $email ?>" />
        </div>
        <div>
            <input type="text" id="Screenname" name="Screenname" placeholder="Screenname"
                class="input-field <?= isset($errors['Screenname']) ? 'input-error' : '' ?>"
                value="<?= $screenname ?>" />
        </div>
        <div>
            <input type="password" id="password" name="password" placeholder="Password"
                class="input-field <?= isset($errors['password']) ? 'input-error' : '' ?>" />
        </div>
        <div>
            <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password"
                class="input-field <?= isset($errors['confirm']) ? 'input-error' : '' ?>" />
        </div>
        <button class="signup-btn" type="submit">Sign Up</button>
        </form>
    </div>
    <script src="js/eventRegisterSignup.js"></script>
</body></html>