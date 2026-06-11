-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: eticaret
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `kart_bilgileri`
--

DROP TABLE IF EXISTS `kart_bilgileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kart_bilgileri` (
  `kullanici_id` int(11) NOT NULL,
  `kart_no` varchar(20) NOT NULL,
  `cvv` varchar(5) DEFAULT NULL,
  `son_kullanma_tarihi` date DEFAULT NULL,
  PRIMARY KEY (`kullanici_id`,`kart_no`),
  CONSTRAINT `kart_bilgileri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanici` (`kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kart_bilgileri`
--

LOCK TABLES `kart_bilgileri` WRITE;
/*!40000 ALTER TABLE `kart_bilgileri` DISABLE KEYS */;
INSERT INTO `kart_bilgileri` VALUES (1,'1234567812345678','123','2027-12-01');
/*!40000 ALTER TABLE `kart_bilgileri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori` (
  `kategori_id` int(11) NOT NULL,
  `kategori_adi` varchar(100) DEFAULT NULL,
  `ust_kategori_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`kategori_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES (1,'Elektronik',NULL),(2,'Aksesuar',NULL);
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_urun`
--

DROP TABLE IF EXISTS `kategori_urun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori_urun` (
  `kategori_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  PRIMARY KEY (`kategori_id`,`urun_id`),
  KEY `urun_id` (`urun_id`),
  CONSTRAINT `kategori_urun_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`kategori_id`),
  CONSTRAINT `kategori_urun_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urun` (`urun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_urun`
--

LOCK TABLES `kategori_urun` WRITE;
/*!40000 ALTER TABLE `kategori_urun` DISABLE KEYS */;
INSERT INTO `kategori_urun` VALUES (1,1),(2,2);
/*!40000 ALTER TABLE `kategori_urun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kullanici`
--

DROP TABLE IF EXISTS `kullanici`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kullanici` (
  `kullanici_id` int(11) NOT NULL,
  `kullanici_adi` varchar(100) DEFAULT NULL,
  `kullanici_soyadi` varchar(100) DEFAULT NULL,
  `kullanici_sifre` varchar(100) DEFAULT NULL,
  `kullanici_eposta` varchar(100) DEFAULT NULL,
  `kullanici_tel` varchar(20) DEFAULT NULL,
  `kullanici_adres` text DEFAULT NULL,
  PRIMARY KEY (`kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanici`
--

LOCK TABLES `kullanici` WRITE;
/*!40000 ALTER TABLE `kullanici` DISABLE KEYS */;
INSERT INTO `kullanici` VALUES (1,'Enes','Çavlanlar','123456','enes@mail.com','05551234567','İstanbul, Türkiye'),(2,'Ayşe','Yılmaz','abcdef','ayse@mail.com','05557654321','Ankara, Türkiye');
/*!40000 ALTER TABLE `kullanici` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `satici`
--

DROP TABLE IF EXISTS `satici`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `satici` (
  `satici_id` int(11) NOT NULL,
  `satici_adi` varchar(100) DEFAULT NULL,
  `satici_adres` text DEFAULT NULL,
  `satici_eposta` varchar(100) DEFAULT NULL,
  `satici_no` varchar(20) DEFAULT NULL,
  `satici_bakiye` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`satici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `satici`
--

LOCK TABLES `satici` WRITE;
/*!40000 ALTER TABLE `satici` DISABLE KEYS */;
/*!40000 ALTER TABLE `satici` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sepet`
--

DROP TABLE IF EXISTS `sepet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sepet` (
  `sepet_id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`sepet_id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `sepet_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanici` (`kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sepet`
--

LOCK TABLES `sepet` WRITE;
/*!40000 ALTER TABLE `sepet` DISABLE KEYS */;
INSERT INTO `sepet` VALUES (1,1);
/*!40000 ALTER TABLE `sepet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sepet_urun`
--

DROP TABLE IF EXISTS `sepet_urun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sepet_urun` (
  `sepet_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `urun_miktar` int(11) DEFAULT NULL,
  PRIMARY KEY (`sepet_id`,`urun_id`),
  KEY `urun_id` (`urun_id`),
  CONSTRAINT `sepet_urun_ibfk_1` FOREIGN KEY (`sepet_id`) REFERENCES `sepet` (`sepet_id`),
  CONSTRAINT `sepet_urun_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urun` (`urun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sepet_urun`
--

LOCK TABLES `sepet_urun` WRITE;
/*!40000 ALTER TABLE `sepet_urun` DISABLE KEYS */;
INSERT INTO `sepet_urun` VALUES (1,1,1);
/*!40000 ALTER TABLE `sepet_urun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siparis`
--

DROP TABLE IF EXISTS `siparis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `siparis` (
  `siparis_id` int(11) NOT NULL,
  `sepet_id` int(11) DEFAULT NULL,
  `siparis_durumu` varchar(50) DEFAULT NULL,
  `toplam_tutar` decimal(10,2) DEFAULT NULL,
  `siparis_tarihi` date DEFAULT NULL,
  PRIMARY KEY (`siparis_id`),
  KEY `sepet_id` (`sepet_id`),
  CONSTRAINT `siparis_ibfk_1` FOREIGN KEY (`sepet_id`) REFERENCES `sepet` (`sepet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siparis`
--

LOCK TABLES `siparis` WRITE;
/*!40000 ALTER TABLE `siparis` DISABLE KEYS */;
INSERT INTO `siparis` VALUES (1,1,'Hazırlanıyor',19999.90,'2025-05-03');
/*!40000 ALTER TABLE `siparis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siparis_detay`
--

DROP TABLE IF EXISTS `siparis_detay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `siparis_detay` (
  `siparis_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `urun_miktar` int(11) DEFAULT NULL,
  PRIMARY KEY (`siparis_id`,`urun_id`),
  KEY `urun_id` (`urun_id`),
  CONSTRAINT `siparis_detay_ibfk_1` FOREIGN KEY (`siparis_id`) REFERENCES `siparis` (`siparis_id`),
  CONSTRAINT `siparis_detay_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urun` (`urun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siparis_detay`
--

LOCK TABLES `siparis_detay` WRITE;
/*!40000 ALTER TABLE `siparis_detay` DISABLE KEYS */;
INSERT INTO `siparis_detay` VALUES (1,1,1);
/*!40000 ALTER TABLE `siparis_detay` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `urun`
--

DROP TABLE IF EXISTS `urun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `urun` (
  `urun_id` int(11) NOT NULL,
  `urun_adi` varchar(100) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `resim_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`urun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `urun`
--

LOCK TABLES `urun` WRITE;
/*!40000 ALTER TABLE `urun` DISABLE KEYS */;
INSERT INTO `urun` VALUES (1,'Laptop','Yüksek performanslı dizüstü bilgisayar',NULL),(2,'Kulaklık','Kablosuz bluetooth kulaklık',NULL);
/*!40000 ALTER TABLE `urun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `urun_satici`
--

DROP TABLE IF EXISTS `urun_satici`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `urun_satici` (
  `urun_id` int(11) NOT NULL,
  `satici_id` int(11) NOT NULL,
  `urun_fiyat` decimal(10,2) DEFAULT NULL,
  `stok_miktar` int(11) DEFAULT NULL,
  PRIMARY KEY (`urun_id`,`satici_id`),
  KEY `satici_id` (`satici_id`),
  CONSTRAINT `urun_satici_ibfk_1` FOREIGN KEY (`urun_id`) REFERENCES `urun` (`urun_id`),
  CONSTRAINT `urun_satici_ibfk_2` FOREIGN KEY (`satici_id`) REFERENCES `satici` (`satici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `urun_satici`
--

LOCK TABLES `urun_satici` WRITE;
/*!40000 ALTER TABLE `urun_satici` DISABLE KEYS */;
/*!40000 ALTER TABLE `urun_satici` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yorum`
--

DROP TABLE IF EXISTS `yorum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yorum` (
  `yorum_id` int(11) NOT NULL,
  `puan` int(11) DEFAULT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `yorum_tarihi` date DEFAULT NULL,
  `yorum_icerigi` text DEFAULT NULL,
  `urun_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`yorum_id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `urun_id` (`urun_id`),
  CONSTRAINT `yorum_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanici` (`kullanici_id`),
  CONSTRAINT `yorum_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urun` (`urun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yorum`
--

LOCK TABLES `yorum` WRITE;
/*!40000 ALTER TABLE `yorum` DISABLE KEYS */;
/*!40000 ALTER TABLE `yorum` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-11 22:15:15
