-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 14, 2025 lúc 09:34 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `school_management`
--

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CalculateStudentAverage` (IN `p_student_id` INT, IN `p_subject_id` INT, IN `p_class_id` INT, IN `p_semester` INT, IN `p_school_year` VARCHAR(20))   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAcademicPerformance` (IN `p_class_id` INT, IN `p_semester` INT, IN `p_school_year` VARCHAR(20))   BEGIN
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `target_audience` enum('all','teachers','students') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `author_id`, `target_audience`, `created_at`, `is_active`, `expires_at`) VALUES
(6, 'Họp giáo viên chủ nhiệm', 'Cuộc họp giáo viên chủ nhiệm sẽ diễn ra vào lúc 14h00 ngày thứ 6 hàng tuần tại phòng họp A.', 1, 'teachers', '2025-10-05 14:10:13', 1, '2025-10-29 21:10:13'),
(7, 'Đóng học phí tháng 9', 'Nhắc nhở học sinh đóng học phí tháng 9 trước ngày 10/9/2024.', 1, 'students', '2025-10-05 14:10:13', 1, '2025-10-29 21:10:13'),
(9, '1', '1', 1, 'all', '2025-10-08 04:34:21', 0, '2025-10-29 21:10:13'),
(10, '2', '2', 1, 'all', '2025-10-08 04:34:31', 0, '2025-10-29 21:10:13'),
(11, '3', '3', 1, 'all', '2025-10-08 04:34:36', 0, '2025-10-29 21:10:13'),
(12, '5', '5', 1, 'all', '2025-10-08 04:34:40', 0, '2025-10-29 21:10:13'),
(13, '4', '4', 1, 'all', '2025-10-08 04:34:43', 0, '2025-10-29 21:10:13'),
(14, '6', '6', 1, 'all', '2025-10-08 04:34:48', 0, '2025-10-29 21:10:13'),
(15, '7', '7', 1, 'all', '2025-10-08 04:34:51', 0, '2025-10-29 21:10:13'),
(16, '11', 'Trái đất mất hàng tỉ năm để hình thành và cũng mất hàng triệu năm để sự sống được nhen nhóm và tồn tại. Nhưng trải qua hàng ngàn năm bồi đắp, sự sống ấy lại đang vô tình mất đi do chính những người đang mỉm cười vì sự sống đó.\r\n\r\nĐã đến lúc mà con người phải ý thức được sâu sắc vận mệnh và hành động của mình. Nhất là khi chúng ta còn đang sống trong thời đại của khoa học và kỹ thuật tiên tiến, càng phải ý thức hơn về việc đó. Trẻ em là người sẽ quyết định tương lai, vị thế của mỗi dân tộc trên trường quốc tế. Qua vấn đề bảo vệ, chăm sóc trẻ em, chúng ta có thể nhận ra được trình độ văn minh và phần nào bản chất của một xã hội\".\r\n\r\nĐoạn văn trên sau đó đã được cô giáo cho 4 điểm kèm lời phê \"lạc đề\". Dân mạng thì ôm bụng cười vì đoạn mở bài quá bá đạo của nam sinh này. Trong khi cô giáo ra đề viết 1 đoạn văn nhưng con trai chị Xinh viết dài đến 6 dòng vẫn chưa hết đoạn mở bài. Đặc biệt, nhiều người đọc đi đọc lại vẫn không tìm ra mối liên quan giữa quyền học tập trẻ em với \"trái đất được hình thành\" và \"sự sống được nhen nhóm\".\r\n\r\nTuy nhiên, cũng có người thừa nhận: \"Cá nhân mình thấy bạn ấy viết được như thế chứng tỏ kiến thức không hề ít. Nếu có thời gian sẽ có 1 bài văn mang tầm vĩ mô. Mình sẽ lưu đoạn văn này cho con mình tham khảo\".', 1, 'all', '2025-10-08 07:01:54', 0, '2025-10-29 21:10:13'),
(17, '111111', '11111111', 1, 'all', '2025-10-08 07:02:12', 1, '2025-10-29 21:10:13'),
(19, 'Trái đất mất hàng tỉ năm để hình thành và cũng mất hàng triệu năm để sự sống được nhen nhóm và tồn tại. Nhưng trải qua hàng ngàn năm bồi đắp, sự sống ấy lại đang vô tình mất đi do chính những người đang mỉm cười vì sự sống đó.  Đã đến lúc mà con người phả', 'Trái đất mất hàng tỉ năm để hình thành và cũng mất hàng triệu năm để sự sống được nhen nhóm và tồn tại. Nhưng trải qua hàng ngàn năm bồi đắp, sự sống ấy lại đang vô tình mất đi do chính những người đang mỉm cười vì sự sống đó.\r\n\r\nĐã đến lúc mà con người phải ý thức được sâu sắc vận mệnh và hành động của mình. Nhất là khi chúng ta còn đang sống trong thời đại của khoa học và kỹ thuật tiên tiến, càng phải ý thức hơn về việc đó. Trẻ em là người sẽ quyết định tương lai, vị thế của mỗi dân tộc trên trường quốc tế. Qua vấn đề bảo vệ, chăm sóc trẻ em, chúng ta có thể nhận ra được trình độ văn minh và phần nào bản chất của một xã hội\".\r\n\r\nĐoạn văn trên sau đó đã được cô giáo cho 4 điểm kèm lời phê \"lạc đề\". Dân mạng thì ôm bụng cười vì đoạn mở bài quá bá đạo của nam sinh này. Trong khi cô giáo ra đề viết 1 đoạn văn nhưng con trai chị Xinh viết dài đến 6 dòng vẫn chưa hết đoạn mở bài. Đặc biệt, nhiều người đọc đi đọc lại vẫn không tìm ra mối liên quan giữa quyền học tập trẻ em với \"trái đất được hình thành\" và \"sự sống được nhen nhóm\".\r\n\r\nTuy nhiên, cũng có người thừa nhận: \"Cá nhân mình thấy bạn ấy viết được như thế chứng tỏ kiến thức không hề ít. Nếu có thời gian sẽ có 1 bài văn mang tầm vĩ mô. Mình sẽ lưu đoạn văn này cho con mình tham khảo\".', 1, 'all', '2025-10-08 07:26:54', 0, '2025-10-29 21:10:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `announcement_views`
--

INSERT INTO `announcement_views` (`id`, `announcement_id`, `user_id`, `viewed_at`) VALUES
(2, 16, 2, '2025-10-08 07:09:18'),
(3, 15, 2, '2025-10-08 07:15:38'),
(4, 14, 2, '2025-10-08 07:15:43'),
(5, 13, 2, '2025-10-08 07:15:55'),
(6, 12, 2, '2025-10-08 07:19:18'),
(7, 11, 2, '2025-10-08 07:19:21'),
(9, 10, 2, '2025-10-08 07:24:50'),
(10, 9, 2, '2025-10-08 07:24:53'),
(11, 19, 2, '2025-10-08 07:27:17'),
(12, 19, 9, '2025-10-08 14:12:33'),
(14, 16, 9, '2025-10-08 14:12:54'),
(15, 15, 9, '2025-10-08 14:12:56'),
(16, 14, 9, '2025-10-08 14:12:58'),
(17, 12, 9, '2025-10-08 14:13:00'),
(18, 11, 9, '2025-10-08 14:13:01'),
(19, 10, 9, '2025-10-08 14:13:03'),
(20, 9, 9, '2025-10-08 14:13:07'),
(22, 17, 9, '2025-10-11 16:53:14'),
(23, 17, 2, '2025-10-11 16:57:23'),
(24, 6, 2, '2025-10-11 16:57:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `grade` int(11) NOT NULL,
  `homeroom_teacher_id` int(11) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `grade`, `homeroom_teacher_id`, `school_year`) VALUES
(1, '10A1', 10, 2, '2024-2025'),
(2, '10A2', 10, 3, '2024-2025'),
(3, '11A1', 11, 4, '2024-2025'),
(4, '11A2', 11, 5, '2024-2025'),
(5, '12A1', 12, 6, '2024-2025'),
(6, '12A2', 12, 7, '2024-2025'),
(7, '10A4', 10, 4, '2024-2025'),
(8, '10A1', 10, 7, '2025-2026'),
(9, '12B1', 12, 6, '2025-2026');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `class_average_scores`
-- (See below for the actual view)
--
CREATE TABLE `class_average_scores` (
`class_name` varchar(50)
,`subject_name` varchar(100)
,`semester` int(11)
,`school_year` varchar(20)
,`class_average` double
,`student_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `class_students`
--

CREATE TABLE `class_students` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `status` enum('active','transferred','graduated','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `class_students`
--

INSERT INTO `class_students` (`id`, `student_id`, `class_id`, `school_year`, `status`) VALUES
(1, 8, 1, '2024-2025', 'active'),
(3, 10, 1, '2024-2025', 'transferred'),
(4, 11, 1, '2024-2025', 'active'),
(5, 12, 1, '2024-2025', 'active'),
(6, 13, 2, '2024-2025', 'active'),
(7, 14, 2, '2024-2025', 'active'),
(8, 15, 2, '2024-2025', 'active'),
(9, 16, 3, '2024-2025', 'active'),
(10, 17, 3, '2024-2025', 'active'),
(11, 18, 5, '2024-2025', 'active'),
(12, 19, 5, '2024-2025', 'active'),
(13, 9, 1, '2024-2025', 'active'),
(15, 22, 1, '2024-2025', 'active'),
(17, 24, 7, '2024-2025', 'active'),
(18, 20, 2, '2024-2025', 'active'),
(19, 10, 2, '2024-2025', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `homeroom_history`
--

CREATE TABLE `homeroom_history` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `homeroom_history`
--

INSERT INTO `homeroom_history` (`id`, `teacher_id`, `class_id`, `school_year`, `is_active`, `start_date`, `end_date`) VALUES
(1, 2, 1, '2024-2025', 1, NULL, NULL),
(2, 3, 2, '2024-2025', 1, NULL, NULL),
(3, 4, 3, '2024-2025', 1, NULL, NULL),
(4, 5, 4, '2024-2025', 1, NULL, NULL),
(5, 6, 5, '2024-2025', 1, NULL, NULL),
(6, 7, 6, '2024-2025', 1, NULL, NULL),
(7, 4, 7, '2024-2025', 1, NULL, NULL),
(8, 8, 8, '2025-2026', 0, '2025-10-13', '2025-10-13'),
(9, 7, 8, '2025-2026', 1, '2025-10-13', NULL),
(10, 6, 9, '2025-2026', 1, '2025-10-13', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('document','video') NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `materials`
--

INSERT INTO `materials` (`id`, `teacher_id`, `title`, `description`, `file_path`, `file_type`, `subject_id`, `class_id`, `uploaded_at`) VALUES
(14, 2, 'w', '', 'uploads/documents/1759716792_activity_android.docx', 'document', 1, 1, '2025-10-06 02:13:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `scores`
--

CREATE TABLE `scores` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `oral_1` float DEFAULT NULL,
  `oral_2` float DEFAULT NULL,
  `oral_3` float DEFAULT NULL,
  `fifteen_min_1` float DEFAULT NULL,
  `fifteen_min_2` float DEFAULT NULL,
  `fifteen_min_3` float DEFAULT NULL,
  `forty_five_min_1` float DEFAULT NULL,
  `forty_five_min_2` float DEFAULT NULL,
  `forty_five_min_3` float DEFAULT NULL,
  `mid_term` float DEFAULT NULL,
  `final_term` float DEFAULT NULL,
  `average` float DEFAULT NULL,
  `score_entry_deadline` date DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `scores`
--

INSERT INTO `scores` (`id`, `student_id`, `subject_id`, `class_id`, `teacher_id`, `semester`, `school_year`, `oral_1`, `oral_2`, `oral_3`, `fifteen_min_1`, `fifteen_min_2`, `fifteen_min_3`, `forty_five_min_1`, `forty_five_min_2`, `forty_five_min_3`, `mid_term`, `final_term`, `average`, `score_entry_deadline`, `is_locked`, `last_modified`) VALUES
(1, 8, 1, 1, 2, 1, '2024-2025', 8.5, 9, 0, 7.5, 8, 0, 8, 8.5, 0, 7.5, 8, 6.18, '2025-11-05', 0, '2025-10-08 07:59:14'),
(2, 8, 2, 1, 3, 1, '2024-2025', 7, 8, NULL, 8.5, 7.5, NULL, 8, 8.5, NULL, 8, 8.5, 8.1, '2024-12-31', 1, '2025-10-05 14:10:13'),
(3, 8, 3, 1, 4, 1, '2024-2025', 9, 8.5, NULL, 8, 9, NULL, 8.5, 8, NULL, 8, 8.5, 8.4, '2024-12-31', 1, '2025-10-05 14:10:13'),
(4, 9, 1, 1, 2, 1, '2024-2025', 4, 4, 4, 8.5, 9, 7.8, 8, 8.1, 8, 8.5, 7, 7.26, '2025-11-05', 0, '2025-10-08 07:59:14'),
(5, 9, 2, 1, 3, 1, '2024-2025', 8.5, 8, NULL, 7.5, 8, NULL, 8.5, 8, NULL, 8, 8.5, 8.2, '2024-12-31', 1, '2025-10-05 14:10:13'),
(6, 10, 1, 1, 2, 1, '2024-2025', 7.5, 8, 0, 7, 7.5, 0, 7.5, 8, 0, 7, 7.5, 5.74, '2025-11-05', 0, '2025-10-08 07:59:14'),
(7, 11, 1, 1, 2, 1, '2024-2025', 6.5, 7, 8, 7, 6.5, 8, 7, 7.5, 8, 6.5, 7, 7.18, '2025-11-05', 0, '2025-10-08 07:59:14'),
(8, 12, 1, 1, 2, 1, '2024-2025', 8, 8.5, 0, 8, 8.5, 0, 8.5, 9, 0, 8, 8.5, 6.44, '2025-11-05', 0, '2025-10-08 07:59:14'),
(9, 11, 1, 1, 2, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 08:01:50'),
(10, 9, 1, 1, 2, 2, '2024-2025', 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, '2025-10-22', 0, '2025-10-08 08:01:50'),
(11, 12, 1, 1, 2, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 08:01:50'),
(12, 10, 1, 1, 2, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 08:01:50'),
(13, 8, 1, 1, 2, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 08:01:50'),
(14, 11, 2, 1, 3, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 12:38:06'),
(15, 9, 2, 1, 3, 2, '2024-2025', 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, '2025-10-22', 0, '2025-10-08 12:38:06'),
(16, 12, 2, 1, 3, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 12:38:06'),
(17, 10, 2, 1, 3, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 12:38:06'),
(18, 8, 2, 1, 3, 2, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-22', 0, '2025-10-08 12:38:06'),
(19, 22, 10, 1, 23, 1, '2024-2025', 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, '2025-10-24', 0, '2025-10-10 03:23:31'),
(20, 11, 10, 1, 23, 1, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-24', 0, '2025-10-10 03:23:31'),
(21, 9, 10, 1, 23, 1, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-24', 0, '2025-10-10 03:23:31'),
(22, 12, 10, 1, 23, 1, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-24', 0, '2025-10-10 03:23:31'),
(23, 10, 10, 1, 23, 1, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-24', 0, '2025-10-10 03:23:31'),
(24, 8, 10, 1, 23, 1, '2024-2025', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-10-24', 0, '2025-10-10 03:23:31');

--
-- Bẫy `scores`
--
DELIMITER $$
CREATE TRIGGER `before_score_insert` BEFORE INSERT ON `scores` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_score_update` BEFORE UPDATE ON `scores` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `student_class_info`
-- (See below for the actual view)
--
CREATE TABLE `student_class_info` (
`student_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
,`class_name` varchar(50)
,`grade` int(11)
,`school_year` varchar(20)
,`homeroom_teacher` varchar(100)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`) VALUES
(1, 'Toán', 'MATH'),
(2, 'Ngữ Văn', 'LITERATURE'),
(3, 'Vật Lý', 'PHYSICS'),
(4, 'Hóa Học', 'CHEMISTRY'),
(5, 'Tiếng Anh', 'ENGLISH'),
(6, 'Lịch Sử', 'HISTORY'),
(7, 'Địa Lý', 'GEOGRAPHY'),
(8, 'Sinh Học', 'BIOLOGY'),
(9, 'Giáo Dục Công Dân', 'CIVIC_EDUCATION'),
(10, 'Công Nghệ', 'TECHNOLOGY'),
(11, 'Thể Dục', 'PHYSICAL_EDU'),
(12, 'Quốc Phòng', 'DEFENSE_EDU');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teaching_assignments`
--

CREATE TABLE `teaching_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT curdate(),
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `teaching_assignments`
--

INSERT INTO `teaching_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `semester`, `school_year`, `is_active`, `start_date`, `end_date`) VALUES
(1, 2, 1, 1, 1, '2024-2025', 1, '2025-10-13', NULL),
(2, 3, 1, 2, 1, '2024-2025', 1, '2025-10-13', NULL),
(3, 4, 1, 3, 1, '2024-2025', 1, '2025-10-13', NULL),
(4, 5, 1, 4, 1, '2024-2025', 1, '2025-10-13', NULL),
(5, 6, 1, 5, 1, '2024-2025', 1, '2025-10-13', NULL),
(6, 2, 2, 1, 1, '2024-2025', 1, '2025-10-13', NULL),
(7, 3, 2, 2, 1, '2024-2025', 1, '2025-10-13', NULL),
(8, 4, 2, 3, 1, '2024-2025', 1, '2025-10-13', NULL),
(9, 2, 3, 1, 1, '2024-2025', 1, '2025-10-13', NULL),
(10, 3, 3, 2, 1, '2024-2025', 1, '2025-10-13', NULL),
(11, 5, 3, 4, 1, '2024-2025', 1, '2025-10-13', NULL),
(12, 2, 5, 1, 1, '2024-2025', 1, '2025-10-13', NULL),
(13, 3, 5, 2, 1, '2024-2025', 1, '2025-10-13', NULL),
(14, 6, 5, 5, 1, '2024-2025', 1, '2025-10-13', NULL),
(16, 3, 1, 4, 2, '2024-2025', 1, '2025-10-14', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `teaching_assignment_details`
-- (See below for the actual view)
--
CREATE TABLE `teaching_assignment_details` (
`id` int(11)
,`teacher_id` int(11)
,`class_id` int(11)
,`subject_id` int(11)
,`semester` int(11)
,`school_year` varchar(20)
,`teacher_name` varchar(100)
,`class_name` varchar(50)
,`grade` int(11)
,`subject_name` varchar(100)
,`subject_code` varchar(20)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `teacher_type` enum('homeroom','subject') DEFAULT 'subject'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `phone`, `created_at`, `status`, `teacher_type`) VALUES
(1, 'admin', '123456', 'admin', 'admin', 'admin@thpt.edu.vn', '0901111111', '2025-10-05 14:10:13', 'active', 'subject'),
(2, 'gv_toan', '1234567', 'teacher', 'Trần Thị Toán', 'gv_toan@thpt.edu.vn', '0902222222', '2025-10-05 14:10:13', 'active', 'homeroom'),
(3, 'gv_van', '123456', 'teacher', 'Lê Văn Văn', 'gv_van@thpt.edu.vn', '0903333333', '2025-10-05 14:10:13', 'active', 'homeroom'),
(4, 'gv_ly', '123456', 'teacher', 'Phạm Vật Lý', 'gv_ly@thpt.edu.vn', '0904444444', '2025-10-05 14:10:13', 'active', 'homeroom'),
(5, 'gv_hoa', '123456', 'teacher', 'Hoàng Thị Hóa', 'gv_hoa@thpt.edu.vn', '0905555555', '2025-10-05 14:10:13', 'inactive', 'homeroom'),
(6, 'gv_anh', '123456', 'teacher', 'Vũ Tiếng Anh', 'gv_anh@thpt.edu.vn', '0906666666', '2025-10-05 14:10:13', 'active', 'homeroom'),
(7, 'gv_su', '123456', 'teacher', 'Ngô Văn Sử', 'gv_su@thpt.edu.vn', '0907777777', '2025-10-05 14:10:13', 'active', 'homeroom'),
(8, 'gv_dia', '123456', 'teacher', 'Đặng Thị Địa', 'gv_dia@thpt.edu.vn', '0908888888', '2025-10-05 14:10:13', 'active', 'subject'),
(9, 'hs_1001', '1234567', 'student', 'Nguyễn Văn An', 'an.nguyen@thpt.edu.vn', '0911111112', '2025-10-05 14:10:13', 'active', 'subject'),
(10, 'hs_1002', '123456', 'student', 'Trần Thị Bình', 'binh.tran@thpt.edu.vn', '0911111112', '2025-10-05 14:10:13', 'active', 'subject'),
(11, 'hs_1003', '123456', 'student', 'Lê Văn Cường', 'cuong.le@thpt.edu.vn', '0911111113', '2025-10-05 14:10:13', 'active', 'subject'),
(12, 'hs_1004', '123456', 'student', 'Phạm Thị Dung', 'dung.pham@thpt.edu.vn', '0911111114', '2025-10-05 14:10:13', 'active', 'subject'),
(13, 'hs_1005', '123456', 'student', 'Hoàng Văn Em', 'em.hoang@thpt.edu.vn', '0911111115', '2025-10-05 14:10:13', 'active', 'subject'),
(14, 'hs_1006', '123456', 'student', 'Vũ Thị Phương', 'phuong.vu@thpt.edu.vn', '0911111116', '2025-10-05 14:10:13', 'active', 'subject'),
(15, 'hs_1007', '123456', 'student', 'Ngô Văn Quân', 'quan.ngo@thpt.edu.vn', '0911111117', '2025-10-05 14:10:13', 'active', 'subject'),
(16, 'hs_1008', '123456', 'student', 'Đặng Thị Hương', 'huong.dang@thpt.edu.vn', '0911111118', '2025-10-05 14:10:13', 'active', 'subject'),
(17, 'hs_1101', '123456', 'student', 'Bùi Văn Minh', 'minh.bui@thpt.edu.vn', '0911111119', '2025-10-05 14:10:13', 'active', 'subject'),
(18, 'hs_1102', '123456', 'student', 'Đỗ Thị Ngọc', 'ngoc.do@thpt.edu.vn', '0911111120', '2025-10-05 14:10:13', 'active', 'subject'),
(19, 'hs_1201', '123456', 'student', 'Mai Văn Tài', 'tai.mai@thpt.edu.vn', '0911111121', '2025-10-05 14:10:13', 'active', 'subject'),
(20, 'hs_1202', '123456', 'student', 'Lý Thị Thảo', 'thao.ly@thpt.edu.vn', '0911111122', '2025-10-05 14:10:13', 'active', 'subject'),
(21, 'gv_gdcd', '$2y$10$bB7pqz8tJWn.pyqb56CSyefwd8M.yi5Xwz6q8MHaa9flXnOaU7HcS', 'teacher', 'Chin chin chan chan', 'chinchan@gmail.com', '0123456789', '2025-10-06 22:02:08', 'inactive', 'subject'),
(22, 'chinchan', '$2y$10$ZzFsr/AZOtoELKE/KylpBuhMh8hF1CkG4YSGzoV8EU36rSdTH5CGy', 'student', 'Chin chin chan chan', 'chinchan@gmail.com', '0123456789', '2025-10-10 03:05:33', 'active', 'subject'),
(23, 'wuanzchan', '123456', 'teacher', 'chan', 'chinchan@gmail.com', '0901111111', '2025-10-10 03:20:53', 'inactive', 'subject'),
(24, 'a', '123456', 'student', 'b', 'b@gmail.com', '0901111111', '2025-10-10 03:47:26', 'active', 'subject');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `class_average_scores`
--
DROP TABLE IF EXISTS `class_average_scores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `class_average_scores`  AS SELECT `c`.`class_name` AS `class_name`, `s`.`subject_name` AS `subject_name`, `sc`.`semester` AS `semester`, `sc`.`school_year` AS `school_year`, avg(`sc`.`average`) AS `class_average`, count(`sc`.`student_id`) AS `student_count` FROM ((`scores` `sc` join `classes` `c` on(`sc`.`class_id` = `c`.`id`)) join `subjects` `s` on(`sc`.`subject_id` = `s`.`id`)) WHERE `sc`.`average` is not null GROUP BY `c`.`class_name`, `s`.`subject_name`, `sc`.`semester`, `sc`.`school_year` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `student_class_info`
--
DROP TABLE IF EXISTS `student_class_info`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_class_info`  AS SELECT `u`.`id` AS `student_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `c`.`class_name` AS `class_name`, `c`.`grade` AS `grade`, `c`.`school_year` AS `school_year`, `hm`.`full_name` AS `homeroom_teacher` FROM (((`users` `u` join `class_students` `cs` on(`u`.`id` = `cs`.`student_id`)) join `classes` `c` on(`cs`.`class_id` = `c`.`id`)) left join `users` `hm` on(`c`.`homeroom_teacher_id` = `hm`.`id`)) WHERE `u`.`role` = 'student' AND `u`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `teaching_assignment_details`
--
DROP TABLE IF EXISTS `teaching_assignment_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `teaching_assignment_details`  AS SELECT `ta`.`id` AS `id`, `ta`.`teacher_id` AS `teacher_id`, `ta`.`class_id` AS `class_id`, `ta`.`subject_id` AS `subject_id`, `ta`.`semester` AS `semester`, `ta`.`school_year` AS `school_year`, `u`.`full_name` AS `teacher_name`, `c`.`class_name` AS `class_name`, `c`.`grade` AS `grade`, `s`.`subject_name` AS `subject_name`, `s`.`subject_code` AS `subject_code` FROM (((`teaching_assignments` `ta` join `users` `u` on(`ta`.`teacher_id` = `u`.`id`)) join `classes` `c` on(`ta`.`class_id` = `c`.`id`)) join `subjects` `s` on(`ta`.`subject_id` = `s`.`id`)) ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Chỉ mục cho bảng `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_view` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Uni_class_year` (`class_name`,`school_year`) USING BTREE,
  ADD KEY `homeroom_teacher_id` (`homeroom_teacher_id`);

--
-- Chỉ mục cho bảng `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_student` (`student_id`,`class_id`,`school_year`),
  ADD KEY `idx_class_students_student` (`student_id`),
  ADD KEY `idx_class_students_class` (`class_id`);

--
-- Chỉ mục cho bảng `homeroom_history`
--
ALTER TABLE `homeroom_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Chỉ mục cho bảng `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_materials_teacher` (`teacher_id`),
  ADD KEY `idx_materials_subject` (`subject_id`);

--
-- Chỉ mục cho bảng `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_score_entry` (`student_id`,`subject_id`,`class_id`,`semester`,`school_year`),
  ADD KEY `idx_scores_student` (`student_id`),
  ADD KEY `idx_scores_subject` (`subject_id`),
  ADD KEY `idx_scores_class` (`class_id`),
  ADD KEY `idx_scores_teacher` (`teacher_id`),
  ADD KEY `idx_scores_semester` (`semester`,`school_year`);

--
-- Chỉ mục cho bảng `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Chỉ mục cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_teaching_assignments_teacher` (`teacher_id`),
  ADD KEY `idx_teaching_assignments_class` (`class_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `class_students`
--
ALTER TABLE `class_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `homeroom_history`
--
ALTER TABLE `homeroom_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD CONSTRAINT `announcement_views_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `class_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Các ràng buộc cho bảng `homeroom_history`
--
ALTER TABLE `homeroom_history`
  ADD CONSTRAINT `homeroom_history_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `homeroom_history_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Các ràng buộc cho bảng `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `materials_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Các ràng buộc cho bảng `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `scores_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD CONSTRAINT `teaching_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teaching_assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `teaching_assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
