# Technical Documentation: Coffee Factory Management System

## 1. Executive Summary

The **Coffee Factory Management System (CMS)** is a centralized platform designed to digitize and optimize coffee factory operations. It provides a "single source of truth" for farmer data, delivery workflows, quality assessments, and inventory management. The system is built for reliability, security, and ease of use in a fast-paced factory environment.

---

## 2. System Architecture

### 2.1 Core Technology Stack

- **Backend (PHP 7.4+)**: Chosen for its high compatibility with hosting environments (like XAMPP/Apache) and rapid development cycles.
- **Database (MySQL)**: A relational database ensuring data integrity through foreign key constraints and optimized indexing.
- **UI/UX (Coffee Pop Theme)**: A custom-designed aesthetic using CSS variables for a premium, high-contrast look that matches the coffee industry (vibrant browns, creams, and greens).
- **Frontend Logic**: Vanilla JavaScript used for real-time client-side searching, modal management, and interactive charts (using Chart.js).

### 2.2 Directory Structure Logic

- `/assets`: Distinguishes between visual styling (`css`) and interactive logic (`js`).
- `/config`: Isolates environment-sensitive settings (DB credentials, Session timeouts).
- `/includes`: Centralizes the "DRY" (Don't Repeat Yourself) components like `header.php`, `footer.php`, and `functions.php`.
- `/modules`: Each module (Farmers, Deliveries, Inventory) is self-contained with its own views and action handlers (e.g., `add.php`, `edit.php`).

---

## 3. Modular Breakdown & Logic

### 3.1 Authentication & Authorization

The system uses a custom session-based authentication layer (`config/session.php`).

- **Logic**: Every page request checks for an active session. Role-based checks (`requireRole`) ensure a Clerk cannot access Manager-only features like User Management or sensitive financial reports.
- **Password Security**: Passwords are never stored in plain text; the system uses `password_hash()` (Bcrypt).

### 3.2 Farmer Registry

The "heart" of the system.

- **Logic**: Uses a specific ID generation pattern (`MRCxxx`) to uniquely identify farmers.
- **Data Flow**: Farmer contributions are automatically aggregated from the `deliveries` table to show "Total Quantity" and "Total Deliveries" in real-time.

### 3.3 Delivery & Quality Control

Captures the primary intake of the factory.

- **Logic**: Records weight, moisture content, and grade (AA, AB, etc.).
- **Workflow**: `Pending` → `Quality Check` → `Approved`. This multi-step process ensures only high-quality coffee enters the inventory batches.

### 3.4 Inventory Management

Tracks coffee through the processing lifecycle.

- **Logic**: Batches are tracked by status (`Received` → `Processing` → `Dried` → `Milled` → `Ready for Export`).
- **Traceability**: Each inventory batch is linked back to the delivery records, providing full traceability from "farm to cup."

---

## 4. Key Code Implementation (Module Deep Dive)

### 4.1 The Security Layer (`includes/functions.php`)

Every state-changing action (adding, editing, deleting) is wrapped in 3 layers of protection:

1. **`sanitize()`**: Removes malicious tags and trims white space.
2. **`requireCsrf()`**: Ensures the request originated from our application, not an external attacker.
3. **`execute()`**: Uses PDO Prepared Statements to prevent SQL Injection by separating the SQL command from the user data.

### 4.2 Data Transformation Flow (Example: `farmers/index.php`)

1. **Query**: Fetches data using SQL Joins to combine farmer profile info with their lifetime delivery statistics.
2. **Sanitize**: Uses the `e()` function to escape data before rendering.
3. **Display**: Uses CSS Flexbox/Grid to present data in "Coffee Cards" for better readability on mobile and desktop.

---

## 5. System Features Highlights

- **Insight Cards**: Compact KPI widgets that provide instant visibility into Total Farmers, Active Status, and Total Volume.
- **Real-time Search**: Instant client-side filtering that lets staff find farmers or deliveries without refreshing the page.
- **Activity Auditing**: Every login and data modification is logged in `system_logs` with a timestamp and IP address.
- **Automated Reporting**: Export-ready views for delivery history and inventory stock levels.

---

## 6. Logic Breakdown (Line-by-Line Narrative)

When a clerk clicks **"Register Farmer"**:

1. **Frontend**: A modal opens; the `getNextFarmerId()` function is called via PHP to pre-fill the next available ID.
2. **Submission**: The data is sent via POST to `add.php`.
3. **CSRF Check**: The server validates the hidden token in the form.
4. **Sanitization**: All strings (Name, Location) are cleaned of potential HTML/Script tags.
5. **Database Transaction**: A prepared SQL `INSERT` statement is sent to the MySQL server.
6. **Logging**: The `logAction()` function records that "Clerk X registered Farmer Y."
7. **Redirect**: The clerk is sent back to the registry with a success "Flash Message."

---
