-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 04:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `Author_ID` int(11) NOT NULL,
  `Author_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `Book_ID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Published_Year` int(11) DEFAULT NULL CHECK (`Published_Year` >= 1000),
  `ISBN` varchar(13) NOT NULL,
  `Total_Loaned` int(11) DEFAULT 0 CHECK (`Total_Loaned` >= 0),
  `Total_available` int(11) DEFAULT 0 CHECK (`Total_available` >= 0)
) ;

--
-- Triggers `books`
--
DELIMITER $$
CREATE TRIGGER `check_published_year` BEFORE INSERT ON `books` FOR EACH ROW BEGIN
    IF NEW.Published_Year > YEAR(CURRENT_DATE()) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Published_Year cannot be in the future';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `Category_ID` int(11) NOT NULL,
  `Category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genre`
--

CREATE TABLE `genre` (
  `Book_ID` int(11) NOT NULL,
  `Category_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `Member_ID` int(11) NOT NULL,
  `Member_Name` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Phone_Number` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Date_of_Birth` date DEFAULT NULL,
  `Registration_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Membership_Expiry` date DEFAULT NULL,
  `Max_Books_Allowed` int(11) DEFAULT 5 CHECK (`Max_Books_Allowed` >= 0),
  `Current_Loans` int(11) DEFAULT 0 CHECK (`Current_Loans` >= 0),
  `Total_Fines` decimal(10,2) DEFAULT 0.00 CHECK (`Total_Fines` >= 0),
  `Membership_Status` enum('Active','Suspended','Inactive','Expired') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `Staff_ID` int(11) NOT NULL,
  `Staff_Name` varchar(255) NOT NULL,
  `Staff_Role` enum('Librarian','Assistant','Admin','Manager','System_Admin') NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password_Hash` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Phone_Number` varchar(20) DEFAULT NULL,
  `Hire_Date` date DEFAULT NULL,
  `Last_Login` timestamp NULL DEFAULT NULL,
  `Is_Active` tinyint(1) DEFAULT 1,
  `Failed_Login_Attempts` int(11) DEFAULT 0 CHECK (`Failed_Login_Attempts` >= 0),
  `Account_Locked_Until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `staff`
--
DELIMITER $$
CREATE TRIGGER `hash_staff_password` BEFORE INSERT ON `staff` FOR EACH ROW BEGIN
  -- In practice, hash passwords in application layer, not SQL
  -- This is just to demonstrate the concept
  IF NEW.Password_Hash NOT LIKE '$2y$%' THEN  -- Simple check for bcrypt pattern
    -- In real system, password should be hashed BEFORE insertion
    SET NEW.Password_Hash = 'placeholder-hash';  
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `writers`
--

CREATE TABLE `writers` (
  `Book_ID` int(11) NOT NULL,
  `Author_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`Author_ID`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`Book_ID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`Category_ID`),
  ADD UNIQUE KEY `Category_name` (`Category_name`);

--
-- Indexes for table `genre`
--
ALTER TABLE `genre`
  ADD PRIMARY KEY (`Book_ID`,`Category_ID`),
  ADD KEY `Category_ID` (`Category_ID`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`Member_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone_Number` (`Phone_Number`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`Staff_ID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone_Number` (`Phone_Number`);

--
-- Indexes for table `writers`
--
ALTER TABLE `writers`
  ADD PRIMARY KEY (`Book_ID`,`Author_ID`),
  ADD KEY `Author_ID` (`Author_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `Author_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `Book_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `Member_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `Staff_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `genre`
--
ALTER TABLE `genre`
  ADD CONSTRAINT `genre_ibfk_1` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `genre_ibfk_2` FOREIGN KEY (`Category_ID`) REFERENCES `categories` (`Category_ID`) ON DELETE CASCADE;

--
-- Constraints for table `writers`
--
ALTER TABLE `writers`
  ADD CONSTRAINT `writers_ibfk_1` FOREIGN KEY (`Book_ID`) REFERENCES `books` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `writers_ibfk_2` FOREIGN KEY (`Author_ID`) REFERENCES `authors` (`Author_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
