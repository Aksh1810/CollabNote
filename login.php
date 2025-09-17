<?php
session_start();
require_once("db.php");

function test_input($data) {
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
    $passwordRegex = "/^[^\s]{6,}$/";
    if (!preg_match($passwordRegex, $password)) {
        $errors["password"] = "Invalid Password";
        $dataOK = FALSE;
    }

    if ($dataOK) {
        try {
            $db = new PDO($attr, $db_user, $db_pwd, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }

        $query = "SELECT user_id, screenname FROM Users WHERE email='$email' AND password='$password'";
        $result = $db->query($query);

        if (!$result) {
            $errors["Database Error"] = "Could not retrieve user information";
        } elseif ($row = $result->fetch()) {
            session_start();
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['screenname'] = $row['screenname'];
            $db = null;
            header("Location: topiclist.php");
            exit();
        } else {
            $errors["Login Failed"] = "That username/password combination does not exist.";
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