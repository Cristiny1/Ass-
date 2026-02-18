<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: /login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'quizsystem');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $user_id");
    header('Location: usermanagement.php');
}

// Fetch all users
$result = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4CAF50; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .delete-btn { color: red; cursor: pointer; }
    </style>
</head>
<body>
    <h1>User Management</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td><a href="?delete=<?php echo $row['id']; ?>" class="delete-btn">Delete</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

<?php $conn->close(); ?>