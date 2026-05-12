# 📄 Functional & Non-Functional Requirements
### Fee Collection and Tracking System (FCATS)
> **Document Type:** Requirements Specification + Database Schema  
> **Scope:** Student Council Fee Collection — University-Level  
> **Modules:** 8 Functional Modules · 16 NFRs · 19 Tables

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
   - [Module 8 — Attendance & Absence Fine Management](#module-8--attendance--absence-fine-management)
   - [Module 9 — Email Receipt Delivery](#module-9--email-receipt-delivery)
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

#### FR-0005 · Role-Based Account Creation & Permission Management
> **Actor:** SSC Admin 

**Core Concept:** Before any officer account is created, the organization must first submit a **Resolution** — a formal document listing the names and designated roles of officers authorized to collect fees (e.g., Treasurer, Chairperson) - personnaly. The SSC Admin reviews this resolution and uses it as the basis for account creation.

- [ ] **Centralized Creation:** The SSC Admin is the sole authority for creating officer accounts across all organizations. No officer can self-register.
- [ ] **Resolution Requirement:** The SSC Admin must attach or reference the approved Resolution before creating accounts for an organization.
- [ ] **Role-Driven Access:** When creating an account, the SSC Admin assigns a fixed role (e.g., Treasurer, Chairperson, Auditor). Each role comes with pre-defined, non-editable permissions.
- [ ] **No Individual Toggling:** Individual permission toggling is removed. Roles are assigned at account creation and can only be changed by the SSC Admin.
- [ ] **Organization Isolation:** An officer account is tied to a specific organization and cannot operate outside it.

**Fixed Role → Permission Mapping:**
| Role | Permissions Granted |
|---|---|
| **Treasurer** | `pos:create`, `remit:create` |
| **Auditor** | `remit:verify`, `void:approve`, `reports:view` |
| **Chairperson** | `remit:accept`, `void:approve`, `reports:view`, `event:create`, `event:approve` |

#### FR-0006 · Organization Isolation

- [ ] Every user is **strictly scoped** to their Organization
- [ ] An Engineering officer **cannot** view, search, or modify data belonging to Nursing
- [ ] Isolation must be enforced at the **API/query level**, not just the UI level
- [ ] Attempting cross-org access returns a `403 Forbidden` response

---

### Module 3 — Student Data & Enrollment *(Semester-Based)*

#### FR-0007 · Student Identity Separation

- [ ] System uses a hidden internal `student_id` (PK) for all database relationships
- [ ] Officers search using the user-facing `student_number` (School ID, e.g., `2023123456`)
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

  Example
    Student: Maria Santos (2024-0042)
    Enrolled in: BS Civil Engineering
                └── Department of Civil Engineering
                      └── College of Engineering
    Active Semester: 1st Semester 2024-2025

→ Automatically a member of:
     · Engineering Department Society    (linked to: Dept of Civil Engineering)
     · College of Engineering Council   (linked to: College of Engineering)
```

- [ ] Membership derivation runs at query time (not stored as a separate flag)
- [ ] If a student is not enrolled in the Active Semester, they have **no active membership**

---

### Module 4 — Fee & Fine Configuration

#### FR-0011 · Membership Fee Configuration *(SSC Admin Only)*
> **Actor:** SSC Admin *

**Core Concept:** The SSC Admin is the sole authority for creating and managing fee profiles for all organizations. Officers and Chairpersons have no access to create, edit, or deactivate fee amounts — they can only collect based on what has been configured.

- [ ] SSC Admin encodes the approved fee amounts per organization, per student category, per semester.
- [ ] Once saved, fee profiles are locked — no officer-level role can modify them.
- [ ] During a POS transaction, the fee amount is automatically fetched and read-only.
- [ ] The officer only selects the student category (Regular, Irregular, Extendee) — the amount populates itself.

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

#### FR-0013 · Fine Collection Window & Settlement Rules
> **Actor:** Treasurer (Window Management) / Officer (POS)

**Core Concept:** Fine collection is under the exclusive jurisdiction of the Treasurer. The Treasurer controls when fine collection opens and closes for the semester. Once opened, other authorized collectors can also process fine payments. When a student pays, they must settle their entire outstanding fine balance in full — no partial payments allowed.

**Collection Window Flow:**
- [ ] **Treasurer opens fine collection** for Active Semester → System unlocks `FINE` transactions for all authorized collectors.
- [ ] **Treasurer closes fine collection** → System locks `FINE` transactions; no fine payments can be processed until reopened.
- [ ] Fine collection status is **organization-scoped** — Engineering Treasurer opening collection does not affect Nursing.

**POS Transaction Flow & Constraints:**
- [ ] At POS, the officer views the student's **Total Outstanding Fine Balance** (read-only, system-computed from accumulated absence records).
- [ ] **Full amount only:** Students must pay their full outstanding fine balance — no partial payments allowed.
- [ ] If fine collection is closed, the FINES section is visible but locked — officers can see the balance but cannot process payment.
- [ ] Fine transactions have `transaction_type = 'FINE'`.
- [ ] **Single-Item Receipts:** Fine and Membership Fee are always separate transactions with separate OR numbers.

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

- [ ] OR numbers are **sequential and organization-specific**: `ENG-00001`, `ENG-00002`, `NURS-001`
- [ ] Generated using a **database-level sequence lock** on the `OR_SEQUENCES` table to prevent gaps
- [ ] Format: `{ORG_CODE}-{PADDED_NUMBER}` (e.g., `COE-00042`)
- [ ] Once issued, an OR number is **never reused**, even after voids

---

#### FR-0019 · Void Workflow

> **Actor (Step 1):** Treasurer or Collector · **Actor (Step 2):** Chairperson

**Core Concept:** A void request is initiated by searching through transaction history, not from the POS screen. The officer locates the specific receipt, views its details, and submits the void from there. The Chairperson is the sole approving authority.

**Two-step flow:**
- **Step 1 — REQUEST**
  - Treasurer or Collector navigates to Transaction History
  - Searches for the transaction → Clicks to open Receipt Overview
  - Clicks "Request Void" and enters a reason
  - Transaction marked: `PENDING VOID` (still valid, not yet voided)
  - Chairperson receives a notification of the pending request
- **Step 2 — APPROVE / REJECT**
  - Chairperson reviews the void request, views receipt details and stated reason
  - **If APPROVED:** `Transaction.is_void = TRUE`, OR number marked void (never reused/reassigned), both parties notified
  - **If REJECTED:** Transaction remains fully valid, requester notified with rejection visible on receipt overview

**Constraints:**
- [ ] Only Treasurer and Collectors (officers with `void:request` permission) can submit a void request
- [ ] Only the Chairperson can approve or reject a void request (`void:approve` permission)
- [ ] Void requests are initiated from the Receipt Overview page, not from the POS screen
- [ ] Only one void request may exist per transaction at a time
- [ ] A transaction in `PENDING` void status remains valid and visible until a decision is made
- [ ] Voided transactions are permanently retained in the database for audit purposes
- [ ] The voided OR number is marked void but never reused or reassigned
- [ ] The Chairperson cannot void their own transactions — conflict of interest prevention

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

### Module 8 — Attendance & Absence Fine Management

> **Context:** Every absence from an organization event corresponds to a fine. Each unchecked "signature" (in/out check) is worth ₱10. Fines are derived automatically from unchecked attendance boxes and appear in the student's accountability breakdown. This module feeds directly into the existing FINE transaction type in the POS.

#### FR-0026 · Event Management
> **Actor:** Chairperson (creates events), Secretary (records attendance)

- [ ] **Event Name** — string, required
- [ ] **Date** — date, required
- [ ] **Venue** — string, required
- [ ] **Time Type** — `HALF_DAY` (2 slots: in + out) or `FULL_DAY` (4 slots: AM in/out, PM in/out)
- [ ] **Start Time / End Time** — time fields, required
- [ ] **Organization / Academic Year** — auto-set to creator's org and active semester (locked)
- [ ] **Status** — `DRAFT` → `PENDING_APPROVAL` → `PENDING_CHAIRPERSON` → `APPROVED` · `REJECTED`
- [ ] Events are strictly scoped to the Chairperson's organization (cross-org visibility forbidden)

---

#### FR-0027 · Attendance Sheet UI (Secretary)
> **Actor:** Secretary (New Role: limited exclusively to `attendance:record` capability)

- [ ] UI displays a table of members with checkbox columns for each slot (2 for HALF_DAY, 4 for FULL_DAY)
- [ ] Each unchecked checkbox = one "signature" missed = ₱10 fine
- [ ] Member list is auto-pulled from `STUDENT_ENROLLMENTS` for the active semester/organization
- [ ] Secretary can check/uncheck freely while status is `DRAFT`
- [ ] Cannot submit until at least one row is interacted with
- [ ] Becomes read-only for Secretary once submitted (`PENDING_APPROVAL`)
- [ ] Displays a real-time running count of present vs total members via JS

---

#### FR-0028 · Submission & Approval Workflow
> **Actor:** Secretary (submits), Auditor (reviews), Chairperson (final approval)

**Step 1 — Submit (Secretary):**
- [ ] System prompts: "Once submitted, you cannot make changes. Proceed?"
- [ ] Changes status: `DRAFT` → `PENDING_APPROVAL`
- [ ] Audit log: `ATTENDANCE_SUBMITTED`

**Step 2 — Review (Auditor):**
- [ ] **Approve**: Status → `APPROVED` (fines computed/applied if no edits made)
- [ ] **Edit**: Auditor corrects checkboxes, generating `ATTENDANCE_EDITED_BY_AUDITOR` logs per change (capturing old/new value per slot). Status → `PENDING_CHAIRPERSON`
- [ ] **Reject**: Status → `DRAFT` with rejection reason, Secretary notified

**Step 3 — Final Approval (Chairperson):** *(Only if auditor made edits)*
- [ ] Sees diff view (Secretary original vs Auditor corrections: green=auditor marked present, red=auditor marked absent)
- [ ] **Confirm**: Status → `APPROVED` (fines computed)
- [ ] **Reject**: Sent back to Auditor

---

#### FR-0029 · Automatic Fine Computation

- [ ] Triggers immediately upon `APPROVED` status
- [ ] Iterates all member rows; `fine_amount = count_of_unchecked_slots × 10.00`
- [ ] If `fine_amount > 0`, creates a `STUDENT_FINES` record (status: `UNPAID`)
- [ ] Does **not** auto-create a POS transaction (POS flow remains manual per FR-0013)
- [ ] Audit log: `FINES_COMPUTED` (logs total fines created)

---

#### FR-0030 · Student-Facing Accountability View
> **Actor:** Student (Unauthenticated Public Portal)

- [ ] Read-only page accessible via `/check-fees` by entering `student_number`
- [ ] Rate-limited to 20 requests/minute per IP to prevent scraping
- [ ] Displays student name, program, and active semester
- [ ] Shows **Fee Accountabilities**: Membership fee status
- [ ] Shows **Fines Breakdown**: Table of missed events, slots missed, fine amount, and payment status (with OR number if paid)
- [ ] Shows **Total Outstanding Balance**

---

### Module 9 — Email Receipt Delivery

#### FR-0031 · Automatic Email Receipt Delivery

> **Actor:** System (automated on transaction completion)

**Core Concept:** Upon successful transaction, the system automatically generates and sends a digital receipt to the student's registered email address. This provides students with instant confirmation and a digital copy for their records.

**Trigger:** Transaction successfully created and persisted to database

**Email Content:**
- Organization name and logo
- Official Receipt (OR) number
- Student name and student number
- Transaction type (Fee/Fine)
- Amount paid
- Payment method (Cash/GCash)
- GCash reference number (if applicable)
- Date and time of transaction
- Processing officer name
- Academic Year / Semester

**Email Specifications:**
- [ ] Sender: Configurable per organization (e.g., `noreply@cmu.edu`)
- [ ] Subject: `Official Receipt - {OR Number} - {Organization Name}`
- [ ] Attachment: PDF receipt (same as printed version)
- [ ] Format: HTML email with embedded styles for email client compatibility

**Queue Processing:**
- [ ] Email sent via Laravel queue for non-blocking delivery
- [ ] Failed deliveries logged for retry
- [ ] Rate limiting to prevent email spam

**Configuration:**
- [ ] Enable/disable per organization (FR-0031.1)
- [ ] Custom email template support per organization (FR-0031.2)

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

#### Table: `ROLE_PERMISSIONS`
> Maps which permissions are granted to which predefined roles *(Replaces USER_PERMISSIONS, per FR-0005)*.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `role` | String | e.g., `"Treasurer"`, `"Auditor"`, `"Chairperson"` |
| `permission_id` | FK → `PERMISSIONS(id)` | `ON DELETE RESTRICT` |
| `created_at` | Datetime | |

**Constraints:** Unique on `(role, permission_id)`

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
| `email` | String | Nullable — For receipt delivery (FR-0031) |
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

#### Table: `ATTENDANCE_RECORDS`
> Tracks student absences and auto-computed fines per activity *(FR-0013)*.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `activity_name` | String | e.g., `"General Assembly - Oct 5"` |
| `status` | Enum | `PRESENT` · `ABSENT` |
| `fine_amount` | Decimal(10,2) | Snapshot of rate at time of recording |
| `recorded_by_user_id` | FK → `USERS(id)` | |
| `recorded_at` | Datetime | |

---

#### Table: `FINE_COLLECTION_WINDOWS`
> Controls when fines can be collected at POS per organization and semester *(FR-0013)*.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `opened_by_user_id` | FK → `USERS(id)` | Must be Treasurer |
| `closed_by_user_id` | FK → `USERS(id)` | Nullable |
| `opened_at` | Datetime | |
| `closed_at` | Datetime | Nullable |
| `status` | Enum | `OPEN` · `CLOSED` |

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

**New Module 8 Action Constants:**
`EVENT_CREATED`, `ATTENDANCE_SUBMITTED`, `ATTENDANCE_EDITED_BY_AUDITOR`, `ATTENDANCE_APPROVED_BY_AUDITOR`, `ATTENDANCE_SENT_TO_CHAIRPERSON`, `ATTENDANCE_APPROVED_BY_CHAIRPERSON`, `ATTENDANCE_REJECTED_BY_CHAIRPERSON`, `FINES_COMPUTED`

**Indexes:**
```sql
INDEX (user_id, timestamp)         -- User activity queries
INDEX (entity_type, entity_id)     -- Entity-specific audit trail
INDEX (timestamp)                  -- Date-range queries
```

> **Scaling Note:** Consider **yearly partitioning** on `timestamp` after 5+ years of data accumulation *(NFR-013, NFR-014)*.

---

### Module 8 — Attendance & Fines

#### Table: `EVENTS`
> Defines a scheduled activity where attendance is tracked.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `name` | String | e.g., `"General Assembly"` |
| `date` | Date | |
| `venue` | String | |
| `time_type` | Enum | `HALF_DAY` · `FULL_DAY` |
| `start_time` | Time | |
| `end_time` | Time | |
| `status` | Enum | `DRAFT` · `PENDING_APPROVAL` · `PENDING_CHAIRPERSON` · `APPROVED` · `REJECTED` |
| `created_by_user_id` | FK → `USERS(id)` | Chairperson who created it |
| `submitted_by_user_id` | FK → `USERS(id)` | Nullable — Secretary who submitted |
| `submitted_at` | Datetime | Nullable |
| `auditor_reviewed_by_user_id` | FK → `USERS(id)` | Nullable |
| `auditor_reviewed_at` | Datetime | Nullable |
| `approved_by_user_id` | FK → `USERS(id)` | Nullable — final approver |
| `approved_at` | Datetime | Nullable |
| `rejection_reason` | Text | Nullable |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

---

#### Table: `EVENT_ATTENDANCE`
> One row per student per attendance slot per event. Pre-populated on event creation.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `event_id` | FK → `EVENTS(id)` | `ON DELETE RESTRICT` |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `slot` | Enum | `MORNING_IN` · `MORNING_OUT` · `AFTERNOON_IN` · `AFTERNOON_OUT` |
| `is_present` | Boolean | Default: `FALSE` (absent) |
| `recorded_by_user_id` | FK → `USERS(id)` | Last user to set this value |
| `recorded_at` | Datetime | |

**Constraints:** Unique on `(event_id, student_id, slot)`

---

#### Table: `STUDENT_FINES`
> Computed financial liabilities resulting from absences.

| Column | Type | Notes |
|---|---|---|
| `id` | Integer | PK |
| `student_id` | FK → `STUDENTS(id)` | `ON DELETE RESTRICT` |
| `organization_id` | FK → `ORGANIZATIONS(id)` | `ON DELETE RESTRICT` |
| `event_id` | FK → `EVENTS(id)` | `ON DELETE RESTRICT` |
| `academic_year_id` | FK → `ACADEMIC_YEARS(id)` | `ON DELETE RESTRICT` |
| `slots_missed` | Integer | Count of unchecked slots |
| `fine_amount` | Decimal(10,2) | `slots_missed` × 10.00 |
| `status` | Enum | `UNPAID` · `PAID` |
| `transaction_id` | FK → `TRANSACTIONS(id)` | Nullable — set when paid via POS |
| `created_at` | Datetime | |
| `updated_at` | Datetime | |

**Constraints:** Unique on `(student_id, event_id)` — one fine record per student per event

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
PERMISSIONS  1───────N  ROLE_PERMISSIONS       (immutable role-to-permission mapping: FR-0005)
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

### 8 · Attendance & Fines

```text
EVENTS  1──────────N  EVENT_ATTENDANCE       (one event has many attendance slots)
STUDENTS  1────────N  EVENT_ATTENDANCE       (one student has many attendance records)
EVENTS  1──────────N  STUDENT_FINES          (one event can generate many fines)
STUDENTS  1────────N  STUDENT_FINES          (one student can have many fines)
STUDENT_FINES  1───01  TRANSACTIONS          (linked to POS payment via service logic)
```

---

## Quick Reference — FR to Table Mapping

| FR | Requirement | Primary Table(s) |
|---|---|---|
| FR-0001 | 3-Tier Hierarchy | `COLLEGES`, `DEPARTMENTS`, `PROGRAMS` |
| FR-0002 | Semester Management | `ACADEMIC_YEARS` |
| FR-0003 | Org Scope | `ORGANIZATIONS` |
| FR-0004 | Auth & Lockout | `USERS` |
| FR-0005 | Role-Based Permissions | `ROLE_PERMISSIONS`, `PERMISSIONS` |
| FR-0006 | Org Isolation | `USERS` (org scope enforced in queries) |
| FR-0007 | Student Identity | `STUDENTS` |
| FR-0008 | Bulk Import | `STUDENTS`, `STUDENT_ENROLLMENTS` |
| FR-0009 | Manual Entry | `STUDENTS`, `STUDENT_ENROLLMENTS` |
| FR-0010 | Membership Logic | `STUDENT_ENROLLMENTS` (derived at query) |
| FR-0011 | Fee Profiles | `FEE_PROFILES` |
| FR-0012 | Fee Categories | `FEE_PROFILES` (category enum) |
| FR-0013 | Fine Settlement Rules | `ATTENDANCE_RECORDS`, `TRANSACTIONS`, `FINE_COLLECTION_WINDOWS` |
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
| FR-0026 | Event Management | `EVENTS` |
| FR-0027 | Attendance Sheet UI | `EVENT_ATTENDANCE` |
| FR-0028 | Submission & Approval | `EVENTS`, `AUDIT_LOGS` |
| FR-0029 | Fine Computation | `STUDENT_FINES` |
| FR-0030 | Accountability View | `STUDENT_FINES`, `TRANSACTIONS` |

---

*FCATS Requirements Specification v1.0 · Converted from PDF*
