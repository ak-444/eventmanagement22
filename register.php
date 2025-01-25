<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Register</title>
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-color: #f8f9fa;
    }
    .login-container { 
      background-color: #ffffff; 
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px; 
    }
  </style>
</head>
<body>
  <div class="login-container"> 

    <h2 class="text-center mb-4">Register</h2>
    <form action="process_register.php" method="POST"  autocomplete="off">

      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" id="name" name="name" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email"  autocomplete="off" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password"  autocomplete="new-password" name="password" required>
      </div>

      <div class="mb-3">
        <label for="user_type" class="form-label">User Type</label>
        <select class="form-select" id="user_type" name="user_type" required>
          <option value="Student">Student</option>
          <option value="Event Organizer">Event Organizer</option>
          <option value="Faculty">Faculty</option>
        </select>
      </div>


      <button type="submit" class="btn btn-primary w-100">Register</button>

      
      <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success mt-4" role="alert">
        Registration successfully completed! <a href="login.php" class="btn btn-link">Proceed to Login</a>
    </div>
<?php endif; ?>

    </form>
  </div>
</body>
</html>
