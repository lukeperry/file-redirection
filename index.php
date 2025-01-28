<?php
// Start the session
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_file_redirection";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error messages
$loginError = "";
$signupError = "";
$signupSuccess = "";
$folderRedirectError = "";

// Handle signup logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $signupUsername = $_POST['signup_username'];
    $signupPassword = $_POST['signup_password'];
    $signupConfirmPassword = $_POST['signup_confirm_password'];

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $signupUsername);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $signupError = "Username already exists!";
    } else {
        if ($signupPassword === $signupConfirmPassword) {
            // Hash the password before storing it
            $hashedPassword = password_hash($signupPassword, PASSWORD_DEFAULT);

            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $signupUsername, $hashedPassword);

            // Execute the statement
            if ($stmt->execute()) {
                $signupSuccess = "User account has been created successfully!";
                echo "<script>document.getElementById('signup-form').reset();</script>";
            } else {
                $signupError = "Error: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            $signupError = "Passwords do not match!";
        }
    }
}

// Handle login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['logged_in'] = true;
            header("Location: dashboard.php");
            exit();
        } else {
            $loginError = "Invalid credentials!";
        }
    } else {
        $loginError = "Invalid credentials!";
    }

    // Close the statement
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Redirection</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">
        <div class="tab-content">
            <!-- Login Form -->
            <div id="login-form" class="form-container">
                <h2 class="text-2xl font-semibold mb-4 text-center">Login</h2>
                <?php if ($loginError): ?>
                    <div class="bg-red-200 text-red-800 p-2 mb-4 text-center rounded">
                        <?= $loginError ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium">Username</label>
                        <input type="text" id="username" name="username" class="mt-1 p-2 w-full border border-gray-300 rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium">Password</label>
                        <input type="password" id="password" name="password" class="mt-1 p-2 w-full border border-gray-300 rounded" required>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="submit" name="login" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Login</button>
                        <button type="button" onclick="toggleForm('signup-form')" class="text-blue-500 hover:text-blue-700">Don't have an account? Signup</button>
                    </div>
                </form>
            </div>

            <!-- Signup Form -->
            <div id="signup-form" class="hidden mt-8">
                <h2 class="text-2xl font-semibold mb-4 text-center">Signup</h2>
                <?php if ($signupError): ?>
                    <div class="bg-red-200 text-red-800 p-2 mb-4 text-center rounded">
                        <?= $signupError ?>
                    </div>
                <?php endif; ?>
                <?php if ($signupSuccess): ?>
                    <div class="bg-green-200 text-green-800 p-2 mb-4 text-center rounded">
                        <?= $signupSuccess ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="signup_username" class="block text-sm font-medium">Username</label>
                        <input type="text" id="signup_username" name="signup_username" class="mt-1 p-2 w-full border border-gray-300 rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="signup_password" class="block text-sm font-medium">Password</label>
                        <input type="password" id="signup_password" name="signup_password" class="mt-1 p-2 w-full border border-gray-300 rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="signup_confirm_password" class="block text-sm font-medium">Confirm Password</label>
                        <input type="password" id="signup_confirm_password" name="signup_confirm_password" class="mt-1 p-2 w-full border border-gray-300 rounded" required>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="submit" name="signup" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-green-600">Signup</button>
                        <button type="button" onclick="toggleForm('login-form')" class="text-blue-500 hover:text-blue-700">Already have an account? Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(formId) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            if (formId === 'signup-form') {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
            } else {
                signupForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
            }
        }
    </script>
</body>

</html>
