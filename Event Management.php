<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "event_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle event submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_time = $conn->real_escape_string($_POST['event_time']);
    
    $sql = "INSERT INTO events (event_name, event_date, event_time) VALUES ('$event_name', '$event_date', '$event_time')";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Event added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding event: " . $conn->error . "');</script>";
    }
}

// Handle event editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_event'])) {
    $event_id = $conn->real_escape_string($_POST['event_id']);
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_time = $conn->real_escape_string($_POST['event_time']);
    
    $sql = "UPDATE events SET event_name='$event_name', event_date='$event_date', event_time='$event_time' WHERE id='$event_id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Event updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating event: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <title>Event Management</title>
    <style>
        body {
            display: flex;
            background: #f4f4f4;
        }
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #293CB7, #1E2A78);
            padding-top: 20px;
            position: fixed;
            color: #ffffff;
            box-shadow: 4px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: #f0f0f0;
            transition: background 0.3s ease;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fff;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
            width: 100%;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-center">AU JAS</h4>
        <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
        <a href="Event Calendar.php"><i class="bi bi-calendar"></i> Event Calendar</a>
        <a href="Event Management.php"><i class="bi bi-gear"></i> Event Management</a>
        <a href="user_management.php"><i class="bi bi-people"></i> User Management</a>
        <a href="reports.php"><i class="bi bi-file-earmark-text"></i> Reports</a>
    </div>

    <div class="content">

        <nav class="navbar navbar-light">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1" id="headerTitle">Dashboard</span>
                
                <!-- User Info -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">User Type: <?php echo htmlspecialchars($_SESSION['user_type']); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="form-container">
            <h4>Add/Edit Event</h4>
            <form method="POST" action="">
                <input type="hidden" name="event_id" id="event_id">
                <div class="mb-3">
                    <label class="form-label">Event Name</label>
                    <input type="text" class="form-control" name="event_name" id="event_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Event Date</label>
                    <input type="date" class="form-control" name="event_date" id="event_date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Event Time</label>
                    <input type="time" class="form-control" name="event_time" id="event_time" required>
                </div>
                <button type="submit" name="add_event" class="btn btn-primary">Add Event</button>
                <button type="submit" name="edit_event" class="btn btn-warning">Edit Event</button>
            </form>
        </div>
    </div>
</body>
</html>
