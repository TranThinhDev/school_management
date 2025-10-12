<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
        </a>
        
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['role'] == 'teacher'): ?>
                                <li><a class="dropdown-item" href="teacher/profile.php"><i class="fas fa-user-edit"></i> Hồ sơ</a></li>
                            <?php elseif ($_SESSION['role'] == 'student'): ?>
                                <li><a class="dropdown-item" href="student/profile.php"><i class="fas fa-user-edit"></i> Hồ sơ</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="admin/profile.php"><i class="fas fa-user-edit"></i> Hồ sơ</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>