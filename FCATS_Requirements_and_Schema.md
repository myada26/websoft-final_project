# 📄 Functional & Non-Functional Requirements
### Fee Collection and Tracking System (FCATS)
> **Document Type:** Requirements Specification + Database Schema  
> **Scope:** Student Council Fee Collection — University-Level  
> **Modules:** 7 Functional Modules · 16 NFRs · 16 Tables

---

## 📑 Table of Contents

1. [Functional Requirements](#functional-requirements)
   - [Module 1 — University Structure & Configuration](#module-1--university-structure--configuration)
   - [Module 2 — Identity & Access Management](#module-2--identity--access-management)
   - [Module 3 — Student Data & Enrollment](#module-3--student-data--enrollment-semester-based)
   - [Module 4 — Fee & Fine Configuration](#module-4--fee--fine-configuration)
   - [Module 5 — Assessment & Payment (POS)](#module-5--assessment--payment-pos)
   - [Module 6 — Cash Management (Remittance)](#module-6--cash-management-remittance)
   - [Module 7 — Reporting](#module-7--reporting)
2. [Non-Functional Requirements](#non-functional-requirements)
3. [Database Schema](#database-schema)
4. [Relationships](#relationships)

---

## Functional Requirements

---

### Module 1 — University Structure & Configuration

#### FR-0001 · 3-Tier Hierarchy Management *(SSC Only)*
> **Actor:** SSC Admin with `config:structure` permission

The system must support a fixed 3-level academic hierarchy:

| Level | Name | Example |
|---|---|---|
| Level 1 | **College** | College of Engineering |
| Level 2 | **Department** | Dept of Civil Engineering *(Parent: COE)* |
| Level 3 | **Program** | BS Civil Engineering *(Parent: DCE)* |

- [ ] SSC Admin is the only role authorized to create, edit, or deactivate hierarchy nodes
- [ ] Deleting a College/Department is restricted if child records exist (`ON DELETE RESTRICT`)

---

#### FR-0002 · Semester & Academic Year Management
> **Actor:** SSC Admin

- [ ] SSC Admin creates and manages Academic Year / Semester records
- [ ] Only **one** semester may be flagged `is_active = TRUE` at any time
- [ ] All system actions (enrollment, payments, remittances) **default to the Active Semester**
- [ ] Switching the active semester must warn if unresolved transactions exist

---

#### FR-0003 · Organization Scope Definition
> **Actor:** SSC Admin

- [ ] Each Organization is mapped to exactly one level of the hierarchy:
  - `COLLEGE_COUNCIL` → linked to a `College`
  - `DEPT_SOCIETY` → linked to a `Department`
  - `SSC` → not linked (top-level)
- [ ] Organizations cannot collect fees outside their scoped hierarchy

---

### Module 2 — Identity & Access Management

#### FR-0004 · Authentication & Lockout

- [ ] Login via **Username + Password**
- [ ] Passwords stored as **bcrypt/Argon2 hash** (never plain text)
- [ ] Account locked for **15 minutes** after **5 consecutive failed login attempts**
- [ ] `locked_until` timestamp stored in `USERS` table; auto-unlocks after expiry

---

#### FR-0005 · Granular Permission Management
> **Actor:** Chairperson (within their own Organization)

- [ ] Chairpersons can toggle individual permissions **on/off** for their officers
- [ ] Permissions are identified by slugs:

| Slug | Description |
|---|---|
| `pos:create` | Process a payment transaction |
| `remit:create` | Create a remittance batch |
| `remit:verify` | Digitally verify/sign a remittance |
| `remit:accept` | Mark remittance as received/banked |
| `void:request` | Request a receipt void |
| `void:approve` | Approve a void request |
| `reports:view` | Access financial reports |

- [ ] A Chairperson **cannot** grant permissions beyond their own access level

---

#### FR-0006 · Organization Isolation

- [ ] Every user is **strictly scoped** to their Organization
- [ ] An Engineering officer **cannot** view, search, or modify data belonging to Nursing
- [ ] Isolation must be enforced at the **API/query level**, not just the UI level
- [ ] Attempting cross-org access returns a `403 Forbidden` response

---

### Module 3 — Student Data & Enrollment *(Semester-Based)*

#### FR-0007 · Student Identity Separation

- [ ] System uses a hidden internal `student_id` (PK) for all database relationships
- [ ] Officers search using the user-facing `student_number` (School ID, e.g., `2023-001`)
- [ ] The two fields are never exposed interchangeably in the UI

---

#### FR-0008 · Semester-Based Bulk Import *(SSC)*
> **Actor:** SSC Admin

- [ ] SSC uploads the student enrollment list (CSV/Excel) for the Active Semester
- [ ] Each import creates a new `STUDENT_ENROLLMENTS` record per student
- [ ] **Shifting Logic:** If a student shifts programs, the new enrollment record reflects the new program; the old record is **preserved in history** (never deleted)
- [ ] Duplicate detection: if `(student_id, academic_year_id)` already exists, skip or prompt

---

#### FR-0009 · Smart Manual Entry *(Chairperson)*
> **Actor:** College Chair or Dept Chair

Context-aware form that locks fields based on the officer's scope:

| Officer Role | College Field | Department Field | Program Field |
|---|---|---|---|
| **College Chair** | 🔒 Locked (own college) | Selectable (within college) | Selectable (within dept) |
| **Dept Chair** | 🔒 Locked | 🔒 Locked (own dept) | Selectable (within dept) |

- [ ] Form must prevent selecting Programs outside the officer's college/department
- [ ] Manual entries are flagged as `created_source = 'MANUAL'`

---

#### FR-0010 · Cascading Membership Logic

> **Rule:** Membership is **derived** from the Active Semester Enrollment — not manually assigned.

```
Student enrolled in Program X (Active Semester)
  → Automatically a member of:
      · Dept Society linked to Program X's Department
      · College Council linked to Program X's College
```

- [ ] Membership derivation runs at query time (not stored as a separate flag)
- [ ] If a student is not enrolled in the Active Semester, they have **no active membership**

---

### Module 4 — Fee & Fine Configuration

#### FR-0011 · Org-Specific Fee Profiles *(Membership)*
> **Actor:** Chairperson

- [ ] Each Organization creates its own **Membership Fee** receivable items
- [ ] Fee profiles are **not shared** — only visible to the Organization that created them
- [ ] Multiple fee profiles can be active simultaneously (e.g., for different student categories)

---

#### FR-0012 · Flexible Fee Categories

| Category | Description | Rate Behavior |
|---|---|---|
| `REGULAR` | Standard enrolled student | Single fixed amount |
| `IRREGULAR` | Varying student load | Multiple named options (e.g., "Irregular Rate A") |
| `EXTENDEE` | Extended/overloading | Separate defined rate |
| `EXEMPTED` | Exempt from payment | Zero rate (`0.00`) |

- [ ] Officer selects the applicable category during POS transaction
- [ ] Irregular: displays a **checklist** of available options; officer selects one

---

#### FR-0013 · Manual Fine Payment *(Direct Entry / Point-of-Sale)*

> ⚠️ **Key Design Decision:** Fines are **not pre-encoded** in the database.

- [ ] There is **no "Fine List"** table — fines are not stored before payment
- [ ] Officer references an **external record** (paper logbook, spreadsheet) at time of collection
- [ ] At payment, officer manually enters the `amount_paid` for a `FINE` transaction
- [ ] Fine transactions have `transaction_type = 'FINE'` and `fee_profile_id = NULL`
- [ ] A receipt (OR) is generated immediately upon entry

---

### Module 5 — Assessment & Payment (POS)

#### FR-0014 · Context-Aware Student Search

- [ ] Search results are **filtered by Active Semester** automatically
- [ ] Displays student's enrollment status within the officer's organization scope
- [ ] If a student has shifted out of the college:
  - The old college's officer sees status: **"Not Enrolled"**
  - This prevents accidental collection from non-members

---

#### FR-0015 · Dynamic Assessment

```
Officer selects: Regular
  → System auto-selects: standard Membership Fee profile
  → Amount pre-filled; officer confirms

Officer selects: Irregular
  → System displays: checklist of Irregular Fee options
  → Officer selects applicable rate
  → Amount populated from selected profile
```

- [ ] Assessment logic must match `FR-0012` category definitions
- [ ] Amount field is **read-only** for Fee transactions (pulled from Fee Profile)

---

#### FR-0016 · Payment Methods

| Method | Required Fields |
|---|---|
| **Cash** | `amount_paid` |
| **GCash** | `amount_paid` + `reference_number` |

- [ ] GCash `reference_number` is mandatory when `payment_method = 'GCASH'` (enforced by DB constraint)
- [ ] No partial payments — amount must equal the Fee Profile's defined amount

---

#### FR-0017 · Single-Item Transactions

> **Rule:** One receipt = One fee item. Membership Fee and Absence Fines must be **separate transactions** with separate Official Receipt (OR) numbers.

- [ ] UI must not allow bundling multiple fee types into one transaction
- [ ] Each transaction generates exactly one OR

---

#### FR-0018 · Gap-Free Official Receipts (OR)

- [ ] OR numbers are **sequential and organization-specific**: `ENG-001`, `ENG-002`, `NURS-001`
- [ ] Generated using a **database-level sequence lock** on the `OR_SEQUENCES` table to prevent gaps
- [ ] Format: `{ORG_CODE}-{PADDED_NUMBER}` (e.g., `COE-00042`)
- [ ] Once issued, an OR number is **never reused**, even after voids

---

#### FR-0019 · Void Workflow

Two-step process to cancel a receipt:

```
Step 1 — REQUEST
  Officer submits void request with reason
  Transaction marked: pending void (not yet voided)
  Status: PENDING

Step 2 — APPROVE / REJECT
  Authorized officer (Auditor/Chairperson) reviews
  If APPROVED → Transaction.is_void = TRUE
  If REJECTED → Transaction remains valid
  Status: APPROVED | REJECTED
```

- [ ] Only one void request may exist per transaction (unique constraint)
- [ ] Voided transactions are **retained** in the database for audit purposes
- [ ] OR number of a voided transaction is marked void but **not reassigned**

---

### Module 6 — Cash Management (Remittance)

#### FR-0020 · Smart Remittance Creation

```
Officer clicks "Create Remittance"
  → System fetches: transactions WHERE
      processed_by_user_id = current_user
      AND remittance_id IS NULL
      AND is_void = FALSE
  → Displays list + total amount
  → Officer confirms
  → System creates Remittance record
  → Updates transactions: remittance_id = new_remittance.id
```

- [ ] Officer cannot manually select which transactions to include — system auto-fetches all unremitted
- [ ] Confirmed total must match system-computed total (no manual override)

---

#### FR-0021 · Three-Stage Verification Cycle

| Stage | Actor | Permission | Action |
|---|---|---|---|
| **Stage 1 · Create** | Finance Officer | `remit:create` | Generates remittance record; groups transactions |
| **Stage 2 · Verify** | Auditor | `remit:verify` | Physically counts cash; digitally signs/verifies |
| **Stage 3 · Accept** | Chairperson/Treasurer | `remit:accept` | Marks cash as received and banked |

- [ ] Stages must be completed **in order** (cannot Accept before Verify)
- [ ] Each stage stamps a timestamp: `verified_at`, `accepted_at`
- [ ] Each stage records the responsible user: `verified_by_user_id`, `accepted_by_user_id`

---

#### FR-0022 · Semester-Scoped Financials

- [ ] Every transaction and remittance is tagged with `academic_year_id`
- [ ] Reports and summaries are **filterable by semester**
- [ ] Current semester data is visually separated from historical records
- [ ] Cross-semester aggregation is available only to Chairpersons and SSC

---

### Module 7 — Reporting

#### FR-0023 · Digital Receipts

- [ ] PDF receipt generated for every completed transaction
- [ ] Receipt includes:
  - Organization logo
  - Student number and name
  - OR number
  - Amount, payment method, GCash reference (if applicable)
  - Date/time and processing officer name
  - Academic Year / Semester
- [ ] Formatted for **thermal printer (80mm/58mm)** or **A4 half-sheet**

---

#### FR-0024 · Org-Specific Financial Reports

- [ ] Collection summary broken down by:
  - Fee Type: `Membership Fee` vs `Fines`
  - Payment Mode: `Cash` vs `GCash`
- [ ] Filterable by: date range, semester, officer
- [ ] Export formats: PDF, Excel/CSV

---

#### FR-0025 · Immutable Audit Logs

- [ ] Every system action generates an audit log entry (cannot be edited or deleted)
- [ ] Log captures: user, action, entity affected, old/new values, IP address, timestamp
- [ ] **Chairperson** can view logs scoped to their Organization
- [ ] **SSC** can view the global audit trail across all organizations
- [ ] Retained for a **minimum of 5 years** (NFR-014)

---

## Non-Functional Requirements

---

### 1 · Performance & Reliability

#### NFR-001 · Sub-Second Search Latency
> **Goal:** Student search queries (by ID or Name) must return results in **< 1 second**

- Reason: POS lines during enrollment week are long — any lag causes crowding
- Implementation: Index `student_number`, index `(student_id, academic_year_id)` in `STUDENT_ENROLLMENTS`

---

#### NFR-002 · Concurrent User Handling
> **Goal:** System must support **50–100 concurrent users** without crashing or degrading performance

- Reason: Multiple college officers transact simultaneously during peak enrollment
- Implementation: Connection pooling, query optimization, no N+1 queries

---

#### NFR-003 · High Availability
> **Goal:** **99.9% uptime** during business hours (8:00 AM – 5:00 PM)

- Reason: Continuous collection operations during enrollment periods
- Implementation: Reliable hosting, health checks, graceful error handling

---

### 2 · Security & Compliance

#### NFR-004 · Password Hashing
> **Goal:** All passwords hashed using **bcrypt or Argon2** — plain text never stored

---

#### NFR-005 · Session Management
> **Goal:** Sessions **auto-timeout after 10 minutes** of inactivity

- Reason: Prevent unauthorized access when an officer leaves their desk unattended

---

#### NFR-006 · Data Privacy
> **Goal:** Student personal data is **strictly visible only to authorized users within their org scope**

- An Engineering officer must not be able to query Nursing student data via API manipulation
- Enforcement must occur at the **query/middleware layer**, not just the UI

---

#### NFR-007 · SQL Injection & XSS Protection
> **Goal:** All inputs sanitized; no script injection possible

- Use parameterized queries / ORM (never raw string concatenation in SQL)
- Strip or encode HTML tags in all user-input fields

---

### 3 · Data Integrity & Accuracy

#### NFR-008 · ACID Compliance
> **Goal:** All financial operations must be **fully atomic**

```
Example failure scenario:
  Payment processed → Internet cuts out → Receipt not generated
  
  Required behavior:
  → Entire transaction ROLLS BACK
  → Payment record NOT saved
  → Student balance unchanged
  → Officer sees error; retries cleanly
```

---

#### NFR-009 · Floating Point Precision
> **Goal:** All monetary values stored as `DECIMAL(10,2)` — **never `FLOAT` or `DOUBLE`**

- Reason: Floating point arithmetic causes rounding errors in financial totals
- All arithmetic (totals, remittance sums) must use decimal-safe computation

---

### 4 · Usability & Interface

#### NFR-010 · Minimal Clicks Workflow
> **Goal:** Standard payment transaction completes in **≤ 4 clicks/steps**

```
Step 1: Search student
Step 2: Select fee / enter fine amount
Step 3: Enter payment amount (+ GCash ref if applicable)
Step 4: Confirm → Receipt generated
```

---

#### NFR-011 · Mobile Responsiveness *(Limited)*
> **Goal:** POS is **desktop-first**; Dashboard and Reports must be **readable on mobile**

- Chairpersons checking stats on mobile devices must see a functional, readable layout
- POS transaction flow does not need to be mobile-optimized

---

#### NFR-012 · Clear Error Messaging
> **Goal:** All errors must display **human-readable messages**

| ❌ Bad | ✅ Good |
|---|---|
| `Error 500` | `"An unexpected error occurred. Please try again."` |
| `FK constraint violation` | `"Student is not enrolled in this college for the active semester."` |
| `Duplicate entry` | `"This student has already been enrolled this semester."` |

---

### 5 · Scalability & Maintenance

#### NFR-013 · Horizontal Scalability
> **Goal:** Active Semester queries remain **fast as historical data grows** (millions of rows)

- Implementation: Proper indexing on `academic_year_id` across all major tables
- Consider table partitioning by year after 5+ years of data accumulation

---

#### NFR-014 · Audit Log Retention
> **Goal:** Audit logs retained for a **minimum of 5 years** (or per university policy)

---

### 6 · Deployment

#### NFR-015 · Browser Compatibility
> **Goal:** Fully functional on **Chrome, Edge, and Firefox**

---

#### NFR-016 · Printer Compatibility
> **Goal:** PDF receipts formatted for:
- Thermal printers: **80mm or 58mm** width
- Standard paper: **A4 or Letter (half-sheet)**

---

## Database Schema

---

### Module 1 — University Structure

#### Table: `ACADEMIC_YEARS`
> Manages semester timeline. Only one row may have `is_active = TRUE`.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `name` | String | e.g., `"1st Semester 2024-2025"` |
| `is_active` | Boolean | Unique constraint: only one `TRUE` at a time |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique constraint on `is_active = TRUE`

---

#### Table: `COLLEGES`
> Top level of the 3-tier hierarchy.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `name` | String | e.g., `"College of Engineering"` |
| `code` | String | e.g., `"COE"` — Unique |
| `is_active` | Boolean | |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

---

#### Table: `DEPARTMENTS`
> Middle tier, linked to a College.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `college_id` | FK → `COLLEGES(id)` | `ON DELETE RESTRICT` |
| `name` | String | e.g., `"Department of Civil Engineering"` |
| `code` | String | e.g., `"DCE"` |
| `is_active` | Boolean | |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique on `(college_id, code)`

---

#### Table: `PROGRAMS`
> The specific course a student enrolls in.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `department_id` | FK → `DEPARTMENTS(id)` | `ON DELETE RESTRICT` |
| `name` | String | e.g., `"BS Civil Engineering"` |
| `code` | String | e.g., `"BSCE"` |
| `is_active` | Boolean | |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique on `(department_id, code)`

---

### Module 2 — Organization & Access

#### Table: `ORGANIZATIONS`
> The entity that collects fees (Council / Society / SSC).

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `name` | String | e.g., `"Engineering Council"` |
| `type` | Enum | `SSC` · `COLLEGE_COUNCIL` · `DEPT_SOCIETY` |
| `linked_college_id` | FK → `COLLEGES(id)` | Nullable · `ON DELETE RESTRICT` |
| `linked_department_id` | FK → `DEPARTMENTS(id)` | Nullable · `ON DELETE RESTRICT` |
| `is_active` | Boolean | |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:**
- If `type = 'COLLEGE_COUNCIL'` → `linked_college_id IS NOT NULL` AND `linked_department_id IS NULL`
- If `type = 'DEPT_SOCIETY'` → `linked_department_id IS NOT NULL`
- If `type = 'SSC'` → both linked IDs are `NULL`

---

#### Table: `USERS`
> Officers and staff (students serving as organization officers).

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `username` | String | Unique · Pattern: `{student_number}-{org_code}` |
| `password_hash` | String | bcrypt / Argon2 |
| `role` | String | e.g., `"Treasurer"`, `"Auditor"`, `"President"` |
| `is_active` | Boolean | |
| `last_login` | Datetime | Nullable |
| `failed_login_attempts` | Integer | Default: `0` |
| `locked_until` | Datetime | Nullable — 15-min lockout |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique on `(student_id, organization_id)` — one officer account per org

---

#### Table: `PERMISSIONS`
> Immutable menu of system capabilities.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `slug` | String | Unique · e.g., `"pos:create"`, `"remit:verify"` |
| `description` | String | Human-readable description |
| `module` | String | e.g., `"POS"`, `"REMITTANCE"`, `"REPORTS"` |

---

#### Table: `USER_PERMISSIONS`
> Maps which permissions are granted to which users.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `user_id` | FK → `USERS(id)` | `ON DELETE CASCADE` |
| `permission_id` | FK → `PERMISSIONS(id)` | `ON DELETE RESTRICT` |
| `granted_at` | Datetime | |

**Constraints:** Unique on `(user_id, permission_id)`

---

### Module 3 — Student Data & Enrollment

#### Table: `STUDENTS`
> Permanent identity record of every student.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK — Internal system ID (hidden from UI) |
| `student_number` | String | Unique — School ID shown in UI (e.g., `"2023-001"`) |
| `first_name` | String | |
| `last_name` | String | |
| `middle_name` | String | Nullable |
| `created_source` | Enum | `SSC_BULK` · `MANUAL` |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

---

#### Table: `STUDENT_ENROLLMENTS`
> Time-bound academic status. Handles program shifting across semesters.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `program_id` | FK → `PROGRAMS(id)` | `ON DELETE RESTRICT` |
| `year_level` | Integer | |
| `is_regular` | Boolean | `TRUE` = Regular student |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique on `(student_id, academic_year_id)` — one enrollment per student per semester

---

### Module 4 — Fees & Fines Configuration

#### Table: `FEE_PROFILES`
> Membership fee items available in the POS per organization.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `name` | String | e.g., `"Membership Fee"`, `"Irregular Rate A"` |
| `amount` | Decimal(10,2) | Must use DECIMAL — never FLOAT |
| `category` | Enum | `REGULAR` · `IRREGULAR` · `EXTENDEE` · `EXEMPTED` |
| `is_active` | Boolean | |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

---

### Module 5 — Assessment & Payment (POS)

#### Table: `TRANSACTIONS`
> Individual payments. **One row = One fee OR one fine.**

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `or_number` | String | e.g., `"ENG-001"` |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `processed_by_user_id` | FK → `USERS(id)` | `ON DELETE RESTRICT` |
| `amount_paid` | Decimal(10,2) | Never FLOAT |
| `payment_method` | Enum | `CASH` · `GCASH` |
| `reference_number` | String | Nullable — required if `GCASH` |
| `fee_profile_id` | FK → `FEE_PROFILES(id)` | Nullable · `ON DELETE RESTRICT` |
| `transaction_type` | Enum | `FEE` · `FINE` |
| `remittance_id` | FK → `REMITTANCES(id)` | Nullable · `ON DELETE SET NULL` |
| `is_void` | Boolean | Default: `FALSE` |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:**
- Unique on `(organization_id, or_number)`
- If `transaction_type = 'FEE'` → `fee_profile_id IS NOT NULL`
- If `transaction_type = 'FINE'` → `fee_profile_id IS NULL`
- If `payment_method = 'GCASH'` → `reference_number IS NOT NULL`

**Indexes:**
```sql
INDEX (organization_id, is_void, remittance_id)   -- Remittance queries
INDEX (student_id, academic_year_id)               -- Student payment history
INDEX (academic_year_id, created_at)               -- Date-range reports
```

---

#### Table: `VOID_REQUESTS`
> Two-step workflow for canceling receipts *(FR-0019)*.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `transaction_id` | FK → `TRANSACTIONS(id)` | `ON DELETE RESTRICT` |
| `requested_by_user_id` | FK → `USERS(id)` | `ON DELETE RESTRICT` |
| `approved_by_user_id` | FK → `USERS(id)` | Nullable · `ON DELETE RESTRICT` |
| `reason` | Text | |
| `status` | Enum | `PENDING` · `APPROVED` · `REJECTED` |
| `created_at` | Datetime | |
| `resolved_at` | Datetime | Nullable |

**Constraints:** Unique on `transaction_id` — one void request per transaction

---

#### Table: `OR_SEQUENCES`
> Tracks the last used OR number per organization to ensure gap-free sequencing *(FR-0018)*.

| Column | Type | Notes |
|---|---|---|
| `organization_id` | PK · FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `last_or_number` | Integer | Default: `0` |
| `updated_at` | Datetime | |

> **Implementation Note:** OR number generation must use a **row-level lock** (`SELECT FOR UPDATE`) on this table to prevent race conditions under concurrent access.

---

### Module 6 — Cash Management (Remittance)

#### Table: `REMITTANCES`
> Batched turnover of cash from officer to treasurer.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `control_number` | String | Unique · e.g., `"REM-2024-001"` |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `total_amount` | Decimal(10,2) | Sum of all grouped transactions |
| `created_by_user_id` | FK → `USERS(id)` | Stage 1 officer · `ON DELETE RESTRICT` |
| `verified_by_user_id` | FK → `USERS(id)` | Nullable · Stage 2 auditor |
| `accepted_by_user_id` | FK → `USERS(id)` | Nullable · Stage 3 treasurer |
| `status` | Enum | `PENDING` · `VERIFIED` · `ACCEPTED` |
| `created_at` | Datetime | |
| `verified_at` | Datetime | Nullable |
| `accepted_at` | Datetime | Nullable |

---

### Module 7 — Logs

#### Table: `AUDIT_LOGS`
> Immutable history trail of all system actions *(FR-0025)*.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `user_id` | FK → `USERS(id)` | `ON DELETE RESTRICT` |
| `action` | String | e.g., `"TRANSACTION_CREATED"`, `"VOID_APPROVED"` |
| `entity_type` | String | e.g., `"TRANSACTION"`, `"REMITTANCE"`, `"USER"` |
| `entity_id` | Integer | Nullable — ID of the affected record |
| `details` | JSONB / Text | Structured log data (old/new values) |
| `ip_address` | String | |
| `timestamp` | Datetime | |

**Indexes:**
```sql
INDEX (user_id, timestamp)         -- User activity queries
INDEX (entity_type, entity_id)     -- Entity-specific audit trail
INDEX (timestamp)                  -- Date-range queries
```

> **Scaling Note:** Consider **yearly partitioning** on `timestamp` after 5+ years of data accumulation *(NFR-013, NFR-014)*.

---

## Relationships

### 1 · University Hierarchy

```
COLLEGES  1──────────N  DEPARTMENTS
              "One college contains many departments"
              A department belongs to exactly one college

DEPARTMENTS  1──────────N  PROGRAMS
               "One department offers many programs"
               A program belongs to exactly one department

PROGRAMS  1──────────N  STUDENT_ENROLLMENTS
            "One program has many enrollment records over time"
```

---

### 2 · Organization & Access

```
COLLEGES  1──────────N  ORGANIZATIONS          (via linked_college_id)
DEPARTMENTS  1───────N  ORGANIZATIONS          (via linked_department_id)
ORGANIZATIONS  1─────N  USERS                  (org isolation: FR-0006)
ORGANIZATIONS  1─────N  FEE_PROFILES           (org-specific fees: FR-0011)
ORGANIZATIONS  1─────N  TRANSACTIONS           (cross-org transactions forbidden)
ORGANIZATIONS  1─────N  REMITTANCES
ORGANIZATIONS  1─────1  OR_SEQUENCES           (gap-free sequencing: FR-0018)
```

---

### 3 · Student Data

```
STUDENTS  1──────────N  STUDENT_ENROLLMENTS    (one per semester; history preserved)
STUDENTS  1──────────N  USERS                  (one officer account per org served)
STUDENTS  1──────────N  TRANSACTIONS           (payment history is permanent)
ACADEMIC_YEARS  1────N  STUDENT_ENROLLMENTS    (semester-specific enrollment)
```

---

### 4 · User Access Control

```
USERS  1────────────N  USER_PERMISSIONS        (chairperson manages: FR-0005)
PERMISSIONS  1───────N  USER_PERMISSIONS       (immutable permission definitions)
USERS  1────────────N  TRANSACTIONS            (processed_by_user_id)
USERS  1────────────N  REMITTANCES             (creator / verifier / acceptor)
USERS  1────────────N  VOID_REQUESTS           (requester / approver)
USERS  1────────────N  AUDIT_LOGS
```

---

### 5 · Financial Transactions

```
ACADEMIC_YEARS  1────N  TRANSACTIONS           (semester-scoped: FR-0022)
FEE_PROFILES  1──────N  TRANSACTIONS           (FEE type only; NULL for FINE)
TRANSACTIONS  1──────01  VOID_REQUESTS         (at most one void per transaction)
TRANSACTIONS  N──────01  REMITTANCES           (unremitted = NULL; batched = FK set)
```

---

### 6 · Remittance

```
ACADEMIC_YEARS  1────N  REMITTANCES            (cash management per semester)
REMITTANCES  1───────N  TRANSACTIONS           (one batch groups many transactions)
```

---

### 7 · Audit Trail

```
AUDIT_LOGS  N────────1  USERS                  (attributed to performing user)
AUDIT_LOGS  ─────────→  Any Entity             (polymorphic: entity_type + entity_id)
```

---

## Quick Reference — FR to Table Mapping

| FR | Requirement | Primary Table(s) |
|---|---|---|
| FR-0001 | 3-Tier Hierarchy | `COLLEGES`, `DEPARTMENTS`, `PROGRAMS` |
| FR-0002 | Semester Management | `ACADEMIC_YEARS` |
| FR-0003 | Org Scope | `ORGANIZATIONS` |
| FR-0004 | Auth & Lockout | `USERS` |
| FR-0005 | Permissions | `USER_PERMISSIONS`, `PERMISSIONS` |
| FR-0006 | Org Isolation | `USERS` (org scope enforced in queries) |
| FR-0007 | Student Identity | `STUDENTS` |
| FR-0008 | Bulk Import | `STUDENTS`, `STUDENT_ENROLLMENTS` |
| FR-0009 | Manual Entry | `STUDENTS`, `STUDENT_ENROLLMENTS` |
| FR-0010 | Membership Logic | `STUDENT_ENROLLMENTS` (derived at query) |
| FR-0011 | Fee Profiles | `FEE_PROFILES` |
| FR-0012 | Fee Categories | `FEE_PROFILES` (category enum) |
| FR-0013 | Manual Fine POS | `TRANSACTIONS` (FINE type, no pre-encoding) |
| FR-0014 | Student Search | `STUDENT_ENROLLMENTS` + `STUDENTS` |
| FR-0015 | Dynamic Assessment | `FEE_PROFILES`, `TRANSACTIONS` |
| FR-0016 | Payment Methods | `TRANSACTIONS` |
| FR-0017 | Single-Item TX | `TRANSACTIONS` (one fee per row) |
| FR-0018 | Gap-Free OR | `OR_SEQUENCES` |
| FR-0019 | Void Workflow | `VOID_REQUESTS`, `TRANSACTIONS` |
| FR-0020 | Smart Remittance | `REMITTANCES`, `TRANSACTIONS` |
| FR-0021 | 3-Stage Verification | `REMITTANCES` |
| FR-0022 | Semester Financials | `TRANSACTIONS`, `REMITTANCES` |
| FR-0023 | Digital Receipts | `TRANSACTIONS` (PDF generation) |
| FR-0024 | Financial Reports | `TRANSACTIONS`, `FEE_PROFILES` |
| FR-0025 | Audit Logs | `AUDIT_LOGS` |

---

*FCATS Requirements Specification v1.0 · Converted from PDF*
