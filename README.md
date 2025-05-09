# PHP/Laravel System with JWT Auth, Nested Categories, and Concurrency-Safe Ticketing

## Overview

This project implements a secure, high-performance system to fulfill a real-world ticketing scenario. The application includes JWT authentication with refresh tokens, a nested category system using the Materialized Path pattern, concurrency-safe ticket reservations, and a React + shadcn frontend.

---

## Scenario

Imagine an event management system where users can browse and purchase tickets. Tickets are organized into nested categories (e.g., `Sports > Football > Premier League`). Each category is stored using a materialized path for efficient querying. Authentication is powered by secure JWT tokens with refresh support, and high concurrency is handled gracefully during ticket purchases.

---

## Running the Project with Docker

This project comes with a Docker setup for easy local development.

### Prerequisites

- Docker and Docker Compose installed

### Steps to Run

1. Clone the Repository

```bash
git clone https://github.com/shivajichalise/ticketing.git
cd ticketing
```

2. Configure the environment

```bash
cp backend/.env.docker.example backend/.env.docker
vim backend/.env.docker
```

> Make sure to set your JWT secrets at the end of the .env.docker file.

3. Start the Containers

```bash
docker compose up --build
```

4. Access the project

- Frontend: `locahost:5173`
- API: `locahost:8000/api`

---

## Features Implemented

All of the requirements from the task were implemented in this project.

## 1. Design Patterns Used

#### a. Strategy Pattern – Used for `ContextAwarePassword`

**Justification:**  
The **Strategy pattern** was used to modularize and isolate each individual password validation rule (e.g., dictionary word check, personal info match, time-based rules) into separate strategy classes.
These classes implement a common PasswordValidationStrategy interface and are executed within ContextAwarePassword.

- Allows Flexible and extensible password validation logic
- Clean separation of concerns from the rule container

_Used in:_ `App\Strategies\PasswordStrategies\*Strategy.php` and `App\Rules\ContextAwarePassword`

#### b. Service Pattern – Used for `JwtService`

**Justification:**  
The **Service pattern** was applied to encapsulate all JWT token logic (signing, verification, encoding, decoding) within a dedicated class: `JwtService`. This helps keep the controller and actions free from cryptographic logic or token structure concerns, making the codebase more **modular and reusable**.

- Keeps token generation/verifications decoupled from controllers
- Allows easy future extension without touching controllers

_Used in:_ `App\Services\JwtService`

#### c. Facade Pattern – Applied to expose `JwtService` globally as `Jwt::`

**Justification:**  
To make the `JwtService` conveniently accessible throughout the app (like in Actions), it is exposed using a **Facade**. This hides the instantiation and binding logic, providing a clean and expressive static interface (`Jwt::sign()`, `Jwt::verify()`).

- Simplifies usage of service class across app
- Maintains a clear API for JWT-related actions
- Keeps underlying implementation swappable

_Used as:_ `App\Facades\Jwt`

#### d. Action Pattern – Used in `AttemptLoginAction`

**Justification:**  
The **Action pattern** was used to isolate the **login logic with rate limiting and brute-force protection** in a dedicated invokable class. This aligns with the **Single Responsibility Principle**.

- Encapsulates one business use case: _attempting login_
- Includes all logic: credential check, rate-limiting, exponential backoff, token generation
- Improves maintainability, especially as auth logic grows

_Used in:_ `App\Actions\AttemptLoginAction`

---

## 2. Performance optimization strategy

#### 1. Chunked Bulk CSV Import (Memory Optimization)

**What was done:**  
Used a **chunked file reader and prepared statement execution** in `ImportCategoriesFromCsv` command.

**Justification:**  
Instead of loading the entire CSV file into memory, we streamed and processed it line-by-line using a fixed chunk size (e.g., 500 rows). This minimized memory usage and allowed importing **50k+ records efficiently** without exhausting PHP memory.

> **Benchmark**  
> Command: `php artisan app:import-categories-from-csv storage/app/data/categories.csv`  
> TIME: **1.91s**  
> MEM: **0.01MB**  
> SQL Queries: **103**  
> Rows Imported: **51,001**

_Relevant file:_ `app/Console/Commands/ImportCategoriesFromCsv.php`

---

#### 2. Efficient Category Tree Using Materialized Path

**What was done:**  
Used the **Materialized Path pattern** to store and query nested categories.

**Justification:**  
Compared to recursive adjacency lists, materialized path queries are **faster and more index-friendly**, especially when fetching children or subtree depth. Perfect for performance in read-heavy systems.

- Enables `LIKE 'path/%'` queries instead of recursive joins
- Subtree and breadcrumb retrieval is near-instant
- Works well with indexed `path` column

_Relevant model:_ `Category.php`

---

#### 3. Pagination for Record Listings

**What was done:**  
Used **pagination strategy** in endpoints dealing with large datasets (tickets, categories).

**Justification:**  
This avoids loading thousands of records into memory, keeping response times low and frontend snappy.

- Prevents slow API responses
- Reduces memory usage
- Improves user experience with paginated views

_Relevant in:_ Ticket listing and category trees

#### 4. Caching and Throttling Logic in Login

**What was done:**  
Used Laravel's `Cache` to throttle brute-force login attempts and implement **exponential backoff** logic inside `AttemptLoginAction`.

**Justification:**  
Avoided repeated DB lookups for rate limit checks. Cache provides microsecond access time, preventing bottlenecks in login flow under heavy traffic.

- Avoids hammering DB with login attempts
- Maintains consistent response time
- Protects app from brute-force slowdowns

_Relevant file:_ `App\Actions\AttemptLoginAction.php`

#### 5. Database Indexing and Constraints

**What was done:**  
Ensured important fields like `slug`, `path`, `parent_id`, `user_id`, and `email` were indexed.

**Justification:**  
Indexes are critical when dealing with tens of thousands of rows. Indexing improved lookup speed drastically for:

- Login validation (`email`)
- Subtree queries (`path`)
- Parent-child traversal (`parent_id`)
- Token checks (`user_id`)

_Applied via:_ Migrations

---

### Security Measures Implemented

#### 1. SQL Injection Protection

- **Eloquent ORM** and **parameter binding** are used throughout the application.
- No user input is concatenated into raw SQL queries.
- All queries/user inputs are sanitized and validated before execution/usage.

#### 2. Mass Assignment Protection

- Models use `$fillable` to explicitly allow fields for mass assignment.
- No use of `$guarded = []` to prevent accidental data injection.

#### 3. Rate Limiting & Brute Force Protection

- Login attempts are rate-limited via Laravel `Cache`.
- Brute force protection is implemented using **exponential backoff** (delay increases after each failed attempt).

#### 4. Password Policy Enforcement

The application enforces adaptive password validation using a custom `ContextAwarePassword` rule.

Security checks include:

- Must not contain the user's name or email
- Must not include common or dictionary words
- Must not match known breached passwords
- Must differ from the current password
- Requires minimum length based on context:
    - At least 10 characters on public IPs
    - At least 12 characters during nighttime hours
- Must include uppercase, lowercase, number, and special character

#### 5. Cross-Site Request Forgery (CSRF) Protection

The application uses stateless JWT for access tokens and stores the refresh token in an `HttpOnly` cookie to prevent CSRF.

Cookie security flags:

- `HttpOnly` to block JavaScript access
- `SameSite=Lax` to restrict cross-origin use
- `Secure` enabled in production for HTTPS

#### 6. Race Condition Mitigation (Security & Consistency)

- Ticket purchases uses Pessimistic locking `lockForUpdate` within DB transactions.
- This prevents race conditions that could lead to double purchases, or data corruption during concurrent access.
- Combined with concurrency testing (JMeter, hey), this ensures both security and system integrity under load.
- hey https://github.com/rakyll/hey `hey -n 100 -c 100 -m POST http://localhost:8000/api/tickets/3/buy`

---
