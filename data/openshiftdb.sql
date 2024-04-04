-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2024 at 01:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `openshiftdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `clusterlist`
--

CREATE TABLE `clusterlist` (
  `dc` varchar(3) NOT NULL,
  `cluster` varchar(24) NOT NULL,
  `typeName` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `clusterlist`
--

INSERT INTO `clusterlist` (`dc`, `cluster`, `typeName`) VALUES
('BLR', 'blocp-pclus-01', 'PROD'),
('BLR', 'blocp-pclus-02', 'PROD'),
('BLR', 'blocp-pclus-03', 'PROD'),
('BLR', 'blpl-drclus01', 'PROD'),
('N1', 'dartclus01n1', 'PROD'),
('MN', 'mnocp-pclus01', 'PROD'),
('MN', 'mnocp-pclus02', 'PROD'),
('N1', 'n1ocp-dclus-01', 'DMZ'),
('N1', 'n1ocp-dclus-02', 'DMZ'),
('N1', 'n1ocp-dclus-03', 'DMZ'),
('N1', 'n1ocp-pclus-01', 'PROD'),
('N1', 'n1ocp-pclus-02', 'PROD'),
('N1', 'n1ocp-pclus-03', 'PROD'),
('N1', 'n1ocp-pclus-04', 'PROD'),
('N1', 'n1ocp-pclus-05', 'PROD'),
('N1', 'n1ocp-pclus-06', 'PROD'),
('N1', 'n1ocp-sclus-01', 'Staging'),
('N1', 'n1ocp-tclus-01', 'T&D'),
('N2', 'n2ocp-dart-tclus-01', 'T&D'),
('N2', 'n2ocp-dclus-02', 'DMZ'),
('N2', 'n2ocp-dclus-03', 'DMZ'),
('N2', 'n2ocp-pclus-02', 'PROD'),
('N2', 'n2ocp-pclus-03', 'PROD'),
('N2', 'n2ocp-pclus-04', 'PROD'),
('N2', 'n2ocp-pclus-05', 'PROD'),
('N2', 'n2ocp-pclus-06', 'PROD'),
('N2', 'n2ocp-pclus-mgmnt', 'PROD'),
('N2', 'n2ocp-tclus-01', 'T&D'),
('PU', 'puocp-dart-pclus-01', 'PROD'),
('PU', 'puocp-pclus-02', 'PROD');

-- --------------------------------------------------------

--
-- Table structure for table `dclist`
--

CREATE TABLE `dclist` (
  `dc` varchar(3) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL CHECK (`dc` regexp '^[A-Z0-9]{2,3}$')
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dclist`
--

INSERT INTO `dclist` (`dc`) VALUES
('BLR'),
('MN'),
('N1'),
('N2'),
('PU'),
('RA');

-- --------------------------------------------------------

--
-- Table structure for table `projectlist`
--

CREATE TABLE `projectlist` (
  `dc` varchar(3) NOT NULL,
  `typeName` varchar(10) NOT NULL,
  `cluster` varchar(24) NOT NULL,
  `project` varchar(64) NOT NULL,
  `emailAddresses` text DEFAULT NULL,
  `numOfProdAvailability` int(11) DEFAULT NULL,
  `numOfDMZAvailability` int(11) DEFAULT NULL,
  `numOfSingleOrFailedReplicas` int(11) DEFAULT NULL,
  `numWithMultipleReplicas` int(11) DEFAULT NULL,
  `numOfMissingHPAs` int(11) DEFAULT NULL,
  `numOfMissingAntiPodAffinities` int(11) DEFAULT NULL,
  `numOfMissingRequiredAntiPodAffinities` int(11) DEFAULT NULL,
  `numOfMissingLivenessProbes` int(11) DEFAULT NULL,
  `numOfMissingReadinessProbes` int(11) DEFAULT NULL,
  `numOfMissingStartupProbes` int(11) DEFAULT NULL,
  `numOfStatefulsets` int(11) DEFAULT NULL,
  `numOf3rdPartyImages` int(11) DEFAULT NULL,
  `numOfExternalImages` int(11) DEFAULT NULL,
  `numOfMissingIfNotPresentImagePullPolicies` int(11) DEFAULT NULL,
  `lastUpdated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `projectlist`
--

INSERT INTO `projectlist` (`dc`, `typeName`, `cluster`, `project`, `emailAddresses`, `numOfProdAvailability`, `numOfDMZAvailability`, `numOfSingleOrFailedReplicas`, `numWithMultipleReplicas`, `numOfMissingHPAs`, `numOfMissingAntiPodAffinities`, `numOfMissingRequiredAntiPodAffinities`, `numOfMissingLivenessProbes`, `numOfMissingReadinessProbes`, `numOfMissingStartupProbes`, `numOfStatefulsets`, `numOf3rdPartyImages`, `numOfExternalImages`, `numOfMissingIfNotPresentImagePullPolicies`, `lastUpdated`) VALUES
('N1', 'DMZ', 'dartclus01n1', 'mint', 'mint@airtel.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('N2', 'PROD', 'n2ocp-pclus-02', 'iris', 'iris@airtel.com, asdsda@airtel.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `typelist`
--

CREATE TABLE `typelist` (
  `typeName` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `typelist`
--

INSERT INTO `typelist` (`typeName`) VALUES
('DMZ'),
('Others'),
('PROD'),
('Staging'),
('T&D');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clusterlist`
--
ALTER TABLE `clusterlist`
  ADD PRIMARY KEY (`cluster`),
  ADD KEY `typeName` (`typeName`),
  ADD KEY `dc` (`dc`);

--
-- Indexes for table `dclist`
--
ALTER TABLE `dclist`
  ADD PRIMARY KEY (`dc`);

--
-- Indexes for table `projectlist`
--
ALTER TABLE `projectlist`
  ADD PRIMARY KEY (`cluster`,`project`),
  ADD KEY `dc` (`dc`),
  ADD KEY `typeName` (`typeName`);

--
-- Indexes for table `typelist`
--
ALTER TABLE `typelist`
  ADD PRIMARY KEY (`typeName`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clusterlist`
--
ALTER TABLE `clusterlist`
  ADD CONSTRAINT `clusterlist_ibfk_1` FOREIGN KEY (`typeName`) REFERENCES `typelist` (`typeName`),
  ADD CONSTRAINT `clusterlist_ibfk_2` FOREIGN KEY (`dc`) REFERENCES `dclist` (`dc`);

--
-- Constraints for table `projectlist`
--
ALTER TABLE `projectlist`
  ADD CONSTRAINT `projectlist_ibfk_1` FOREIGN KEY (`dc`) REFERENCES `dclist` (`dc`),
  ADD CONSTRAINT `projectlist_ibfk_2` FOREIGN KEY (`typeName`) REFERENCES `typelist` (`typeName`),
  ADD CONSTRAINT `projectlist_ibfk_3` FOREIGN KEY (`cluster`) REFERENCES `clusterlist` (`cluster`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
