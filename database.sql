-- Tạo database
DROP DATABASE IF EXISTS school_management;
CREATE DATABASE school_management;
USE school_management;

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Bảng lớp học
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    grade INT NOT NULL,
    homeroom_teacher_id INT,
    school_year VARCHAR(20),
    FOREIGN KEY (homeroom_teacher_id) REFERENCES users(id)
);

-- Bảng môn học
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) UNIQUE NOT NULL
);

-- Bảng phân công giảng dạy
CREATE TABLE teaching_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    semester INT NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- Bảng điểm số
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    semester INT NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    -- Điểm miệng
    oral_1 FLOAT,
    oral_2 FLOAT,
    oral_3 FLOAT,
    -- Điểm 15 phút
    fifteen_min_1 FLOAT,
    fifteen_min_2 FLOAT,
    fifteen_min_3 FLOAT,
    -- Điểm 45 phút
    forty_five_min_1 FLOAT,
    forty_five_min_2 FLOAT,
    forty_five_min_3 FLOAT,
    -- Điểm giữa kỳ và cuối kỳ
    mid_term FLOAT,
    final_term FLOAT,
    -- Điểm trung bình
    average FLOAT,
    -- Thời hạn nhập điểm
    score_entry_deadline DATE,
    is_locked BOOLEAN DEFAULT FALSE,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    UNIQUE KEY unique_score_entry (student_id, subject_id, class_id, semester, school_year)
);

-- Bảng tài liệu/video
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('document', 'video') NOT NULL,
    subject_id INT,
    class_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Bảng thông báo
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    target_audience ENUM('all', 'teachers', 'students') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Bảng học sinh trong lớp
CREATE TABLE class_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    UNIQUE KEY unique_class_student (student_id, class_id, school_year)
);

-- =============================================================================
-- THÊM DỮ LIỆU MẪU
-- =============================================================================

-- Admin (mật khẩu: 123456)
INSERT INTO users (username, password, role, full_name, email, phone) VALUES 
('admin', '123456', 'admin', 'Nguyễn Văn Quản Trị', 'admin@thpt.edu.vn', '0901111111');

-- Giáo viên (mật khẩu: 123456)
INSERT INTO users (username, password, role, full_name, email, phone) VALUES 
('gv_toan', '123456', 'teacher', 'Trần Thị Toán', 'gv_toan@thpt.edu.vn', '0902222222'),
('gv_van', '123456', 'teacher', 'Lê Văn Văn', 'gv_van@thpt.edu.vn', '0903333333'),
('gv_ly', '123456', 'teacher', 'Phạm Vật Lý', 'gv_ly@thpt.edu.vn', '0904444444'),
('gv_hoa', '123456', 'teacher', 'Hoàng Thị Hóa', 'gv_hoa@thpt.edu.vn', '0905555555'),
('gv_anh', '123456', 'teacher', 'Vũ Tiếng Anh', 'gv_anh@thpt.edu.vn', '0906666666'),
('gv_su', '123456', 'teacher', 'Ngô Văn Sử', 'gv_su@thpt.edu.vn', '0907777777'),
('gv_dia', '123456', 'teacher', 'Đặng Thị Địa', 'gv_dia@thpt.edu.vn', '0908888888');

-- Học sinh (mật khẩu: 123456)
INSERT INTO users (username, password, role, full_name, email, phone) VALUES 
-- Lớp 10A1
('hs_1001', '123456', 'student', 'Nguyễn Văn An', 'an.nguyen@thpt.edu.vn', '0911111111'),
('hs_1002', '123456', 'student', 'Trần Thị Bình', 'binh.tran@thpt.edu.vn', '0911111112'),
('hs_1003', '123456', 'student', 'Lê Văn Cường', 'cuong.le@thpt.edu.vn', '0911111113'),
('hs_1004', '123456', 'student', 'Phạm Thị Dung', 'dung.pham@thpt.edu.vn', '0911111114'),
('hs_1005', '123456', 'student', 'Hoàng Văn Em', 'em.hoang@thpt.edu.vn', '0911111115'),

-- Lớp 10A2
('hs_1006', '123456', 'student', 'Vũ Thị Phương', 'phuong.vu@thpt.edu.vn', '0911111116'),
('hs_1007', '123456', 'student', 'Ngô Văn Quân', 'quan.ngo@thpt.edu.vn', '0911111117'),
('hs_1008', '123456', 'student', 'Đặng Thị Hương', 'huong.dang@thpt.edu.vn', '0911111118'),

-- Lớp 11A1
('hs_1101', '123456', 'student', 'Bùi Văn Minh', 'minh.bui@thpt.edu.vn', '0911111119'),
('hs_1102', '123456', 'student', 'Đỗ Thị Ngọc', 'ngoc.do@thpt.edu.vn', '0911111120'),

-- Lớp 12A1
('hs_1201', '123456', 'student', 'Mai Văn Tài', 'tai.mai@thpt.edu.vn', '0911111121'),
('hs_1202', '123456', 'student', 'Lý Thị Thảo', 'thao.ly@thpt.edu.vn', '0911111122');

-- Môn học
INSERT INTO subjects (subject_name, subject_code) VALUES 
('Toán', 'MATH'),
('Ngữ Văn', 'LITERATURE'),
('Vật Lý', 'PHYSICS'),
('Hóa Học', 'CHEMISTRY'),
('Tiếng Anh', 'ENGLISH'),
('Lịch Sử', 'HISTORY'),
('Địa Lý', 'GEOGRAPHY'),
('Sinh Học', 'BIOLOGY'),
('Giáo Dục Công Dân', 'CIVIC_EDUCATION'),
('Công Nghệ', 'TECHNOLOGY'),
('Thể Dục', 'PHYSICAL_EDU'),
('Quốc Phòng', 'DEFENSE_EDU');

-- Lớp học
INSERT INTO classes (class_name, grade, homeroom_teacher_id, school_year) VALUES 
('10A1', 10, 2, '2024-2025'),
('10A2', 10, 3, '2024-2025'),
('11A1', 11, 4, '2024-2025'),
('11A2', 11, 5, '2024-2025'),
('12A1', 12, 6, '2024-2025'),
('12A2', 12, 7, '2024-2025');

-- Phân công giảng dạy Học kỳ 1
INSERT INTO teaching_assignments (teacher_id, class_id, subject_id, semester, school_year) VALUES 
-- Lớp 10A1
(2, 1, 1, 1, '2024-2025'), -- Toán
(3, 1, 2, 1, '2024-2025'), -- Văn
(4, 1, 3, 1, '2024-2025'), -- Lý
(5, 1, 4, 1, '2024-2025'), -- Hóa
(6, 1, 5, 1, '2024-2025'), -- Anh

-- Lớp 10A2
(2, 2, 1, 1, '2024-2025'), -- Toán
(3, 2, 2, 1, '2024-2025'), -- Văn
(4, 2, 3, 1, '2024-2025'), -- Lý

-- Lớp 11A1
(2, 3, 1, 1, '2024-2025'), -- Toán
(3, 3, 2, 1, '2024-2025'), -- Văn
(5, 3, 4, 1, '2024-2025'), -- Hóa

-- Lớp 12A1
(2, 5, 1, 1, '2024-2025'), -- Toán
(3, 5, 2, 1, '2024-2025'), -- Văn
(6, 5, 5, 1, '2024-2025'); -- Anh

-- Học sinh trong lớp
INSERT INTO class_students (student_id, class_id, school_year) VALUES 
-- Lớp 10A1
(8, 1, '2024-2025'),
(9, 1, '2024-2025'),
(10, 1, '2024-2025'),
(11, 1, '2024-2025'),
(12, 1, '2024-2025'),

-- Lớp 10A2
(13, 2, '2024-2025'),
(14, 2, '2024-2025'),
(15, 2, '2024-2025'),

-- Lớp 11A1
(16, 3, '2024-2025'),
(17, 3, '2024-2025'),

-- Lớp 12A1
(18, 5, '2024-2025'),
(19, 5, '2024-2025');

-- Thông báo
INSERT INTO announcements (title, content, author_id, target_audience) VALUES 
('Chào mừng năm học mới 2024-2025', 'Trường THPT chúc mừng tất cả học sinh và giáo viên bước vào năm học mới. Chúc các em học sinh một năm học thành công và đạt nhiều kết quả tốt!', 1, 'all'),
('Lịch thi học kỳ 1', 'Lịch thi học kỳ 1 năm học 2024-2025 sẽ diễn ra từ ngày 15/12/2024 đến 23/12/2024. Các em học sinh chú ý ôn tập chuẩn bị cho kỳ thi.', 1, 'all'),
('Họp phụ huynh đầu năm', 'Nhà trường tổ chức họp phụ huynh đầu năm cho tất cả các khối lớp vào ngày 15/9/2024. Kính mời quý phụ huynh đến tham dự đầy đủ.', 1, 'all'),
('Thông báo nghỉ lễ 2/9', 'Nhà trường thông báo lịch nghỉ lễ Quốc khánh 2/9 từ ngày 01/09/2024 đến hết ngày 03/09/2024.', 1, 'all'),
('Cuộc thi Olympic Toán học', 'Trường phát động cuộc thi Olympic Toán học cấp trường. Hạn đăng ký đến hết ngày 30/9/2024.', 1, 'students'),
('Họp giáo viên chủ nhiệm', 'Cuộc họp giáo viên chủ nhiệm sẽ diễn ra vào lúc 14h00 ngày thứ 6 hàng tuần tại phòng họp A.', 1, 'teachers'),
('Đóng học phí tháng 9', 'Nhắc nhở học sinh đóng học phí tháng 9 trước ngày 10/9/2024.', 1, 'students');

-- Tài liệu học tập
INSERT INTO materials (teacher_id, title, description, file_path, file_type, subject_id, class_id) VALUES 
(2, 'Bài giảng Toán 10 - Chương 1', 'Tài liệu bài giảng môn Toán lớp 10 chương 1: Mệnh đề và tập hợp', 'uploads/documents/toan10_chuong1.pdf', 'document', 1, 1),
(3, 'Văn mẫu: Tây Tiến', 'Phân tích bài thơ Tây Tiến của Quang Dũng', 'uploads/documents/van_taytien.pdf', 'document', 2, 1),
(4, 'Thí nghiệm Vật lý 10', 'Video hướng dẫn thí nghiệm định luật Newton', 'uploads/videos/vatly_thi_nghiem.mp4', 'video', 3, 1),
(5, 'Bài tập Hóa học 10', 'Tổng hợp bài tập chương Nguyên tử', 'uploads/documents/hoa_baitap.pdf', 'document', 4, 1),
(6, 'Ngữ pháp Tiếng Anh cơ bản', 'Tài liệu ngữ pháp Tiếng Anh dành cho học sinh lớp 10', 'uploads/documents/english_grammar.pdf', 'document', 5, NULL);

-- Điểm số mẫu (Học kỳ 1) - ĐÃ SỬA LỖI
INSERT INTO scores (student_id, subject_id, class_id, teacher_id, semester, school_year, 
                   oral_1, oral_2, fifteen_min_1, fifteen_min_2, forty_five_min_1, forty_five_min_2, 
                   mid_term, final_term, average, score_entry_deadline) VALUES 
-- Học sinh 1 - Toán
(8, 1, 1, 2, 1, '2024-2025', 8.5, 9.0, 7.5, 8.0, 8.0, 8.5, 7.5, 8.0, 8.0, '2024-12-31'),
-- Học sinh 1 - Văn
(8, 2, 1, 3, 1, '2024-2025', 7.0, 8.0, 8.5, 7.5, 8.0, 8.5, 8.0, 8.5, 8.1, '2024-12-31'),
-- Học sinh 1 - Lý
(8, 3, 1, 4, 1, '2024-2025', 9.0, 8.5, 8.0, 9.0, 8.5, 8.0, 8.0, 8.5, 8.4, '2024-12-31'),

-- Học sinh 2 - Toán
(9, 1, 1, 2, 1, '2024-2025', 9.0, 8.5, 8.5, 9.0, 8.0, 8.5, 8.5, 9.0, 8.6, '2024-12-31'),
-- Học sinh 2 - Văn
(9, 2, 1, 3, 1, '2024-2025', 8.5, 8.0, 7.5, 8.0, 8.5, 8.0, 8.0, 8.5, 8.2, '2024-12-31'),

-- Học sinh 3 - Toán
(10, 1, 1, 2, 1, '2024-2025', 7.5, 8.0, 7.0, 7.5, 7.5, 8.0, 7.0, 7.5, 7.5, '2024-12-31'),

-- Học sinh 4 - Toán (bị khóa do quá hạn)
(11, 1, 1, 2, 1, '2024-2025', 6.5, 7.0, 7.0, 6.5, 7.0, 7.5, 6.5, 7.0, 6.9, '2024-06-30'),

-- Học sinh 5 - Toán (ĐÃ SỬA - bỏ giá trị semester thừa)
(12, 1, 1, 2, 1, '2024-2025', 8.0, 8.5, 8.0, 8.5, 8.5, 9.0, 8.0, 8.5, 8.4, '2024-12-31');

-- Cập nhật điểm bị khóa
UPDATE scores SET is_locked = TRUE WHERE score_entry_deadline < CURDATE();

-- =============================================================================
-- TẠO INDEX ĐỂ TỐI ƯU HIỆU SUẤT
-- =============================================================================

CREATE INDEX idx_scores_student ON scores(student_id);
CREATE INDEX idx_scores_subject ON scores(subject_id);
CREATE INDEX idx_scores_class ON scores(class_id);
CREATE INDEX idx_scores_teacher ON scores(teacher_id);
CREATE INDEX idx_scores_semester ON scores(semester, school_year);

CREATE INDEX idx_teaching_assignments_teacher ON teaching_assignments(teacher_id);
CREATE INDEX idx_teaching_assignments_class ON teaching_assignments(class_id);

CREATE INDEX idx_class_students_student ON class_students(student_id);
CREATE INDEX idx_class_students_class ON class_students(class_id);

CREATE INDEX idx_materials_teacher ON materials(teacher_id);
CREATE INDEX idx_materials_subject ON materials(subject_id);

-- =============================================================================
-- TẠO VIEW ĐỂ TRUY VẤN THUẬN TIỆN
-- =============================================================================

-- View thống kê điểm trung bình theo lớp
CREATE VIEW class_average_scores AS
SELECT 
    c.class_name,
    s.subject_name,
    sc.semester,
    sc.school_year,
    AVG(sc.average) as class_average,
    COUNT(sc.student_id) as student_count
FROM scores sc
JOIN classes c ON sc.class_id = c.id
JOIN subjects s ON sc.subject_id = s.id
WHERE sc.average IS NOT NULL
GROUP BY c.class_name, s.subject_name, sc.semester, sc.school_year;

-- View học sinh và lớp
CREATE VIEW student_class_info AS
SELECT 
    u.id as student_id,
    u.username,
    u.full_name,
    u.email,
    u.phone,
    c.class_name,
    c.grade,
    c.school_year,
    hm.full_name as homeroom_teacher
FROM users u
JOIN class_students cs ON u.id = cs.student_id
JOIN classes c ON cs.class_id = c.id
LEFT JOIN users hm ON c.homeroom_teacher_id = hm.id
WHERE u.role = 'student' AND u.status = 'active';

-- View phân công giảng dạy chi tiết
CREATE VIEW teaching_assignment_details AS
SELECT 
    ta.*,
    u.full_name as teacher_name,
    c.class_name,
    c.grade,
    s.subject_name,
    s.subject_code
FROM teaching_assignments ta
JOIN users u ON ta.teacher_id = u.id
JOIN classes c ON ta.class_id = c.id
JOIN subjects s ON ta.subject_id = s.id;

-- =============================================================================
-- TẠO STORED PROCEDURE
-- =============================================================================

-- Procedure tính điểm trung bình tự động
DELIMITER $$
CREATE PROCEDURE CalculateStudentAverage(
    IN p_student_id INT,
    IN p_subject_id INT,
    IN p_class_id INT,
    IN p_semester INT,
    IN p_school_year VARCHAR(20)
)
BEGIN
    DECLARE avg_score FLOAT;
    
    SELECT 
        (COALESCE(oral_1, 0) + COALESCE(oral_2, 0) + COALESCE(oral_3, 0) +
         COALESCE(fifteen_min_1, 0) + COALESCE(fifteen_min_2, 0) + COALESCE(fifteen_min_3, 0) +
         COALESCE(forty_five_min_1, 0) * 2 + COALESCE(forty_five_min_2, 0) * 2 + COALESCE(forty_five_min_3, 0) * 2 +
         COALESCE(mid_term, 0) * 2 + COALESCE(final_term, 0) * 3) /
        (CASE WHEN oral_1 IS NOT NULL THEN 1 ELSE 0 END + 
         CASE WHEN oral_2 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN oral_3 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN fifteen_min_1 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN fifteen_min_2 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN fifteen_min_3 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN forty_five_min_1 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN forty_five_min_2 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN forty_five_min_3 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN mid_term IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN final_term IS NOT NULL THEN 3 ELSE 0 END)
    INTO avg_score
    FROM scores
    WHERE student_id = p_student_id 
        AND subject_id = p_subject_id 
        AND class_id = p_class_id 
        AND semester = p_semester 
        AND school_year = p_school_year;
    
    UPDATE scores 
    SET average = ROUND(avg_score, 2)
    WHERE student_id = p_student_id 
        AND subject_id = p_subject_id 
        AND class_id = p_class_id 
        AND semester = p_semester 
        AND school_year = p_school_year;
END$$
DELIMITER ;

-- Procedure thống kê học lực
DELIMITER $$
CREATE PROCEDURE GetAcademicPerformance(
    IN p_class_id INT,
    IN p_semester INT,
    IN p_school_year VARCHAR(20)
)
BEGIN
    SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN avg_score >= 8 THEN 1 ELSE 0 END) as excellent,
        SUM(CASE WHEN avg_score >= 6.5 AND avg_score < 8 THEN 1 ELSE 0 END) as good,
        SUM(CASE WHEN avg_score >= 5 AND avg_score < 6.5 THEN 1 ELSE 0 END) as average,
        SUM(CASE WHEN avg_score < 5 THEN 1 ELSE 0 END) as weak
    FROM (
        SELECT student_id, AVG(average) as avg_score
        FROM scores 
        WHERE class_id = p_class_id 
            AND semester = p_semester 
            AND school_year = p_school_year
            AND average IS NOT NULL
        GROUP BY student_id
    ) as student_averages;
END$$
DELIMITER ;

-- =============================================================================
-- TẠO TRIGGER
-- =============================================================================

-- Trigger tự động tính điểm trung bình khi cập nhật điểm
DELIMITER $$
CREATE TRIGGER before_score_update
BEFORE UPDATE ON scores
FOR EACH ROW
BEGIN
    -- Tính điểm trung bình nếu có thay đổi điểm số
    IF (OLD.oral_1 <> NEW.oral_1 OR OLD.oral_2 <> NEW.oral_2 OR OLD.oral_3 <> NEW.oral_3 OR
        OLD.fifteen_min_1 <> NEW.fifteen_min_1 OR OLD.fifteen_min_2 <> NEW.fifteen_min_2 OR OLD.fifteen_min_3 <> NEW.fifteen_min_3 OR
        OLD.forty_five_min_1 <> NEW.forty_five_min_1 OR OLD.forty_five_min_2 <> NEW.forty_five_min_2 OR OLD.forty_five_min_3 <> NEW.forty_five_min_3 OR
        OLD.mid_term <> NEW.mid_term OR OLD.final_term <> NEW.final_term) THEN
        
        SET NEW.average = (
            (COALESCE(NEW.oral_1, 0) + COALESCE(NEW.oral_2, 0) + COALESCE(NEW.oral_3, 0) +
             COALESCE(NEW.fifteen_min_1, 0) + COALESCE(NEW.fifteen_min_2, 0) + COALESCE(NEW.fifteen_min_3, 0) +
             COALESCE(NEW.forty_five_min_1, 0) * 2 + COALESCE(NEW.forty_five_min_2, 0) * 2 + COALESCE(NEW.forty_five_min_3, 0) * 2 +
             COALESCE(NEW.mid_term, 0) * 2 + COALESCE(NEW.final_term, 0) * 3) /
            (CASE WHEN NEW.oral_1 IS NOT NULL THEN 1 ELSE 0 END + 
             CASE WHEN NEW.oral_2 IS NOT NULL THEN 1 ELSE 0 END +
             CASE WHEN NEW.oral_3 IS NOT NULL THEN 1 ELSE 0 END +
             CASE WHEN NEW.fifteen_min_1 IS NOT NULL THEN 1 ELSE 0 END +
             CASE WHEN NEW.fifteen_min_2 IS NOT NULL THEN 1 ELSE 0 END +
             CASE WHEN NEW.fifteen_min_3 IS NOT NULL THEN 1 ELSE 0 END +
             CASE WHEN NEW.forty_five_min_1 IS NOT NULL THEN 2 ELSE 0 END +
             CASE WHEN NEW.forty_five_min_2 IS NOT NULL THEN 2 ELSE 0 END +
             CASE WHEN NEW.forty_five_min_3 IS NOT NULL THEN 2 ELSE 0 END +
             CASE WHEN NEW.mid_term IS NOT NULL THEN 2 ELSE 0 END +
             CASE WHEN NEW.final_term IS NOT NULL THEN 3 ELSE 0 END)
        );
        
        SET NEW.average = ROUND(NEW.average, 2);
    END IF;
END$$
DELIMITER ;

-- Trigger kiểm tra hạn nhập điểm
DELIMITER $$
CREATE TRIGGER before_score_insert
BEFORE INSERT ON scores
FOR EACH ROW
BEGIN
    -- Tự động khóa nếu quá hạn
    IF NEW.score_entry_deadline < CURDATE() THEN
        SET NEW.is_locked = TRUE;
    END IF;
    
    -- Tính điểm trung bình ban đầu
    SET NEW.average = (
        (COALESCE(NEW.oral_1, 0) + COALESCE(NEW.oral_2, 0) + COALESCE(NEW.oral_3, 0) +
         COALESCE(NEW.fifteen_min_1, 0) + COALESCE(NEW.fifteen_min_2, 0) + COALESCE(NEW.fifteen_min_3, 0) +
         COALESCE(NEW.forty_five_min_1, 0) * 2 + COALESCE(NEW.forty_five_min_2, 0) * 2 + COALESCE(NEW.forty_five_min_3, 0) * 2 +
         COALESCE(NEW.mid_term, 0) * 2 + COALESCE(NEW.final_term, 0) * 3) /
        (CASE WHEN NEW.oral_1 IS NOT NULL THEN 1 ELSE 0 END + 
         CASE WHEN NEW.oral_2 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN NEW.oral_3 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN NEW.fifteen_min_1 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN NEW.fifteen_min_2 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN NEW.fifteen_min_3 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN NEW.forty_five_min_1 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN NEW.forty_five_min_2 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN NEW.forty_five_min_3 IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN NEW.mid_term IS NOT NULL THEN 2 ELSE 0 END +
         CASE WHEN NEW.final_term IS NOT NULL THEN 3 ELSE 0 END)
    );
    
    SET NEW.average = ROUND(NEW.average, 2);
END$$
DELIMITER ;

