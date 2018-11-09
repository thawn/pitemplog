CREATE DATABASE temperatures;
CREATE USER 'temp'@'localhost' IDENTIFIED BY 'temp';
GRANT ALL ON temperatures.* TO 'temp'@'localhost';
