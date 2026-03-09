CREATE DATABASE employee_db;

USE employee_db;

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    technology VARCHAR(100) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
);

SHOW TABLES;

INSERT INTO employees (name, location, technology, salary, image_url) 
VALUES 
('John Doe', 'New York', 'AWS', 75000.00, 'https://s3.amazonaws.com/my-xyz-company-bucket/john_doe.jpg'),
('Alice Smith', 'San Francisco', 'Python', 90000.00, 'https://s3.amazonaws.com/my-xyz-company-bucket/alice_smith.jpg'),
('Bob Johnson', 'Seattle', 'DevOps', 85000.00, 'https://s3.amazonaws.com/my-xyz-company-bucket/bob_johnson.jpg');


SELECT * FROM employees;
