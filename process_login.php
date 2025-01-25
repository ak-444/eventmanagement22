<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'event_management');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare and execute the SQL query to get the user's data
    $stmt = $conn->prepare("SELECT id, name, password, user_type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $user_name, $hashed_password, $user_type);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        // Correct password, set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_type'] = $user_type;  // Store the user type in the session

        // Redirect to dashboard after successful login
        header("Location: dashboard.php");
        exit();
    } else {
        // If login fails, show error message
        echo "Invalid login credentials.";
    }

    $stmt->close();
    $conn->close();
}
?>
