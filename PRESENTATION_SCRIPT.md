# Employee Management System — Final Project Presentation Script

**Course:** ITELEC 4100 — Web API Final Project
**Target length:** 15–20 minutes
**Stack:** Laravel 12 (REST API) + React 19 (SPA) + MySQL

> Lines in *italics* are stage directions (what to show on screen). Everything else is meant to be spoken.
> Time markers are a guide — adjust to your group. If you have several speakers, split by section.

---

## 1. Introduction (≈1 min)

*Show: the login page at http://localhost:5173.*

Good day! This is our final project for ITELEC 4100: the **Employee Management System**.

In our previous project we built this system using native PHP — PHP files that mixed HTML, SQL queries, and logic in the same page. For this final project we converted it into a modern architecture:

- the **back end** is now a **Laravel 12 REST API**,
- the **front end** is a **React 19 single-page application**,
- and both share a **MySQL** database.

The system has two roles. **Employees** can view their subjects and class schedule, see their tasks, and file leave, resignation, subject, and task requests with supporting documents. **Admins** manage employees, subjects, schedules, and tasks, and approve or reject every request.

---

## 2. System Architecture (≈2 min)

*Show: the project folder with `backend/` and `frontend/` side by side.*

The project is split into two separate applications:

| Folder | Technology | Runs on |
|---|---|---|
| `backend/` | Laravel 12, PHP 8.4 | http://127.0.0.1:8000 |
| `frontend/` | React 19, Vite | http://localhost:5173 |

The React app never touches the database directly. Every action — logging in, loading the dashboard, submitting a leave — is an **HTTP request to the Laravel API**, which responds in **JSON**.

So the flow is:

1. The user clicks something in React.
2. React sends a request using **Axios**, with a **bearer token** for authentication.
3. Laravel's **route** sends it to a **controller**.
4. The controller uses **models** to read or write MySQL.
5. Laravel returns JSON, and React re-renders the screen.

This is the **MVC pattern**: Laravel provides the **Model** and **Controller**, and React acts as the **View**. Separating them this way means the same API could later serve a mobile app without changing the back end.

---

## 3. Back End — Laravel (≈6 min)

### 3.1 Database and Migrations

*Show: `backend/database/migrations/`.*

Instead of creating tables by hand in phpMyAdmin, we use **Laravel migrations**. Each migration is a PHP file that describes a table, so the whole database can be rebuilt with one command:

```
php artisan migrate:fresh --seed
```

Our tables are: `employees`, `subjects`, `employee_subjects` (the subject assignments), `schedules`, `tasks`, four request tables — `leave_requests`, `resignation_requests`, `subject_requests`, `task_requests` — and `attachments` for uploaded documents.

We used **foreign keys** with cascading deletes, **unique constraints** — for example, an employee cannot be assigned the same subject twice — and **enum columns** for fixed values like role and status.

*Show: `database/seeders/DatabaseSeeder.php`.*

The **seeder** fills the database with demo accounts and sample data so the system is ready to use right away.

We also wrote a custom Artisan command, `php artisan legacy:import`, that copies all the data from our **old native PHP database** into the new one. It also **hashes the old plain-text passwords**, which was a security problem in our original project.

### 3.2 Models (the "M" in MVC)

*Show: `app/Models/Employee.php`, then `LeaveRequest.php`.*

Each table has an **Eloquent model**. Eloquent is Laravel's ORM, which means we work with PHP objects instead of writing raw SQL.

- Relationships are defined once — for example, an `Employee` **has many** `tasks`, `leaveRequests`, and `subjects` — and Laravel builds the joins for us.
- **Casts** convert columns automatically: the password is always **hashed**, dates become date objects, and role and status become **PHP enums**.

*Show: `app/Enums/`.*

We used **PHP 8 enums** — `Role`, `EmploymentStatus`, `RequestStatus`, `TaskStatus`, and `LeaveType` — instead of plain strings. This prevents typos like "aproved" and keeps all valid values in one place.

`LeaveType` also defines which **proof documents** each leave needs. For example, **Sick Leave requires a Medical Certificate**, Paternity Leave requires a birth certificate, and Vacation Leave's document is optional.

*Show: `app/Contracts/Reviewable.php` and `app/Models/Concerns/IsReviewable.php`.*

All four request types share the same approval behavior, so instead of copying code we created a **`Reviewable` interface** and an **`IsReviewable` trait**. Each request model just describes itself and what should happen when it is approved. For example, when a **subject request** is approved, the subject is automatically assigned to the employee, and when a **task request** is approved, a new task is created.

### 3.3 Routes and Controllers (the "C" in MVC)

*Show: `routes/api.php`.*

All endpoints are in `routes/api.php`, organized into three groups:

- **Public** — register, login, Google login, and dropdown options.
- **Employee** (`/api/me/...`) — dashboard, schedule, tasks, and submitting requests.
- **Admin** (`/api/admin/...`) — managing employees, subjects, assignments, tasks, and reviewing requests.

*Show: `app/Http/Controllers/Api/`.*

Controllers are split into `Auth`, `Employee`, and `Admin` folders. We kept the controllers **thin**: they receive the request, call a model or service, and return a response. For example, `EmployeeController` is a **resource controller** with `index`, `store`, `show`, `update`, and `destroy`.

### 3.4 Validation — Form Requests

*Show: `app/Http/Requests/Employee/SubmitLeaveRequest.php`.*

Validation is not inside the controllers. Each form has its own **Form Request** class. For example, `SubmitLeaveRequest` checks that:

- the start date is today or later,
- the end date is not before the start date,
- and the required document is uploaded, is a PDF or image, and is at most 5 MB.

If validation fails, Laravel automatically returns a **422** response with an error for each field, and React shows that message under the matching input.

### 3.5 Authentication and Security

*Show: `app/Http/Controllers/Api/Auth/AuthController.php` and `bootstrap/app.php`.*

For authentication we used **Laravel Sanctum**. When a user logs in, Laravel creates an **API token**. React stores it and sends it as a `Bearer` header with every request.

Security layers:

- **Passwords** are hashed with bcrypt.
- **Login is rate limited** to 10 attempts per minute to block brute-force attacks.
- **`role` middleware** — employees get **403 Forbidden** if they call admin endpoints.
- **`active` middleware** — if an admin deactivates an account, that user's tokens are revoked and they are logged out immediately.
- **`AttachmentPolicy`** — uploaded documents are stored **privately**, not in a public folder. Only the admin and the employee who uploaded a file can open it.
- An admin **cannot delete, demote, or deactivate their own account**, so the system is never left without an admin.

*Show: `app/Http/Controllers/Api/Auth/GoogleAuthController.php`.*

For the bonus, we added **Google login** using **Laravel Socialite**. If an employee's Google email matches an existing account, it is linked automatically. The Google button only appears when the Google credentials are configured.

### 3.6 Services and API Resources

*Show: `app/Services/RequestReviewService.php` and `AttachmentService.php`.*

Business logic that is too big for a controller lives in **service classes**:

- **`RequestReviewService`** approves or rejects requests inside a **database transaction**, so if anything fails, nothing is half-saved. It also prevents a request from being reviewed twice, and requires a comment when rejecting.
- **`AttachmentService`** saves uploaded files. If saving the request fails, it **deletes the files** so no orphan files are left behind.

*Show: `app/Http/Resources/`.*

**API Resources** control exactly what JSON is sent to React. For example, `EmployeeResource` never includes the password.

### 3.7 Automated Testing

*Show: run `php artisan test` in the terminal.*

We wrote **28 automated feature tests** using PHPUnit. They cover login, registration, role protection, file uploads, validation, approving and rejecting requests, and admin safeguards. All of them pass.

---

## 4. Front End — React (≈4 min)

### 4.1 Tools

*Show: `frontend/package.json`.*

| Tool | Purpose |
|---|---|
| **React 19** | Builds the UI out of reusable components |
| **Vite** | Development server with instant reload, and the production build |
| **React Router 7** | Page navigation without reloading the browser |
| **Axios** | Sends HTTP requests to the Laravel API |
| **Bootstrap 5 + Bootstrap Icons** | Responsive layout, styling, and icons |
| **Oxlint** | Checks code quality and catches mistakes |

### 4.2 Folder Structure

*Show: `frontend/src/`.*

```
src/
├── api/          → Axios client and all API calls
├── components/   → reusable UI pieces (tables, modals, form fields, badges)
├── context/      → global state (logged-in user, toasts, dropdown options)
├── hooks/        → reusable logic (useApi, useForm, useDebounce)
├── layouts/      → Employee and Admin page frames with navigation
├── pages/        → one file per screen, split into auth/, employee/, admin/
├── styles/       → app.css design
└── utils/        → date/file formatting and error parsing
```

### 4.3 How It Connects to the API

*Show: `src/api/client.js` and `src/api/services.js`.*

`client.js` creates one Axios instance. An **interceptor** automatically attaches the login token to every request, and if the server ever returns **401 Unauthorized**, the user is logged out and sent back to the login page.

`services.js` groups every endpoint into `authApi`, `employeeApi`, and `adminApi`, so pages call simple functions like `adminApi.employees()` instead of writing URLs everywhere.

### 4.4 State and Reusable Logic

*Show: `src/context/AuthContext.jsx`, then `src/hooks/useForm.js`.*

- **Context API** — `AuthContext` stores the logged-in user so any page knows who is logged in and whether they are an admin. `ToastContext` shows success and error pop-ups, and `OptionsContext` loads dropdown values once.
- **Custom hooks**:
  - `useApi` loads data and tracks loading and error states.
  - `useForm` manages form values, submission, and the validation errors from Laravel.
  - `useDebounce` waits until the user stops typing before searching, so we don't send a request on every keystroke.

### 4.5 Routing and Protection

*Show: `src/App.jsx` and `src/components/ProtectedRoute.jsx`.*

`ProtectedRoute` blocks pages that need login and checks the role — an employee who types `/admin` in the address bar is redirected away. The **server also checks the role**, so the protection doesn't rely only on the front end.

### 4.6 Reusable Components

Instead of repeating HTML, we built shared components: `DataTable`, `Modal`, `ConfirmDialog`, `StatusBadge`, `SearchInput`, form fields, and `AttachmentPreview`, which lets the admin view uploaded PDFs and images right inside the review window.

---

## 5. Live Demo (≈5 min)

*Before recording: run `php artisan migrate:fresh --seed` so the data is clean.*

**Employee side** — log in as `employee` / `employee123`:

1. *Dashboard:* "This is the employee dashboard. It shows the profile, subjects and class schedule, assigned tasks, and the status of every request."
2. *File Leave → choose **Sick Leave**:* "Notice that the form changes — Sick Leave requires a **Medical Certificate**."
3. *Click Submit with empty fields:* "These errors come from Laravel's validation, shown under each field."
4. *Fill in the dates and reason, upload a PDF or image, and submit:* "The request is now **Pending**."
5. *Submit a Subject Request or Task Request,* then log out.

**Admin side** — log in as `admin` / `admin12345`:

1. *Admin dashboard:* "The admin sees totals, pending requests by type, and employees per department. The bell icon is the request inbox."
2. *Requests → open the Sick Leave:* "The admin can **preview the medical certificate** right here, or download it."
3. *Approve with a comment.* Then open another request and *try to reject with no comment:* "A comment is required when rejecting."
4. *Approve the subject request,* then go to *Employees → Subjects* for that employee: "Approving it **automatically assigned the subject**."
5. *Employees page:* show search, Add/Edit/Delete, and the details window.
6. *Subjects page,* and an employee's *Assign Subject* (day, time, room) and *Tasks* page.

**Back to the employee:** log in again and show that the request status and the admin's comment are now visible.

---

## 6. Feature Enhancements over the Native PHP Version (≈1 min)

- REST API architecture separating the back end and front end
- Token authentication with Laravel Sanctum, plus optional **Google login**
- Old plain-text passwords are now **hashed**
- Login rate limiting, role middleware, and protection for deactivated accounts
- Leave proof documents that depend on the leave type, with an **in-app document preview** for admins
- Approval side effects — approved subject and task requests are applied automatically
- Admin inbox with automatic refresh every 30 seconds
- Search and status filters on lists
- Database transactions for approvals and file uploads
- A command that imports our old database into the new system
- 28 automated tests

---

## 7. Closing (≈30 sec)

To summarize: we converted our native PHP employee management system into a **Laravel REST API** and a **React single-page application**, following MVC with models, controllers, validation, middleware, and services on the back end, and reusable components, hooks, and context on the front end.

Thank you for watching!

---

## Quick Reference (for the presenter)

| Item | Value |
|---|---|
| Start back end | `cd backend` → `php artisan serve --host=127.0.0.1 --port=8000` |
| Start front end | `cd frontend` → `npm run dev` → open http://localhost:5173 |
| Reset demo data | `php artisan migrate:fresh --seed` |
| Run tests | `php artisan test` |
| Admin login | `admin` / `admin12345` |
| Employee login | `employee` / `employee123` |
