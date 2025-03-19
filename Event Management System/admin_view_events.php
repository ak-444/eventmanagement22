<?php
session_start();
require_once 'config.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$event_id = $_GET['id'] ?? null;
if (!$event_id || !is_numeric($event_id)) {
    header("Location: admin_Event_management.php");
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: admin_Event_management.php");
    exit();
}

// Fetch attendees
$attendees = [];
$stmt = $conn->prepare("SELECT u.username, u.school_id, u.department 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .event-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .attendee-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <nav class="navbar navbar-light">
            <div class="container-fluid d-flex justify-content-between">
                <span class="navbar-brand mb-0 h1">Event Details</span>
                <a href="admin_edit_events.php?id=<?= $event_id ?>" class="btn btn-warning">Edit Event</a>
            </div>
        </nav>

        <div class="container">
            <div class="event-header">
                <h2><?= htmlspecialchars($event['event_name']) ?></h2>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($event['event_date'])) ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($event['event_time'])) ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
                    </div>
                </div>
                <div class="mt-3">
                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($event['event_description'])) ?></p>
                </div>
            </div>

            <div class="attendee-table p-4">
                <h4>Attendees</h4>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendees as $attendee): ?>
                            <tr>
                                <td><?= htmlspecialchars($attendee['username']) ?></td>
                                <td><?= htmlspecialchars($attendee['school_id']) ?></td>
                                <td><?= htmlspecialchars($attendee['department']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>