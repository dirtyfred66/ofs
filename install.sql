CREATE TABLE `ofs_blocks` (
  `block_id` int NOT NULL,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ofs_blocks`
  ADD PRIMARY KEY (`block_id`);

ALTER TABLE `ofs_blocks`
  MODIFY `block_id` int NOT NULL AUTO_INCREMENT;

CREATE TABLE `ofs_options` (
  `option_id` bigint NOT NULL,
  `option_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ofs_options`
  ADD PRIMARY KEY (`option_id`);

ALTER TABLE `ofs_options`
  MODIFY `option_id` bigint NOT NULL AUTO_INCREMENT;

CREATE TABLE `ofs_posts` (
  `id` int NOT NULL,
  `posted` timestamp NULL DEFAULT NULL,
  `post_id` int NOT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `separated` int DEFAULT NULL,
  `profile_id` int NOT NULL,
  `profile_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_username` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usernames` json NOT NULL,
  `price` float NOT NULL,
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photos` int DEFAULT NULL,
  `videos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ofs_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

ALTER TABLE `ofs_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

CREATE TABLE `ofs_profiles` (
  `profile_id` bigint NOT NULL,
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `done` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ofs_profiles`
  ADD PRIMARY KEY (`profile_id`);

ALTER TABLE `ofs_profiles`
  MODIFY `profile_id` bigint NOT NULL AUTO_INCREMENT;
