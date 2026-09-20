-- Drop existing tables
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `destinations`;

-- Create books table
CREATE TABLE `books` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `book_title` TEXT,
    `book_author` TEXT,
    `class_name` TEXT,
    `student_name` TEXT,
    `num_pages` INTEGER
);

-- Create destinations table
CREATE TABLE `destinations` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` TEXT,
    `order` INTEGER,
    `lat` TEXT,
    `lng` TEXT
);

-- Optional: add indices if needed
