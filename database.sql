-- ============================================================
-- ISP Management System - Database Schema
-- Import this file in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS isp_system;
USE isp_system;

-- ============================================================
-- 1. CUSTOMER
-- ============================================================
CREATE TABLE Customer (
    customer_id   INT           AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(100)  UNIQUE NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    phone         VARCHAR(20),
    street        VARCHAR(150),
    city          VARCHAR(60),
    status        VARCHAR(20)   DEFAULT 'active',
    created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. PLAN
-- ============================================================
CREATE TABLE Plan (
    plan_id            INT            AUTO_INCREMENT PRIMARY KEY,
    plan_name          VARCHAR(100)   NOT NULL,
    price_monthly      DECIMAL(10,2)  NOT NULL,
    bandwidth_limit_gb INT            NOT NULL,
    speed_mbps         VARCHAR(50)
);

-- ============================================================
-- 3. SUBSCRIPTION (resolves Customer M:N Plan)
-- ============================================================
CREATE TABLE Subscription (
    subscription_id  INT   AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT   NOT NULL,
    plan_id          INT   NOT NULL,
    start_date       DATE  NOT NULL,
    end_date         DATE,
    status           VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id)     REFERENCES Plan(plan_id)
);

-- ============================================================
-- 4. INVOICE (weak entity)
-- ============================================================
CREATE TABLE Invoice (
    invoice_id       INT            AUTO_INCREMENT,
    subscription_id  INT            NOT NULL,
    amount           DECIMAL(10,2)  NOT NULL,
    due_date         DATE           NOT NULL,
    payment_status   VARCHAR(20)    DEFAULT 'unpaid',
    created_at       DATETIME       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (invoice_id, subscription_id),
    FOREIGN KEY (subscription_id) REFERENCES Subscription(subscription_id) ON DELETE CASCADE
);

-- ============================================================
-- 5. PAYMENT
-- ============================================================
CREATE TABLE Payment (
    payment_id    INT            AUTO_INCREMENT PRIMARY KEY,
    invoice_id    INT            NOT NULL,
    amount_paid   DECIMAL(10,2)  NOT NULL,
    method        VARCHAR(50),
    payment_date  DATE           DEFAULT (CURRENT_DATE),
    FOREIGN KEY (invoice_id) REFERENCES Invoice(invoice_id)
);

-- ============================================================
-- 6. USAGE_RECORD (weak entity)
-- ============================================================
CREATE TABLE Usage_Record (
    usage_id         INT            AUTO_INCREMENT,
    subscription_id  INT            NOT NULL,
    month_year       VARCHAR(10)    NOT NULL,
    data_used_gb     DECIMAL(8,2)   DEFAULT 0,
    PRIMARY KEY (usage_id, subscription_id),
    FOREIGN KEY (subscription_id) REFERENCES Subscription(subscription_id) ON DELETE CASCADE
);

-- ============================================================
-- 7. STAFF + ADMIN LOGIN
-- ============================================================
CREATE TABLE Staff (
    staff_id   INT           AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       VARCHAR(50)   NOT NULL
);

CREATE TABLE Technician (
    staff_id        INT          PRIMARY KEY,
    specialization  VARCHAR(100),
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id) ON DELETE CASCADE
);

CREATE TABLE Billing_Agent (
    staff_id         INT            PRIMARY KEY,
    commission_rate  DECIMAL(5,2),
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id) ON DELETE CASCADE
);

CREATE TABLE Support_Agent (
    staff_id  INT         PRIMARY KEY,
    shift     VARCHAR(30),
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id) ON DELETE CASCADE
);

-- ============================================================
-- 8. SUPPORT_TICKET (weak entity)
-- ============================================================
CREATE TABLE Support_Ticket (
    ticket_id    INT          AUTO_INCREMENT,
    customer_id  INT          NOT NULL,
    subject      VARCHAR(200),
    priority     VARCHAR(20)  DEFAULT 'medium',
    status       VARCHAR(20)  DEFAULT 'open',
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, customer_id),
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

-- ============================================================
-- 9. TICKET_STAFF (M:N assigned_to diamond)
-- ============================================================
CREATE TABLE Ticket_Staff (
    ticket_id    INT      NOT NULL,
    staff_id     INT      NOT NULL,
    assigned_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, staff_id),
    FOREIGN KEY (ticket_id) REFERENCES Support_Ticket(ticket_id),
    FOREIGN KEY (staff_id)  REFERENCES Staff(staff_id)
);

-- ============================================================
-- 10. OUTAGE
-- ============================================================
CREATE TABLE Outage (
    outage_id   INT           AUTO_INCREMENT PRIMARY KEY,
    staff_id    INT           NOT NULL,
    title       VARCHAR(200),
    type        VARCHAR(50),
    status      VARCHAR(30)   DEFAULT 'ongoing',
    start_time  DATETIME,
    end_time    DATETIME,
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id)
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO Plan (plan_name, price_monthly, bandwidth_limit_gb, speed_mbps) VALUES
('Basic',    500.00,  30,  '10 Mbps'),
('Standard', 900.00,  60,  '25 Mbps'),
('Premium',  1500.00, 120, '50 Mbps');

-- Default admin staff (password: admin123)
INSERT INTO Staff (name, email, password, role) VALUES
('Admin User', 'admin@isp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default customer (password: customer123)
INSERT INTO Customer (name, email, password, phone, street, city) VALUES
('Test Customer', 'customer@isp.com', '$2y$10$TKh8H1.PkR5ex8mEDMr.CO/m0.HqyLBZcw1aVaqWKpKK7BpHnRHK', '01711000000', '12 Main Road', 'Dhaka');