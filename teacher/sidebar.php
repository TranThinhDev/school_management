<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                    <i class="fas fa-bullhorn"></i>
                    Thông báo
                    <?php
                    // Hiển thị badge thông báo mới (7 ngày gần đây)
                    if (isset($_SESSION['user_id'])) {
                        try {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as new_count 
                                FROM announcements 
                                WHERE (target_audience = 'all' OR target_audience = 'teachers')
                                AND status = 'active'
                                AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                            ");
                            $stmt->execute();
                            $result = $stmt->fetch();
                            $new_count = $result['new_count'];
                            
                            if ($new_count > 0) {
                                echo '<span class="badge bg-danger ms-1">'.$new_count.'</span>';
                            }
                        } catch (Exception $e) {
                            // Không hiển thị gì nếu có lỗi
                        }
                    }
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="fas fa-users"></i>
                    Học sinh
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'scores.php' ? 'active' : ''; ?>" href="scores.php">
                    <i class="fas fa-chart-line"></i>
                    Nhập điểm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'materials.php' ? 'active' : ''; ?>" href="materials.php">
                    <i class="fas fa-file-upload"></i>
                    Tài liệu & Video
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'export_scores.php' ? 'active' : ''; ?>" href="export_scores.php">
                    <i class="fas fa-file-excel"></i>
                    Export Excel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user-cog"></i>
                    Hồ sơ
                </a>
            </li>
        </ul>
    </div>
</nav>