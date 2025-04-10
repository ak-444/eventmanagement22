<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch all events for the dropdown
$events_sql = "SELECT id, event_name FROM events ORDER BY event_name ASC";
$events_result = $conn->query($events_sql);
$events = [];

if ($events_result && $events_result->num_rows > 0) {
    while ($event_row = $events_result->fetch_assoc()) {
        $events[] = $event_row;
    }
}

// Get data for the selected event
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$questionnaires = [];
$event_name = '';
$questions = [];

if ($selected_event_id > 0) {
    // Get questions for the selected event
    $sql = "SELECT q.id, q.question_text, q.question_type 
            FROM questions q 
            JOIN questionnaires qn ON q.questionnaire_id = qn.id 
            WHERE qn.event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();

    // Get event name
    $event_sql = "SELECT event_name FROM events WHERE id = ?";
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("i", $selected_event_id);
    $stmt->execute();
    $event_result = $stmt->get_result();
    if ($event_result && $event_result->num_rows > 0) {
        $event_row = $event_result->fetch_assoc();
        $event_name = $event_row['event_name'];
    }
    $stmt->close();

    // Get questionnaires for the selected event
    $sql = "SELECT q.id, q.title, q.description, q.created_at, 
           (SELECT COUNT(*) FROM questions WHERE questionnaire_id = q.id) AS question_count
           FROM questionnaires q
           WHERE q.event_id = ?
           ORDER BY q.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $questionnaires[] = $row;
        }
    }
    $stmt->close();
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Event Reports</title>
    <style>
        :root {
            --primary-color: #293CB7;
            --secondary-color: #1E2A78;
            --accent-color: #4CC9F0;
            --background-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --text-secondary: #64748b;
            --text-tertiary: #94a3b8;
            --border-color: #e2e8f0;
            --border-radius-sm: 6px;
            --border-radius: 10px;
            --border-radius-lg: 16px;
            --box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --box-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s ease;
        }

        body {
            display: flex;
            background: #f4f4f4;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #293CB7, #1E2A78);
            padding-top: 20px;
            position: fixed;
            color: #ffffff;
            box-shadow: 4px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar h4 {
            text-align: center;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            color: #f0f0f0;
            font-size: 16px;
            transition: background 0.3s ease, border-left 0.3s ease;
        }
        .sidebar a i {
            margin-right: 10px;
            font-size: 18px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 5px solid #fff;
        }
        /* Main Content */
        .content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
            min-height: 100vh;
        }

        /* Navbar - Updated to match other pages */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border-radius: 0;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-sm);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--box-shadow);
        }
        
        .card-header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            margin-bottom: 0;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }
        
        /* Form Controls */
        .form-control {
            border-radius: var(--border-radius-sm);
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            box-shadow: none;
            height: 42px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.1);
        }
        
        /* Button Styles */
        .btn {
            border-radius: var(--border-radius-sm);
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            margin-right: 8px;
        }

        /* Event Header */
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }
        
        /* Report Styles */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .report-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .report-header h4 {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .report-date {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            font-style: italic;
            text-align: right;
            margin-bottom: 30px;
            color: var(--text-tertiary);
            font-size: 0.9rem;
        }
        
        /* Summary Cards */
        .event-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            flex: 1;
            min-width: 200px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow-sm);
            text-align: center;
            border-top: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .summary-card:nth-child(2) {
            border-top-color: var(--accent-color);
        }

        .summary-card:nth-child(3) {
            border-top-color: #4ade80; /* Success color */
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .summary-card .icon {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .summary-card:nth-child(2) .icon {
            color: var(--accent-color);
        }

        .summary-card:nth-child(3) .icon {
            color: #4ade80; /* Success color */
        }
        
        .summary-card h5 {
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 10px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0;
        }
        
        /* Table Styles */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            border-top: none;
            padding: 16px;
            color: var(--text-secondary);
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 16px;
            vertical-align: middle;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.01);
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            border-radius: 30px;
        }
        
        /* Response Question Styles */
        .response-question {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow-sm);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .response-question:hover {
            box-shadow: var(--box-shadow);
        }
        
        .response-question h5 {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .response-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .response-text {
            background-color: #f8f9fa;
            border-radius: var(--border-radius-sm);
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .response-text:hover {
            border-color: var(--accent-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .response-user {
            font-weight: 600;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .response-user i {
            font-size: 1rem;
        }

        .response-user small {
            color: var(--text-tertiary);
            font-weight: normal;
            margin-left: 5px;
        }
        
        .response-content {
            margin-top: 8px;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Alert Styles */
        .alert {
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid transparent;
        }

        .alert-info {
            background-color: rgba(76, 201, 240, 0.1);
            border-left-color: var(--accent-color);
            color: #0369a1;
        }

        .alert-warning {
            background-color: rgba(251, 191, 36, 0.1);
            border-left-color: #f59e0b;
            color: #b45309;
        }
        
        /* Print Area */
        #printArea {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-sm);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .content {
                margin-left: 260px;
                width: calc(100% - 260px);
            }
            .event-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .event-summary {
                flex-direction: column;
            }
            .summary-card {
                min-width: 100%;
            }
            .table-responsive {
                width: 100%;
                overflow-x: auto;
            }
        }
        
        @media print {
            .sidebar, .navbar, .event-header, .no-print {
                display: none !important;
            }
            .content {
                margin-left: 0;
                padding: 0;
                width: 100%;
            }
            #printArea {
                box-shadow: none;
                padding: 0;
            }
            body {
                background: white;
            }
            .collapse-body {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <!-- Updated Navbar to match other pages -->
        <nav class="navbar navbar-light">
            <div class="container-fluid d-flex justify-content-between">
                <span class="navbar-brand mb-0 h1">Event Reports</span>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">User Type: <?php echo htmlspecialchars($_SESSION['user_type']); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="card mb-4 fade-in">
            <div class="card-body">
                <div class="event-header">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <form method="get" action="admin_reports.php" class="d-flex align-items-center gap-2">
                            <select class="form-control" name="event_id" id="event_id" style="min-width: 250px;">
                                <option value="0">-- Select Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>" <?= ($selected_event_id == $event['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['event_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary" style="width: 180px;">
                                <i class="bi bi-search"></i> Load Report
                            </button>
                        </form>
                    </div>
                    <div class="button-group">
                        <button class="btn btn-outline-primary" onclick="printReport()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="printArea" class="fade-in">
            <?php if ($selected_event_id > 0): ?>
                <div class="report-header">
                    <h2>Event Report</h2>
                    <h4><?= htmlspecialchars($event_name) ?></h4>
                </div>
                
                <div class="report-date">
                    <i class="bi bi-calendar3"></i> Report generated on: <?= date('F j, Y, g:i a') ?>
                </div>
                
                <?php if (!empty($questionnaires)): ?>
            <!-- Summary cards and questionnaires table -->
            <div class="event-summary">
                        <?php
                            $total_questions = 0;
                            foreach ($questionnaires as $q) {
                                $total_questions += $q['question_count'];
                            }
                            
                            // Count total evaluations for this event
                            $eval_count_sql = "SELECT COUNT(DISTINCT user_id) as total_users FROM answers 
                                              WHERE question_id IN (
                                                  SELECT q.id FROM questions q
                                                  JOIN questionnaires qn ON q.questionnaire_id = qn.id
                                                  WHERE qn.event_id = ?
                                              )";
                            $stmt = $conn->prepare($eval_count_sql);
                            $stmt->bind_param("i", $selected_event_id);
                            $stmt->execute();
                            $eval_result = $stmt->get_result();
                            $eval_count = $eval_result->fetch_assoc();
                            $stmt->close();
                        ?>
                        <div class="summary-card">
                            <div class="icon"><i class="bi bi-clipboard-check"></i></div>
                            <h5>Total Questionnaires</h5>
                            <p><?= count($questionnaires) ?></p>
                        </div>
                        <div class="summary-card">
                            <div class="icon"><i class="bi bi-question-circle"></i></div>
                            <h5>Total Questions</h5>
                            <p><?= $total_questions ?></p>
                        </div>
                        <div class="summary-card">
                            <div class="icon"><i class="bi bi-people"></i></div>
                            <h5>Total Participants</h5>
                            <p><?= $eval_count['total_users'] ?? 0 ?></p>
                        </div>
                </div>
                    
                    <?php if (!empty($questions)) : ?>
                <!-- Questions and responses section -->
                <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-chat-dots"></i> Evaluation Responses</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($questions as $index => $question) : ?>
                                    <div class="response-question">
                                        <h5><?= $index + 1 ?>. <?= htmlspecialchars($question['question_text']) ?></h5>
                                        <div class="response-meta">
                                            <span class="badge bg-<?= $question['question_type'] === 'likert' ? 'primary' : 'success' ?>">
                                                <?= ucfirst($question['question_type']) ?>
                                            </span>
                                        </div>
                    
                                        <?php
                                        // Fetch responses for this question
                                        $response_sql = "SELECT a.answer_text, u.username, a.submitted_at 
                                                        FROM answers a
                                                        JOIN users u ON a.user_id = u.id
                                                        WHERE a.question_id = ?";
                                        $stmt = $conn->prepare($response_sql);
                                        $stmt->bind_param("i", $question['id']);
                                        $stmt->execute();
                                        $responses = $stmt->get_result();
                                        ?>
                    
                                        <?php if ($question['question_type'] === 'likert') : ?>
                                            <!-- Likert Statistics -->
                                            <?php
                                            $likert_values = [];
                                            while ($row = $responses->fetch_assoc()) {
                                                $likert_values[] = (int)$row['answer_text'];
                                            }
                                            $total_responses = count($likert_values);
                                            
                                            // Define Likert scale labels
                                            $likert_labels = [
                                                1 => 'Strongly Disagree',
                                                2 => 'Disagree',
                                                3 => 'Neutral',
                                                4 => 'Agree',
                                                5 => 'Strongly Agree'
                                            ];
                                            ?>
                                            
                                            <?php if ($total_responses > 0) : ?>
                                                <div class="likert-stats mt-3">
                                                    <p class="text-muted">Total Responses: <?= $total_responses ?></p>
                                                    <div class="distribution">
                                                        <?php
                                                        $distribution = array_count_values($likert_values);
                                                        for ($i = 1; $i <= 5; $i++) :
                                                            $count = $distribution[$i] ?? 0;
                                                            $percent = ($count / $total_responses) * 100;
                                                        ?>
                                                            <div class="distribution-row mb-2">
                                                                <span class="text-sm"><?= $likert_labels[$i] ?> (<?= $count ?>)</span>
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%">
                                                                        <?= number_format($percent, 1) ?>%
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            <?php else : ?>
                                                <div class="alert alert-info">No responses for this question.</div>
                                            <?php endif; ?>
                    
                                        <?php else : ?>
                                            <!-- Text Responses -->
                                            <div class="text-responses mt-3">
                                                <?php if ($responses->num_rows > 0) : ?>
                                                    <?php while ($response = $responses->fetch_assoc()) : ?>
                                                        <div class="response-text">
                                                            <div class="response-user">
                                                                <i class="bi bi-person"></i>
                                                                <?= htmlspecialchars($response['username']) ?>
                                                                <small class="text-muted">
                                                                    <?= date('M j, Y g:i a', strtotime($response['submitted_at'])) ?>
                                                                </small>
                                                            </div>
                                                            <div class="response-content">
                                                                <?= nl2br(htmlspecialchars($response['answer_text'])) ?>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                <?php else : ?>
                                                    <div class="alert alert-info">No responses for this question.</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                    
                                        <?php $stmt->close(); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
            <?php else : ?>
                <div class="alert alert-warning">No questions found for this event.</div>
            <?php endif; ?>
  <!-- Completed Evaluations Section (Moved Outside Questions Check) -->
  <?php
        $total_questions = count($questions);
        if ($total_questions > 0) {
            // Fetch and display users who completed all questions
            $user_sql = "SELECT u.id, u.username, COUNT(a.id) as answered 
                        FROM users u
                        JOIN answers a ON u.id = a.user_id
                        WHERE a.question_id IN (
                            SELECT q.id FROM questions q
                            JOIN questionnaires qn ON q.questionnaire_id = qn.id
                            WHERE qn.event_id = ?
                        )
                        GROUP BY u.id
                        HAVING answered = ?";
            
            $stmt = $conn->prepare($user_sql);
            $stmt->bind_param("ii", $selected_event_id, $total_questions);
            $stmt->execute();
            $users_result = $stmt->get_result();
            
            if ($users_result->num_rows > 0) {
                echo '<div class="card mt-4">';
                echo '<div class="card-header">';
                echo '<h5 class="card-title"><i class="bi bi-check-circle"></i> Completed Evaluations</h5>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<tr><th>#</th><th>Participant</th><th>Completed At</th></tr>';
                echo '</thead><tbody>';
                
                $counter = 1;
                while ($user = $users_result->fetch_assoc()) {
                    // Get completion timestamp
                    $time_sql = "SELECT MAX(submitted_at) as last_submit FROM answers 
                                WHERE user_id = ? AND question_id IN (
                                    SELECT q.id FROM questions q
                                    JOIN questionnaires qn ON q.questionnaire_id = qn.id
                                    WHERE qn.event_id = ?
                                )";
                    $stmt2 = $conn->prepare($time_sql);
                    $stmt2->bind_param("ii", $user['id'], $selected_event_id);
                    $stmt2->execute();
                    $time_result = $stmt2->get_result();
                    $last_submit = $time_result->fetch_assoc()['last_submit'];
                    
                    echo '<tr>';
                    echo '<td>'.$counter++.'</td>';
                    echo '<td>'.htmlspecialchars($user['username']).'</td>';
                    echo '<td>'.date('M j, Y g:i a', strtotime($last_submit)).'</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                echo '</div></div></div>';
            }
            $stmt->close();
        }
        ?>
    <?php else: ?>
        <div class="alert alert-warning">No questionnaires found for the selected event.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info">Please select an event to view its report.</div>
<?php endif; ?>