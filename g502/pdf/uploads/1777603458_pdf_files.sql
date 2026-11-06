-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 01, 2026 at 02:44 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u584797177_dios1`
--

-- --------------------------------------------------------

--
-- Table structure for table `pdf_files`
--

CREATE TABLE `pdf_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(10) NOT NULL,
  `upload_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pdf_files`
--

INSERT INTO `pdf_files` (`id`, `file_name`, `file_path`, `file_type`, `upload_date`) VALUES
(9, 'g502_poster_2x2 (1).pdf', 'pdfdb/g502_poster_2x2 (1).pdf', '', '2026-03-17 02:10:10'),
(10, 'g502_poster_2x2 (1).pdf', 'pdfdb/g502_poster_2x2 (1).pdf', '', '2026-03-17 02:12:49'),
(11, 'g502_poster_2x2 (1).pdf', 'pdfdb/g502_poster_2x2 (1).pdf', '', '2026-03-17 02:17:12'),
(12, 'g502_poster_2x2 (1).pdf', 'pdfdb/g502_poster_2x2 (1).pdf', '', '2026-03-17 02:18:08'),
(13, '18666.pdf', 'pdfdb/18666.pdf', '', '2026-03-18 12:49:09'),
(14, 'Cartas_V_DIAS_TEMATICOS_CARULLA.pdf', 'pdfdb/Cartas_V_DIAS_TEMATICOS_CARULLA.pdf', '', '2026-03-18 14:05:03'),
(15, 'VERDFAD.pdf', 'pdfdb/VERDFAD.pdf', '', '2026-03-20 20:18:01'),
(16, '19234.pdf', 'pdfdb/19234.pdf', '', '2026-03-20 20:20:51'),
(17, 'VERDFAD.pdf', 'pdfdb/VERDFAD.pdf', '', '2026-03-20 20:20:58'),
(18, '19236.pdf', 'pdfdb/19236.pdf', '', '2026-03-20 20:24:04'),
(19, 'VERDFAD.pdf', 'pdfdb/VERDFAD.pdf', '', '2026-03-20 20:24:11'),
(20, '21653__1_.pdf', 'pdfdb/21653__1_.pdf', '', '2026-04-10 14:22:20'),
(21, 'cerdoo.pdf', 'pdfdb/cerdoo.pdf', '', '2026-04-18 12:48:33'),
(22, 'POLLOO.pdf', 'pdfdb/POLLOO.pdf', '', '2026-04-18 12:48:42'),
(23, 'POLLOO.pdf', 'pdfdb/POLLOO.pdf', '', '2026-04-18 12:49:11'),
(24, 'donaaaaarta AA.pdf', 'pdfdb/donaaaaarta AA.pdf', '', '2026-04-18 15:20:34'),
(25, 'Tabloide horiza.pdf', 'pdfdb/Tabloide horiza.pdf', '', '2026-04-18 18:37:25'),
(26, 'SHAMPO.pdf', 'pdfdb/SHAMPO.pdf', '', '2026-04-18 18:37:33'),
(27, '2DAM.pdf', 'pdfdb/2DAM.pdf', '', '2026-04-18 18:38:38'),
(28, 'so_big.pdf', 'pdfdb/so_big.pdf', '', '2026-04-18 18:38:56'),
(29, 'MV5BNDg1ZWE5MmYtZGU5YS00MzU2LThjMjktYThiNmVmMmZjMDkxXkEyXkFqcGdeQXVyMTU3ODE5NzYy._V1_.jpg', 'uploads/1777602939_MV5BNDg1ZWE5MmYtZGU5YS00MzU2LThjMjktYThiNmVmMmZjMDkxXkEyXkFqcGdeQXVyMTU3ODE5NzYy._V1_.jpg', 'jpg', '2026-05-01 02:35:39'),
(30, 'WhatsApp Image 2026-03-21 at 11.16.33 PM.jpeg', 'uploads/1777603257_WhatsApp_Image_2026-03-21_at_11.16.33_PM.jpeg', 'jpeg', '2026-05-01 02:40:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pdf_files`
--
ALTER TABLE `pdf_files`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pdf_files`
--
ALTER TABLE `pdf_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
