-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 09, 2016 at 06:35 PM
-- Server version: 5.7.13-0ubuntu0.16.04.2
-- PHP Version: 7.0.8-0ubuntu0.16.04.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE LetsTestDaMo;
USE LetsTestDaMo;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LetsTestDaMo`
--

-- --------------------------------------------------------

--
-- Table structure for table `UserInformation`
--

CREATE TABLE `UserInformation` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(32) NOT NULL,
  `password` varchar(16) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` date NOT NULL,
  `profile_pic` int(11) DEFAULT NULL,
  `max_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `UserInformation`
--

INSERT INTO `UserInformation` (`user_id`, `user_name`, `password`, `first_name`, `last_name`, `birth_date`, `profile_pic`, `max_score`) VALUES
(0, 'skaterboy18', 'afdfda82313!', 'John', 'Sommerson', '1997-06-09', 1, 32656),
(2, 'traillady26!', 'teheg8452$$', 'Sarah', 'Stevens', '1989-04-17', 2, 89659),
(3, 'surferguy54!', 'uhjier343&', 'Joseph', 'Galvin', '1961-06-16', 3, 6526),
(4, 'footballjoc21', 'latreltoms236!', 'Latrelle', 'Thomas', '1993-12-09', 4, 76542),
(5, 'cheeringgirl17', 'yoliehocs9256!', 'Joanna', 'Heathberg', '1998-03-17', 5, 12563);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `UserInformation`
--
ALTER TABLE `UserInformation`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `profile_pic` (`profile_pic`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `UserInformation`
--

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
