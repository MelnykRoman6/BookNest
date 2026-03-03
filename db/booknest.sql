-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 08:12 AM
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
-- Database: `booknest`
--

-- --------------------------------------------------------

--
-- Table structure for table `aggiungere`
--

CREATE TABLE `aggiungere` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_collezione` int(11) NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appartenere`
--

CREATE TABLE `appartenere` (
  `id_libro` int(11) NOT NULL,
  `id_genere` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `autore`
--

CREATE TABLE `autore` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collezione`
--

CREATE TABLE `collezione` (
  `id` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_crea` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cronologia`
--

CREATE TABLE `cronologia` (
  `id` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `criterio_ricerca` varchar(255) DEFAULT NULL,
  `data_ricerca` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `download`
--

CREATE TABLE `download` (
  `id` int(11) NOT NULL,
  `id_formato` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `data_download` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formato`
--

CREATE TABLE `formato` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `tipo` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genere`
--

CREATE TABLE `genere` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `libro`
--

CREATE TABLE `libro` (
  `id` int(11) NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `lingua` varchar(20) DEFAULT NULL,
  `ia_id` varchar(100) DEFAULT NULL,
  `open_library_id` varchar(50) DEFAULT NULL,
  `cover_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recensione`
--

CREATE TABLE `recensione` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `commento` text DEFAULT NULL,
  `data_rec` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salvare`
--

CREATE TABLE `salvare` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `progresso` varchar(255) DEFAULT NULL,
  `stato` enum('da_leggere','in_lettura','letto') DEFAULT 'da_leggere',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scrivere`
--

CREATE TABLE `scrivere` (
  `id_libro` int(11) NOT NULL,
  `id_autore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utente`
--

CREATE TABLE `utente` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `data_reg` datetime DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aggiungere`
--
ALTER TABLE `aggiungere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_libro` (`id_libro`,`id_collezione`),
  ADD KEY `id_collezione` (`id_collezione`);

--
-- Indexes for table `appartenere`
--
ALTER TABLE `appartenere`
  ADD PRIMARY KEY (`id_libro`,`id_genere`),
  ADD KEY `id_genere` (`id_genere`);

--
-- Indexes for table `autore`
--
ALTER TABLE `autore`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collezione`
--
ALTER TABLE `collezione`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indexes for table `cronologia`
--
ALTER TABLE `cronologia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indexes for table `download`
--
ALTER TABLE `download`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_formato` (`id_formato`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indexes for table `formato`
--
ALTER TABLE `formato`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_libro` (`id_libro`);

--
-- Indexes for table `genere`
--
ALTER TABLE `genere`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `libro`
--
ALTER TABLE `libro`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recensione`
--
ALTER TABLE `recensione`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_libro` (`id_libro`,`id_utente`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indexes for table `salvare`
--
ALTER TABLE `salvare`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_libro` (`id_libro`,`id_utente`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indexes for table `scrivere`
--
ALTER TABLE `scrivere`
  ADD PRIMARY KEY (`id_libro`,`id_autore`),
  ADD KEY `id_autore` (`id_autore`);

--
-- Indexes for table `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aggiungere`
--
ALTER TABLE `aggiungere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `autore`
--
ALTER TABLE `autore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `collezione`
--
ALTER TABLE `collezione`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `cronologia`
--
ALTER TABLE `cronologia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `download`
--
ALTER TABLE `download`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `formato`
--
ALTER TABLE `formato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `genere`
--
ALTER TABLE `genere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `libro`
--
ALTER TABLE `libro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `recensione`
--
ALTER TABLE `recensione`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salvare`
--
ALTER TABLE `salvare`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aggiungere`
--
ALTER TABLE `aggiungere`
  ADD CONSTRAINT `aggiungere_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aggiungere_ibfk_2` FOREIGN KEY (`id_collezione`) REFERENCES `collezione` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appartenere`
--
ALTER TABLE `appartenere`
  ADD CONSTRAINT `appartenere_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appartenere_ibfk_2` FOREIGN KEY (`id_genere`) REFERENCES `genere` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `collezione`
--
ALTER TABLE `collezione`
  ADD CONSTRAINT `collezione_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cronologia`
--
ALTER TABLE `cronologia`
  ADD CONSTRAINT `cronologia_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `download`
--
ALTER TABLE `download`
  ADD CONSTRAINT `download_ibfk_1` FOREIGN KEY (`id_formato`) REFERENCES `formato` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `download_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `formato`
--
ALTER TABLE `formato`
  ADD CONSTRAINT `formato_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recensione`
--
ALTER TABLE `recensione`
  ADD CONSTRAINT `recensione_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recensione_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salvare`
--
ALTER TABLE `salvare`
  ADD CONSTRAINT `salvare_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salvare_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scrivere`
--
ALTER TABLE `scrivere`
  ADD CONSTRAINT `scrivere_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libro` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrivere_ibfk_2` FOREIGN KEY (`id_autore`) REFERENCES `autore` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
