<?php

include('db.php'); // your database connection file
include('header.php'); // optional, if you're using a shared header

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Save user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Get user's IP address
            $ip_address = $_SERVER['REMOTE_ADDR'];

            // Log login
            $log_stmt = $conn->prepare("INSERT INTO login_logs (l_id,user_id, username, ip_address) VALUES ('',?, ?, ?)");
            $log_stmt->bind_param("iss", $user['id'], $user['username'], $ip_address);
            $log_stmt->execute();

            // Redirect after successful login
            header("Location: applicant_reports.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!-- HTML & CSS for the login form -->
<style>
    .form-container {
        flex-direction: column;
        gap: 10px;
        display: flex;
        align-items: center;
    }
    .container {
        max-width: 400px;
        margin: 50px auto;
        padding: 30px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        background-color: #f9f9f9;
    }
    h2 {
        text-align: center;
    }
    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .submit-btn {
        width: 100%;
        padding: 10px;
        margin-top: 15px;
        background-color: #007bff;
        border: none;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }
    .submit-btn:hover {
        background-color: #0056b3;
    }
    .error-message {
        color: red;
        text-align: center;
    }
</style>

<div class="container">
    <h2>LOGIN</h2>
    <form method="POST">
        <div class="form-container">
            <?php if (isset($error)): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="submit-btn">Login</button>
        </div>
    </form>
</div>
