--
-- Dumping data for table `posts`
--

INSERT INTO `users` (`id`, `student_id`, `username`, `email`, `password`, `bio`, `profile_pic`, `created_at`, `profile_image`) VALUES
(1, '250161', 'admin', '250161gd@yse-c.net', '$2y$10$z4954b.Euqs6PVkIzl96Q.2HonFvkW8XrevZMzh3JMa1WTfcsGpXi', 'hello we are linker group in YSE!!', 'uploads/AVATAR_6a5061e0d99450.32494110.jpg', '2026-07-10 02:29:44', 'uploads/1783661673_2b2a3a46-be9d-472a-91e4-76289db72039.jpg'),
(2, '250118', 'nan', '250118yd@yse-c.net', '$2y$10$21Hx6NBeHvZBAiv2EEamXOpZh7ZX4w2SA.VXcTKy4v9/TlkyrW6mW', 'Hello i m nan shwe yee phoo thit . from GS21.\r\ni m SINGLE', 'default.png', '2026-07-10 04:59:52', 'uploads/1783661205_A short history of the Willem Dafoe Looking Up meme.jpg');

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(7, 1, 1, '2026-07-10 04:35:48'),
(17, 1, 3, '2026-07-10 04:40:56'),
(18, 1, 2, '2026-07-10 04:55:12'),
(19, 1, 5, '2026-07-10 05:49:09'),
(20, 1, 4, '2026-09-14 03:10:55');

INSERT INTO `posts` (`id`, `user_id`, `caption`, `image_path`, `created_at`) VALUES
(1, 1, 'ps5 days', 'uploads/POST_6a505d69e25010.39821381.jpg', '2026-07-10 02:48:09'),
(2, 1, 'bag', 'uploads/POST_6a505d851456f9.05062288.jpg', '2026-07-10 02:48:37'),
(3, 1, 'box jbl', 'uploads/POST_6a505ef6a2e592.11585255.jpg', '2026-07-10 02:54:46'),
(4, 1, 'lamp', 'uploads/POST_6a505f12af9c97.85722546.jpg', '2026-07-10 02:55:14'),
(5, 2, 'still studying', 'uploads/POST_6a507d6ca94533.12612915.jpg', '2026-07-10 05:04:44');

INSERT INTO `comments` (`id`, `user_id`, `post_id`, `comment_text`, `created_at`) VALUES
(1, 1, 3, 'how much it', '2026-07-10 03:18:40'),
(2, 1, 3, 'i dont know', '2026-07-10 04:36:35'),
(3, 1, 3, 'i want it', '2026-07-10 04:42:01'),
(4, 1, 5, 'wawergltr', '2026-07-10 05:49:19'),
(5, 1, 5, 'its no', '2026-09-14 03:11:19');