-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 2019-08-16 08:13:13
-- 服务器版本： 5.6.44
-- PHP Version: 7.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `book`
--

-- --------------------------------------------------------

--
-- 表的结构 `book_dir`
--

CREATE TABLE `book_dir` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `url` varchar(120) NOT NULL,
  `time` int(11) NOT NULL,
  `in_stat` tinyint(4) NOT NULL COMMENT '1代表下面有正文内容了'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `book_name`
--

CREATE TABLE `book_name` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `time` int(11) NOT NULL,
  `up_time` int(11) NOT NULL,
  `url` varchar(120) NOT NULL COMMENT '目标DIR',
  `num` int(11) NOT NULL COMMENT '最新数据条数',
  `onclick_num` int(11) NOT NULL COMMENT '本书点击人数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `book_text`
--

CREATE TABLE `book_text` (
  `id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `dir_id` int(11) DEFAULT NULL,
  `text` mediumtext NOT NULL,
  `time` int(11) NOT NULL,
  `num` int(11) NOT NULL COMMENT '当前点击数量'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book_dir`
--
ALTER TABLE `book_dir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `book_name`
--
ALTER TABLE `book_name`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `book_text`
--
ALTER TABLE `book_text`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book` (`book_id`,`dir_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `book_dir`
--
ALTER TABLE `book_dir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `book_name`
--
ALTER TABLE `book_name`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `book_text`
--
ALTER TABLE `book_text`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
