<?php
session_start();
require_once __DIR__ . '../../../../config/database.php';

// -----------------------
// LOGOUT HANDLING
// -----------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// -----------------------
// LOGIN CHECK
// -----------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] == 'admin';
$currentPage = $_GET['page'] == 'dashboard';

// -----------------------
// PAGE TITLE
// -----------------------
$dashboardTitle = $userRole === 'admin' ? 'Admin Panel' : 'Teacher Dashboard';

// -----------------------
// ALLOWED PAGES
// -----------------------
$allowedPages = ['dashboard','quizzes','create_quiz','myquizzes','students','results','settings','users','departments','reports'];
if (!in_array($currentPage, $allowedPages)) $currentPage = 'dashboard';

// -----------------------
// FILTER DATA
// -----------------------
$categories = ['All Categories','Programming','Security','Database','Web Development'];
$difficultyLevels = ['All Levels','Beginner','Intermediate','Advanced'];
$statusTypes = ['All Status','Active','Draft','Archived'];

// -----------------------
// FETCH STATISTICS
// -----------------------
try {
    // Get user statistics
    if ($userRole === 'admin') {
        // Admin stats
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
        $totalQuizzes = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes WHERE status = 'draft'");
        $pendingReviews = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
        $totalDepartments = $stmt->fetch()['total'];
    } else {
        // Teacher stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quizzes WHERE creator_id = ?");
        $stmt->execute([$userId]);
        $myQuizzes = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attempts a JOIN quizzes q ON a.quiz_id = q.id WHERE q.creator_id = ?");
        $stmt->execute([$userId]);
        $totalAttempts = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT AVG(score) as avg FROM attempts a JOIN quizzes q ON a.quiz_id = q.id WHERE q.creator_id = ?");
        $stmt->execute([$userId]);
        $avgScore = round($stmt->fetch()['avg'] ?? 0, 1);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quizzes WHERE creator_id = ? AND status = 'draft'");
        $stmt->execute([$userId]);
        $pendingTeacherReviews = $stmt->fetch()['total'];
    }
    
    // Weekly trends
    if ($userRole === 'teacher') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quizzes WHERE creator_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$userId]);
        $weeklyQuizzes = $stmt->fetch()['total'];
    }
    
} catch (Exception $e) {
    error_log("Stats fetch failed: " . $e->getMessage());
}

// -----------------------
// FETCH QUIZZES WITH ALL NEEDED DATA
// -----------------------
try {
    $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    if ($userRole === 'teacher' && ($currentPage === 'myquizzes' || $currentPage === 'dashboard')) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM quizzes WHERE creator_id = ?");
        $countStmt->execute([$userId]);
        $totalRecords = $countStmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT 
            q.*, 
            u.username AS creator,
            (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count,
            (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id) as attempts_count,
            (SELECT COALESCE(AVG(score), 0) FROM attempts WHERE quiz_id = q.id) as average_score
            FROM quizzes q 
            JOIN users u ON q.creator_id = u.id
            WHERE q.creator_id = :uid
            ORDER BY q.created_at DESC
            LIMIT :limit OFFSET :offset");
        
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
        $totalRecords = $countStmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT 
            q.*, 
            u.username AS creator,
            (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count,
            (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id) as attempts_count,
            (SELECT COALESCE(AVG(score), 0) FROM attempts WHERE quiz_id = q.id) as average_score
            FROM quizzes q 
            JOIN users u ON q.creator_id = u.id
            ORDER BY q.created_at DESC
            LIMIT :limit OFFSET :offset");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $allQuizzes = $stmt->fetchAll();
    $totalPages = ceil($totalRecords / $limit);
    
} catch (Exception $e) {
    $allQuizzes = [];
    $totalPages = 0;
    error_log("Failed to fetch quizzes: " . $e->getMessage());
}

// -----------------------
// FETCH CATEGORY STATISTICS
// -----------------------
try {
    $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM quizzes GROUP BY category");
    $categoryStats = $catStmt->fetchAll();
    $totalQuizCount = array_sum(array_column($categoryStats, 'count'));
} catch (Exception $e) {
    $categoryStats = [];
    $totalQuizCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $dashboardTitle ?> - <?= ucfirst($currentPage) ?></title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DataTables for advanced table features -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<style>
body {
    background-color: #f5f5f5;
}

/* Sidebar */
.sidebar {
    background: linear-gradient(180deg, #2c3e50 0%, #1a2634 100%);
    min-height: 100vh;
    width: 260px;
    position: fixed;
    top: 0;
    left: 0;
    transition: transform 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 10px;
}

.sidebar-header h5 {
    color: #ecf0f1;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
}

.sidebar-header small {
    color: #bdc3c7;
    font-size: 0.8rem;
}

.sidebar a {
    color: #bdc3c7;
    padding: 15px 25px;
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.sidebar a i {
    width: 24px;
    font-size: 1.1rem;
    margin-right: 12px;
}

.sidebar a:hover {
    background-color: #34495e;
    color: #fff;
    border-left-color: #3498db;
}

.sidebar a.active {
    background-color: #34495e;
    color: #fff;
    border-left-color: #3498db;
}

/* Main Content */
.main-content {
    margin-left: 260px;
    padding: 25px;
    margin-top: 70px;
}

/* Mobile */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        margin-top: 60px;
    }
}

/* Top Navbar */
.navbar {
    margin-left: 260px;
    padding: 15px 25px;
    background: white !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

@media (max-width: 768px) {
    .navbar {
        margin-left: 0;
    }
}

.menu-btn {
    font-size: 1.5rem;
    cursor: pointer;
    color: #2c3e50;
}

/* Stats Cards */
.stat-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card .card-body {
    padding: 20px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Activity Card */
.activity-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.activity-card .card-header {
    background: white;
    border-bottom: 1px solid #eef2f7;
    padding: 20px;
    font-weight: 600;
    border-radius: 15px 15px 0 0 !important;
}

/* Welcome Section */
.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.welcome-section h1 {
    font-weight: 600;
    margin-bottom: 10px;
}

.welcome-section p {
    opacity: 0.9;
    margin: 0;
}

/* Table Styles */
.table-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.table thead th {
    border-top: none;
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: background-color 0.3s;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    padding: 15px 10px;
}

/* Quiz Cards (for dashboard view) */
.quiz-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
    position: relative;
}

.quiz-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.quiz-card .card-body {
    padding: 20px;
}

.quiz-badge {
    position: absolute;
    top: 15px;
    right: 15px;
}

.quiz-meta {
    font-size: 0.9rem;
    color: #6c757d;
}

.quiz-meta i {
    width: 18px;
    margin-right: 5px;
}

/* Logout button special style */
.sidebar a:last-child {
    margin-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.sidebar a:last-child:hover {
    background-color: #c0392b;
    color: white;
}

/* Section title */
.section-title {
    margin-bottom: 20px;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    color: #3498db;
}

/* Progress bar */
.progress-sm {
    height: 5px;
}

/* Status badges */
.badge {
    padding: 8px 12px;
    font-weight: 500;
    font-size: 0.8rem;
}

/* Filter bar */
.filter-bar {
    background: white;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.filter-select {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 8px 15px;
    font-size: 0.9rem;
    color: #495057;
    background-color: white;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

/* Action buttons */
.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s;
    margin: 0 3px;
}

.action-btn:hover {
    transform: translateY(-2px);
}

/* Table image/avatar */
.table-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Pagination */
.pagination {
    margin-top: 20px;
}

.page-link {
    border: none;
    padding: 8px 15px;
    margin: 0 3px;
    border-radius: 8px;
    color: #2c3e50;
}

.page-link:hover {
    background-color: #f8f9fa;
    color: #3498db;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Search box */
.search-box {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 10px 15px;
    width: 250px;
    font-size: 0.9rem;
}

.search-box:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

/* Responsive table */
@media (max-width: 768px) {
    .table-container {
        padding: 10px;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-wrap: wrap;
    }
    
    .search-box {
        width: 100%;
    }
}
</style>
</head>

<body>

<!-- Top Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid">
        <span class="menu-btn d-md-none" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </span>
        <span class="fw-bold ms-3"><?= $dashboardTitle ?></span>
        <div class="d-flex align-items-center">
            <span class="badge bg-primary me-3">
                <i class="fas fa-user-circle me-1"></i>
                <?= ucfirst($userRole) ?>
            </span>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h5><i class="fas fa-graduation-cap me-2"></i>EduQuiz</h5>
        <small><?= $dashboardTitle ?></small>
    </div>

    <a href="?page=dashboard" class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>

    <a href="?page=quizzes" class="<?= $currentPage == 'quizzes' ? 'active' : '' ?>">
        <i class="fas fa-question-circle"></i> All Quizzes
    </a>

    <?php if ($userRole === 'admin'): ?>
        <a href="?page=users" class="<?= $currentPage == 'users' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i> User Management
        </a>
        <a href="?page=departments" class="<?= $currentPage == 'departments' ? 'active' : '' ?>">
            <i class="fas fa-school"></i> Departments
        </a>
    <?php endif; ?>

    <?php if ($userRole === 'teacher'): ?>
        <a href="?page=create" class="<?= $currentPage == 'create' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> Create Quiz
        </a>
        <a href="?page=myquizzes" class="<?= $currentPage == 'myquizzes' ? 'active' : '' ?>">
            <i class="fas fa-puzzle-piece"></i> My Quizzes
        </a>
    <?php endif; ?>
    
    <a href="?page=reports" class="<?= $currentPage == 'reports' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
    
    <a href="?page=settings" class="<?= $currentPage == 'settings' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Settings
    </a>
    
    <a href="?logout=1">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php if ($currentPage == 'dashboard'): ?>
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col">
                    <h1>
                        <i class="fas fa-hand-wave me-2"></i>
                        Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? ucfirst($userRole)) ?>!
                    </h1>
                    <p>Here's what's happening with your quizzes today.</p>
                </div>
                <div class="col-auto">
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <?php if ($userRole === 'admin'): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Users</h6>
                                <h3 class="mb-0 fw-bold">1,245</h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up me-1"></i>+12%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Quizzes</h6>
                                <h3 class="mb-0 fw-bold">156</h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up me-1"></i>+8%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Pending Reviews</h6>
                                <h3 class="mb-0 fw-bold">23</h3>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-circle me-1"></i>Needs attention
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Departments</h6>
                                <h3 class="mb-0 fw-bold">12</h3>
                                <small class="text-info">
                                    <i class="fas fa-circle me-1"></i>Active
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">My Quizzes</h6>
                                <h3 class="mb-0 fw-bold">24</h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up me-1"></i>+3 this week
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Attempts</h6>
                                <h3 class="mb-0 fw-bold">1,432</h3>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up me-1"></i>+18%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Avg. Score</h6>
                                <h3 class="mb-0 fw-bold">78.5%</h3>
                                <small class="text-info">
                                    <i class="fas fa-chart-line me-1"></i>+5.2%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Pending Reviews</h6>
                                <h3 class="mb-0 fw-bold">8</h3>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-circle me-1"></i>Needs attention
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Quizzes Cards -->
        <div class="row mt-4">
            <div class="col-12">
                <h4 class="section-title">
                    <i class="fas fa-clock"></i> Recent Quizzes
                </h4>
            </div>
            
            <?php foreach (array_slice($allQuizzes, 0, 4) as $quiz): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card quiz-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-<?= $quiz['difficulty'] == 'Beginner' ? 'success' : ($quiz['difficulty'] == 'Intermediate' ? 'warning' : 'danger') ?>">
                                <?= $quiz['difficulty'] ?>
                            </span>
                            <span class="badge bg-<?= $quiz['status'] == 'active' ? 'success' : ($quiz['status'] == 'draft' ? 'secondary' : 'danger') ?>">
                                <?= ucfirst($quiz['status']) ?>
                            </span>
                        </div>
                        <h6 class="card-title fw-bold"><?= htmlspecialchars($quiz['title']) ?></h6>
                        
                        <div class="quiz-meta mb-2">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($quiz['creator']) ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                <?= date('M d', strtotime($quiz['created_at'])) ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-question-circle"></i> 
                                <?= $quiz['questions'] ?> Q
                            </small>
                        </div>
                        
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-eye me-2"></i>Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mt-2">
            <div class="col-lg-6">
                <div class="card activity-card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>
                        Quiz Categories
                    </div>
                    <div class="card-body">
                        <?php
                        $categoryCounts = array_count_values(array_column($allQuizzes, 'category'));
                        foreach ($categoryCounts as $category => $count):
                            $percentage = round(($count / count($allQuizzes)) * 100);
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= $category ?></span>
                                <span class="text-muted"><?= $count ?> quizzes (<?= $percentage ?>%)</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card activity-card">
                    <div class="card-header">
                        <i class="fas fa-tasks me-2"></i>
                        Quick Actions
                    </div>
                    <div class="card-body">
                        <a href="#" class="btn btn-outline-primary w-100 text-start mb-2">
                            <i class="fas fa-plus-circle me-2"></i>
                            Create New Quiz
                        </a>
                        <a href="#" class="btn btn-outline-success w-100 text-start mb-2">
                            <i class="fas fa-upload me-2"></i>
                            Import Questions
                        </a>
                        <a href="#" class="btn btn-outline-info w-100 text-start mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            Send Notifications
                        </a>
                        <a href="#" class="btn btn-outline-warning w-100 text-start">
                            <i class="fas fa-chart-pie me-2"></i>
                            Generate Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($currentPage == 'quizzes' || $currentPage == 'myquizzes'): ?>
        <!-- Full Page Table View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="section-title mb-0">
                <i class="fas fa-list"></i> 
                <?= $currentPage == 'myquizzes' ? 'My Quizzes' : 'All Quizzes' ?>
            </h4>
            <a href="#" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Create New Quiz
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Category:</span>
                <select class="filter-select" id="categoryFilter">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category == 'All Categories' ? '' : $category ?>"><?= $category ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Difficulty:</span>
                <select class="filter-select" id="difficultyFilter">
                    <?php foreach ($difficultyLevels as $level): ?>
                        <option value="<?= $level == 'All Levels' ? '' : $level ?>"><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select class="filter-select" id="statusFilter">
                    <?php foreach ($statusTypes as $status): ?>
                        <option value="<?= $status == 'All Status' ? '' : strtolower($status) ?>"><?= $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="ms-auto">
                <input type="text" class="search-box" id="searchInput" placeholder="Search quizzes...">
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover" id="quizzesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Quiz Title</th>
                            <th>Creator</th>
                            <th>Category</th>
                            <th>Difficulty</th>
                            <th>Questions</th>
                            <th>Attempts</th>
                            <th>Avg Score</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allQuizzes as $quiz): ?>
                        <tr>
                            <td><span class="fw-bold">#<?= $quiz['id'] ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="table-avatar me-2">
                                        <?= substr($quiz['title'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars($quiz['title']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($quiz['creator']) ?></td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info">
                                    <?= $quiz['category'] ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $difficultyColor = 'success';
                                if ($quiz['difficulty'] == 'Intermediate') {
                                    $difficultyColor = 'warning';
                                } elseif ($quiz['difficulty'] == 'Advanced') {
                                    $difficultyColor = 'danger';
                                }
                                ?>
                                <span class="badge bg-<?= $difficultyColor ?> bg-opacity-10 text-<?= $difficultyColor ?>">
                                    <?= $quiz['difficulty'] ?>
                                </span>
                            </td>
                            <td><?= $quiz['questions'] ?></td>
                            <td>
                                <span class="fw-bold"><?= $quiz['attempts'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2 <?= $quiz['avg_score'] >= 80 ? 'text-success' : ($quiz['avg_score'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                        <?= $quiz['avg_score'] ?>%
                                    </span>
                                    <div class="progress progress-sm w-50">
                                        <div class="progress-bar bg-<?= $quiz['avg_score'] >= 80 ? 'success' : ($quiz['avg_score'] >= 60 ? 'warning' : 'danger') ?>" 
                                             style="width: <?= $quiz['avg_score'] ?>%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($quiz['status'] == 'active'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Active
                                    </span>
                                <?php elseif ($quiz['status'] == 'draft'): ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-pen me-1"></i>Draft
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-archive me-1"></i>Archived
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="far fa-calendar me-1"></i>
                                    <?= date('M d, Y', strtotime($quiz['created_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="#" class="btn btn-sm btn-outline-primary action-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-success action-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger action-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Table navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">4</a></li>
                    <li class="page-item"><a class="page-link" href="#">5</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Export Options -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card activity-card">
                    <div class="card-header">
                        <i class="fas fa-download me-2"></i>
                        Export Options
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-outline-success">
                                <i class="fas fa-file-excel me-2"></i>Export to Excel
                            </a>
                            <a href="#" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-2"></i>Export to PDF
                            </a>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-print me-2"></i>Print
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Other pages placeholder -->
        <div class="welcome-section">
            <h2><?= ucfirst($currentPage) ?> Page</h2>
            <p>This page is under construction.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.menu-btn');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !menuBtn.contains(event.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});

// Table filtering (simple implementation)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('#quizzesTable tbody tr');

    function filterTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const category = categoryFilter ? categoryFilter.value.toLowerCase() : '';
        const difficulty = difficultyFilter ? difficultyFilter.value.toLowerCase() : '';
        const status = statusFilter ? statusFilter.value.toLowerCase() : '';

        tableRows.forEach(row => {
            const title = row.cells[1].textContent.toLowerCase();
            const creator = row.cells[2].textContent.toLowerCase();
            const rowCategory = row.cells[3].textContent.toLowerCase();
            const rowDifficulty = row.cells[4].textContent.toLowerCase();
            const rowStatus = row.cells[8].textContent.toLowerCase();

            let show = true;

            if (searchTerm && !title.includes(searchTerm) && !creator.includes(searchTerm)) {
                show = false;
            }

            if (category && !rowCategory.includes(category)) {
                show = false;
            }

            if (difficulty && !rowDifficulty.includes(difficulty)) {
                show = false;
            }

            if (status && !rowStatus.includes(status)) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (categoryFilter) categoryFilter.addEventListener('change', filterTable);
    if (difficultyFilter) difficultyFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
});

// Add smooth hover effects
document.querySelectorAll('.quiz-card, .stat-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transition = 'all 0.3s ease';
    });
});
</script>

<!-- Optional: Add Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>