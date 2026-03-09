# Deployment Guide

AWS Employee Management System

---

# 1. Overview

This document describes the steps required to deploy the AWS Employee Management System architecture.

The deployment includes:

* Network infrastructure
* Compute resources
* Database services
* Storage services
* Serverless components
* Monitoring configuration

---

# 2. VPC Setup

Create a custom VPC.

CIDR Block:

10.0.0.0/16

---

# 3. Subnet Creation

Create three subnets:

Public Subnet
Private Application Subnet
Private Database Subnet

Example CIDR:

Public Subnet – 10.0.208.0/20
Private App Subnet – 10.0.144.0/20
Private DB Subnet – 10.0.192.0/20

---

# 4. Internet Gateway

Create an Internet Gateway and attach it to the VPC.

Update the Public Route Table:

0.0.0.0/0 → Internet Gateway

---

# 5. NAT Gateway

Allocate an Elastic IP.

Create NAT Gateway inside the Public Subnet.

Update Private Route Table:

0.0.0.0/0 → NAT Gateway

This allows private resources to access the internet securely.

---

# 6. Bastion Host Setup

Launch EC2 instance in Public Subnet.

Configuration:

AMI: Ubuntu
Instance Type: t2.micro

Associate an Elastic IP.

Security Group:

Allow SSH from administrator IP.

This instance is used to access private instances securely.

---

# 7. Private EC2 Application Server

Launch EC2 instance in Private App Subnet.

No public IP assigned.

Security Group rules:

Allow SSH from Bastion Host
Allow HTTP from Application Load Balancer

---

# 8. Application Installation

SSH into Bastion Host and then into Private EC2.

Install packages:

sudo apt update
sudo apt install apache2
sudo apt install php8.1
sudo apt install mysql-client

Install Composer and AWS PHP SDK.

Deploy application code to:

/var/www/html

---

# 9. RDS Database

Create Amazon RDS MySQL instance.

Configuration:

Engine: MySQL
Public Access: Disabled

Allow MySQL access only from EC2 security group.

---

# 10. Application Load Balancer

Create an Application Load Balancer.

Configuration:

Scheme: Internet-facing
Listener: HTTP (80)

Create Target Group and register EC2 instances.

---

# 11. Auto Scaling Group

Create Launch Template.

Create Auto Scaling Group using template.

Attach Auto Scaling Group to ALB Target Group.

---

# 12. S3 Bucket

Create S3 bucket to store employee images.

Enable:

Block Public Access.

---

# 13. DynamoDB

Create DynamoDB table for storing image metadata.

Primary key:

EmployeeID

---

# 14. Lambda Function

Create Lambda function triggered by S3 object upload.

Function stores metadata in DynamoDB and triggers SNS notification.

---

# 15. SNS Notification

Create SNS topic.

Subscribe using email endpoint.

Lambda publishes message to SNS when image upload occurs.

---

# 16. Route 53

Create hosted zone.

Create A record pointing to Application Load Balancer.

---

# 17. CloudWatch Monitoring

Enable monitoring for:

EC2
RDS
Lambda
Application Load Balancer

Logs and metrics are available through CloudWatch.

---

# Deployment Completed

The Employee Management System is now accessible via the Application Load Balancer endpoint.
