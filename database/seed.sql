-- Brainware University Employee Subject Selection System Seed Data
-- 27 Official Academic Curriculum Subjects & HOD Administrator Account
-- (No dummy faculty or dummy submissions)

-- 1. Insert 27 Official Curriculum Subjects
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `active`) VALUES
(1, 'SUB-01', 'Database Management System', 1),
(2, 'SUB-02', 'Computer Network', 1),
(3, 'SUB-03', 'HTML, CSS and CSS Preprocessor', 1),
(4, 'SUB-04', 'Computer Organization', 1),
(5, 'SUB-05', 'Programming in C', 1),
(6, 'SUB-06', 'Operating System', 1),
(7, 'SUB-07', 'Software Project Management', 1),
(8, 'SUB-08', 'Cyber Security', 1),
(9, 'SUB-09', 'MOOC', 1),
(10, 'SUB-10', 'Data Structure and Algorithm', 1),
(11, 'SUB-11', 'Object Oriented Design using Java', 1),
(12, 'SUB-12', 'Machine Learning', 1),
(13, 'SUB-13', 'PHP with Laravel Lab', 1),
(14, 'SUB-14', 'Full-stack Development-I', 1),
(15, 'SUB-15', 'Data Warehousing and Data Mining', 1),
(16, 'SUB-16', 'Advance Java', 1),
(17, 'SUB-17', 'Python Programming', 1),
(18, 'SUB-18', 'E-Commerce Technologies', 1),
(19, 'SUB-19', 'IT Enabled Services and Entrepreneurship', 1),
(20, 'SUB-20', 'Cloud Computing', 1),
(21, 'SUB-21', 'Big Data Analytics', 1),
(22, 'SUB-22', 'Compiler Design', 1),
(23, 'SUB-23', 'Blockchain Technology', 1),
(24, 'SUB-24', 'Pattern Recognition', 1),
(25, 'SUB-25', 'Soft Computing', 1),
(26, 'SUB-26', 'Natural Language Processing', 1),
(27, 'SUB-27', 'Virtual Reality and Augmented Reality', 1);

-- 2. Insert HOD Administrator Account
-- Email: hod.css@brainwareuniversity.ac.in
-- Default password: 'gurudev' (also supports 'Admin@123')
INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`) VALUES
(1, 'Dr. Jayanta Aich (HOD)', 'hod.css@brainwareuniversity.ac.in', '$2y$10$G6CuNqExJIGkwupl5cxBA.dANW5Mexu2aQ/VgIn5BC/pgMC/WJ3US');
