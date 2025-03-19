<?php
session_start();
require_once 'config.php';

// Validate and get event ID
$event_id = $_GET['id'] ?? null;
if (!$event_id || !is_numeric($event_id)) {
    header("Location: admin_Event_management.php");
    exit();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $event_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: admin_Event_management.php");
    exit();
}

// Fetch all users with correct column names
$users = [];
$result = $conn->query("SELECT id, username, school_id, department FROM users WHERE status = 'approved'");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch current attendees with correct column names
$attendees = [];
$stmt = $conn->prepare("SELECT u.id, u.username, u.school_id, u.department 
                       FROM event_attendees ea
                       JOIN users u ON ea.user_id = u.id
                       WHERE ea.event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $attendees = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update event details
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = $_POST['venue'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE events 
                           SET event_name=?, event_date=?, event_time=?, venue=?, event_description=?
                           WHERE id=?");
    $stmt->bind_param("sssssi", $event_name, $event_date, $event_time, $venue, $description, $event_id);
    $stmt->execute();
    $stmt->close();

    // Handle attendees
    if (isset($_POST['attendees'])) {
        $selected_users = $_POST['attendees'];
        
        // Remove existing attendees securely
        $del_stmt = $conn->prepare("DELETE FROM event_attendees WHERE event_id = ?");
        $del_stmt->bind_param("i", $event_id);
        $del_stmt->execute();
        $del_stmt->close();
        
        // Add new attendees
        $stmt = $conn->prepare("INSERT INTO event_attendees (event_id, user_id) VALUES (?, ?)");
        foreach ($selected_users as $user_id) {
            $stmt->bind_param("ii", $event_id, $user_id);
            $stmt->execute();
        }
        $stmt->close();
    }

    header("Location: admin_view_events.php?id=" . $event_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin: 40px auto; }
        .form-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .attendee-list { margin-top: 20px; }
        .attendee-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <nav class="navbar navbar-light">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">Edit Event</span>
            </div>
        </nav>

        <div class="container">
            <div class="form-section">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" class="form-control" name="event_name" 
                               value="<?= htmlspecialchars($event['event_name']) ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Event Date</label>
                            <input type="date" class="form-control" name="event_date" 
                                   value="<?= htmlspecialchars($event['event_date']) ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Event Time</label>
                            <input type="time" class="form-control" name="event_time" 
                                   value="<?= htmlspecialchars($event['event_time']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Venue</label>
                        <input type="text" class="form-control" name="venue" 
                               value="<?= htmlspecialchars($event['venue']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Event Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= 
                            htmlspecialchars($event['event_description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Add Attendees</label>
                        <div class="input-group">
                            <select class="form-select" id="userSelect">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?> (<?= $user['school_id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-primary" onclick="addAttendee()">
                                Add from Users
                            </button>
                        </div>
                    </div>

                    <div class="attendee-list">
                        <h5>Current Attendees</h5>
                        <div id="attendeesContainer">
                            <?php foreach ($attendees as $attendee): ?>
                                <div class="attendee-item">
                                    <span>
                                        <?= htmlspecialchars($attendee['username']) ?> 
                                        (<?= $attendee['school_id'] ?> - <?= $attendee['department'] ?>)
                                    </span>
                                    <input type="hidden" name="attendees[]" value="<?= $attendee['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeAttendee(this)">
                                        Remove
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addAttendee() {
            const select = document.getElementById('userSelect');
            const user = select.options[select.selectedIndex];
            
            const container = document.getElementById('attendeesContainer');
            
            const div = document.createElement('div');
            div.className = 'attendee-item';
            div.innerHTML = `
                <span>${user.textContent}</span>
                <input type="hidden" name="attendees[]" value="${user.value}">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeAttendee(this)">
                    Remove
                </button>
            `;
            
            container.appendChild(div);
        }

        function removeAttendee(button) {
            button.closest('.attendee-item').remove();
        }
    </script>
</body>
</html>