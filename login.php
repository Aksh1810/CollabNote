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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = array();
    $dataOK = TRUE;

    $email = test_input($_POST["Email"]);
    $emailRegex = "/^[\w\.-]+@[\w\.-]+\.\w+$/";
    if (!preg_match($emailRegex, $email)) {
        $errors["email"] = "Invalid Email";
        $dataOK = FALSE;
    }

    $password = test_input($_POST["password"]);
    if (empty($password)) {
        $errors["password"] = "Password is required";
        $dataOK = FALSE;
    }

    if ($dataOK) {
        try {
            $db = new PDO($attr, $db_user, $db_pwd, $options);
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }

        $query = "SELECT user_id, screenname, password, avatar_url FROM Users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['screenname'] = $user['screenname'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
            $db = null;
            header("Location: topiclist.php");
            exit();
        } else {
            $errors["Login Failed"] = "Invalid email or password.";
        }

        $db = null;
    } else {
        $errors['Login Failed'] = "You entered invalid data while logging in.";
    }

    if (!empty($errors)) {
        foreach ($errors as $type => $message) {
            echo "$type: $message <br />\n";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page</title>
    <link rel="stylesheet" href="css/styles.css" />
    <script src="js/eventHandlers.js"></script>
</head>

<body id="login-page">
    <div class="container">
        <div class="topic-header">
            <p>Login</p>
        </div>
        <form id="login-form" action="" method="post">
            <div>
                <div>
                    <input type="email" id="Email" name="Email" placeholder="Email" class="input-field" />
                    <div id="error-text-email" class="error-text hidden">Email is invalid</div>
                </div>
                <div>
                    <input type="password" id="password" name="password" placeholder="Password" class="input-field" />
                    <div id="error-text-password" class="error-text hidden">At least 6 character, no spaces</div>
                </div>
            </div>
            <div>
                <p><a href="Signup.php">Create a new account</a></p>
            </div>
            <div>
                <button type="submit" class="login-btn">Login</button>
            </div>
        </form>
    </div>
    <script src="js/eventRegisterLogin.js"></script>
</body>

</html>