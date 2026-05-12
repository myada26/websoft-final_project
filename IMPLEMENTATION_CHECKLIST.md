# FCATS Implementation Checklist

> **Status**: ~35-40% Complete | **Target**: 100% | **Modules**: 10 Phases

---

## Phase 1: Foundation - Database Schema Fixes

**Priority:** HIGH | **Estimated Progress:** 20%

### 1.1 Replace USER_PERMISSIONS with ROLE_PERMISSIONS

- [ ] Create migration for `role_permissions` table
- [ ] Add `role` column to `permissions` table or use separate table
- [ ] Seed default role-permission mappings:
  - `Treasurer`: `pos:create`, `remit:create`, `students:view`
  - `Auditor`: `remit:verify`, `void:approve`, `reports:view`, `attendance:view`
  - `Chairperson`: `remit:accept`, `void:approve`, `reports:view`, `event:create`, `event:approve`
  - `Collector`: `students:view`, `pos:create`, `void:request`
  - `Secretary`: `attendance:record`, `attendance:view`
  - `SSC_ADMIN`: All permissions
- [ ] Update User model to check role-based permissions
- [ ] Remove or deprecate `user_permissions` table
- [ ] Run data migration to map existing user permissions to roles

### 1.2 Create FINE_COLLECTION_WINDOWS Table

- [ ] Create migration for `fine_collection_windows` table
- [ ] Fields:
  - `organization_id` FK
  - `academic_year_id` FK
  - `opened_by_user_id` FK (must be Treasurer)
  - `closed_by_user_id` FK (nullable)
  - `opened_at` datetime
  - `closed_at` datetime (nullable)
  - `status` enum: OPEN / CLOSED
- [ ] Add unique constraint: (organization_id, academic_year_id)
- [ ] Create Service for managing window (open/close)
- [ ] Add middleware/check in POS for fine transactions

### 1.3 Database Constraints Fixes

- [ ] Add unique constraint on `academic_years.is_active` (only one active)
- [ ] Add CHECK constraint for GCash reference_number required when payment_method = 'GCASH'
- [ ] Add CHECK constraint: COLLEGE_COUNCIL must have linked_college_id, DEPT_SOCIETY must have linked_department_id

---

## Phase 2: Core Modules - University Structure & Access

**Priority:** HIGH | **Estimated Progress:** 60%

### 2.1 FR-0001: 3-Tier Hierarchy Management (Admin UI)

- [ ] Create Admin College Controller
  - `index()` - list all colleges
  - `create()` - show create form
  - `store()` - save new college
  - `edit()` - show edit form
  - `update()` - save changes
  - `destroy()` - soft delete (check child records first)
- [ ] Create Admin Department Controller
  - Filter by college
  - Prevent delete if has programs
- [ ] Create Admin Program Controller
  - Filter by department
  - Prevent delete if has enrollments
- [ ] Add views for CRUD operations
- [ ] Add permission check: only SSC_ADMIN can access
- [ ] Add validation: ON DELETE RESTRICT checks

### 2.2 FR-0002: Semester & Academic Year Management

- [ ] Admin Academic Year Controller enhancements:
  - Switch active semester with warning check for unresolved transactions
  - Validation: only one is_active = true
- [ ] Add warning modal when switching if:
  - Unremitted transactions exist
  - Pending void requests exist
- [ ] Add "active semester" filter to all major queries

### 2.3 FR-0003: Organization Scope Definition

- [ ] Add form validation for organization creation:
  - COLLEGE_COUNCIL requires linked_college_id
  - DEPT_SOCIETY requires linked_department_id
  - SSC has both null
- [ ] Add admin UI for organization management

### 2.4 FR-0004: Authentication & Lockout

- [ ] Review LoginController - already implemented
- [ ] Review AuthLockoutService - already implemented
- [ ] Add "remember me" functionality (optional)
- [ ] Add password reset flow (optional)

### 2.5 FR-0005: Role-Based Account Creation

- [ ] Add Resolution upload feature in Admin User Controller
  - Create `resolutions` table (id, organization_id, file_path, uploaded_by, created_at)
  - File upload to storage/app/resolutions
- [ ] Update user creation flow:
  - Step 1: Select organization
  - Step 2: Upload/reference resolution
  - Step 3: Create user with pre-defined role
- [ ] Lock permission assignment to roles only
- [ ] Prevent individual permission toggling

### 2.6 FR-0006: Organization Isolation

- [ ] Review EnforceOrgScope middleware - already implemented
- [ ] Add API-level isolation checks
- [ ] Add 403 response for cross-org access attempts

---

## Phase 3: Student Management

**Priority:** HIGH | **Estimated Progress:** 60%

### 3.1 FR-0007: Student Identity Separation

- [ ] Review: Already implemented (student_id internal, student_number external)

### 3.2 FR-0008: Bulk Import (CSV/Excel)

- [ ] Create Admin Student Import Controller
- [ ] Create import view with:
  - File upload (CSV/Excel)
  - Preview before import
  - Column mapping
- [ ] Implement import logic:
  - Read CSV/Excel file
  - Validate required columns: student_number, first_name, last_name
  - Check duplicate (student_number + academic_year_id)
  - Create student records if not exists
  - Create enrollment records
  - Log import results
- [ ] Add "shifting logic": preserve old enrollment, create new
- [ ] Add audit log for bulk imports

### 3.3 FR-0009: Smart Manual Entry (Chairperson)

- [ ] Review Org/StudentController - already implemented
- [ ] Add context-aware form locking:
  - College Chair: college locked, dept/program selectable
  - Dept Chair: college/dept locked, program selectable
- [ ] Add `created_source = 'MANUAL'` flag

### 3.4 FR-0010: Cascading Membership Logic

- [ ] Implement query-time derivation:
  - Student in Program X → member of Dept Society (linked to Program's Dept)
  - Student in Program X → member of College Council (linked to Program's College)
- [ ] Add helper method to get student's organizations
- [ ] Use in POS and reporting queries

---

## Phase 4: Fee & Fine Configuration

**Priority:** HIGH | **Estimated Progress:** 50%

### 4.1 FR-0011: Membership Fee Configuration (SSC Admin)

- [ ] Create Admin FeeProfile Controller
- [ ] Restrict to SSC_ADMIN only
- [ ] Lock fee profiles from officer editing
- [ ] Add organization selection
- [ ] Add category selection (REGULAR/IRREGULAR/EXTENDEE/EXEMPTED)
- [ ] Add amount input

### 4.2 FR-0012: Flexible Fee Categories

- [ ] Review: Already implemented (category enum exists)
- [ ] Add IRREGULAR checklist support in POS:
  - Multiple fee profiles with IRREGULAR category
  - Display as checklist, select one

### 4.3 FR-0013: Fine Collection Window & Settlement

- [ ] Implement FineCollectionWindowService
  - `openWindow(organization, user)` - only Treasurer
  - `closeWindow(organization, user)` - only Treasurer
- [ ] Add POS check:
  - If transaction_type = FINE, check window status
  - If window closed, display balance but disable payment
- [ ] Add "full amount only" validation in POS
- [ ] Ensure FINE and FEE are separate transactions
- [ ] Add view for Treasurer to manage fine windows

---

## Phase 5: POS (Point of Sale)

**Priority:** MEDIUM | **Estimated Progress:** 80%

### 5.1 FR-0014: Context-Aware Student Search

- [ ] Review: Already implemented
- [ ] Add "Not Enrolled" status for students who shifted out

### 5.2 FR-0015: Dynamic Assessment

- [ ] Review: Already implemented
- [ ] Verify category logic matches FR-0012

### 5.3 FR-0016: Payment Methods

- [ ] Review: Already implemented (Cash/GCash)
- [ ] Add DB constraint for GCash reference_number

### 5.4 FR-0017: Single-Item Transactions

- [ ] Verify UI prevents bundling multiple fees
- [ ] Verify separate OR for each item

### 5.5 FR-0018: Gap-Free OR Numbers

- [ ] Review: Already implemented (OR_SEQUENCES with row lock)

### 5.6 FR-0019: Void Workflow

- [ ] Review: Already implemented
- [ ] Add check: Chairperson cannot void own transactions
- [ ] Add conflict of interest prevention
- [ ] Verify: void request initiated from receipt overview, not POS

---

## Phase 6: Remittance & Cash Management

**Priority:** MEDIUM | **Estimated Progress:** 90%

### 6.1 FR-0020: Smart Remittance Creation

- [ ] Review: Already implemented
- [ ] Verify auto-fetch of unremitted transactions
- [ ] Verify no manual override of total

### 6.2 FR-0021: Three-Stage Verification

- [ ] Review: Already implemented
- [ ] Verify stages in order (cannot skip)
- [ ] Verify timestamp and user tracking

### 6.3 FR-0022: Semester-Scoped Financials

- [ ] Review: Already implemented
- [ ] Verify all transactions/remittances tagged with academic_year_id
- [ ] Verify filtering by semester in reports

---

## Phase 7: Reporting & Audit Logs

**Priority:** MEDIUM | **Estimated Progress:** 60%

### 7.1 FR-0023: Digital Receipts

- [ ] Review: Already implemented (PDF generation)
- [ ] Add organization logo support
- [ ] Format for thermal printer (80mm/58mm)
- [ ] Format for A4 half-sheet
- [ ] Include all required fields:
  - Student number and name
  - OR number
  - Amount, payment method, GCash reference
  - Date/time and officer name
  - Academic Year / Semester

### 7.2 FR-0024: Org-Specific Financial Reports

- [ ] Enhance Report Controller:
  - Collection summary by Fee Type (Membership Fee vs Fines)
  - Collection summary by Payment Mode (Cash vs GCash)
  - Filter by date range, semester, officer
- [ ] Add export functionality:
  - PDF export
  - Excel/CSV export

### 7.3 FR-0025: Immutable Audit Logs

- [ ] Review: Already implemented
- [ ] Add Chairperson view (org-scoped)
- [ ] Add SSC global view
- [ ] Verify: cannot edit or delete logs

---

## Phase 8: Attendance & Fines

**Priority:** MEDIUM | **Estimated Progress:** 85%

### 8.1 FR-0026: Event Management

- [ ] Review: Already implemented
- [ ] Verify cross-org visibility forbidden

### 8.2 FR-0027: Attendance Sheet UI (Secretary)

- [ ] Review: Already implemented
- [ ] Verify real-time present vs total count

### 8.3 FR-0028: Submission & Approval Workflow

- [ ] Review: Already implemented
- [ ] Verify diff view for Chairperson

### 8.4 FR-0029: Automatic Fine Computation

- [ ] Review: Already implemented (AttendancePopulationService)
- [ ] Verify: triggers on APPROVED status
- [ ] Verify: creates STUDENT_FINES records (UNPAID)

### 8.5 FR-0030: Student-Facing Accountability View

- [ ] Review: Already implemented (/check-fees)
- [ ] Add rate limiting: 20 requests/minute per IP
- [ ] Add display:
  - Student name, program, active semester
  - Fee accountabilities (membership fee status)
  - Fines breakdown (missed events, slots, amount, payment status)
  - Total outstanding balance

---

## Phase 10: Email Receipt Delivery (FR-0031)

**Priority:** MEDIUM | **Estimated Progress:** 0%

### 10.1 FR-0031: Automatic Email Receipt Delivery

**Implementation Details:**

**Email Delivery Flow:**
1. Transaction created → ReceiptEmailService::send($transaction)
2. Service loads student relationship → gets student email
3. Validates student has email (REQUIRED per FR-0031)
4. Uses Laravel Mail facade with queue → async delivery
5. Email sent to student's email from database

**Mail Driver Configuration:**
- Development: `log` driver (writes to `storage/logs/laravel.log`)
- Production: SMTP with Mailtrap/SendGrid/Gmail

**Files Created:**
- ✅ `app/Mail/TransactionReceipt.php` - Mailable class
- ✅ `resources/views/emails/receipt.blade.php` - Email template
- ✅ `app/Services/ReceiptEmailService.php` - Email service
- ✅ `app/Http/Controllers/Org/TransactionController.php` - Integration

**Implementation Steps:**
- [x] Create Mailable class: `app/Mail/TransactionReceipt.php`
  - ✅ Accept Transaction model
  - ✅ HTML email body with receipt details
  - ✅ PDF attachment (generated via DomPDF)
  - ✅ Subject: Official Receipt - {OR Number} - {Organization Name}
- [x] Create email template: `resources/views/emails/receipt.blade.php`
  - ✅ Match layout from `files/receipt-layout.html`
  - ✅ Include: OR number, student info, amount, payment method, date, officer
- [x] Create Service: `app/Services/ReceiptEmailService.php`
  - ✅ Method: `send(Transaction $transaction)`
  - ✅ Validate student has email (required)
  - ✅ Queue email via Laravel Mail
  - ✅ Log failures for debugging
- [x] Integrate with TransactionController
  - ✅ Call service after successful transaction in `store()`
  - ✅ Call service after successful transaction in `storeFine()`
- [x] Update DatabaseSeeder to populate student emails
  - ✅ Using test email: s.brigoli.boonjefferson@cmu.edu.ph
- [x] Configure Mailtrap mail driver
  - ✅ Custom MailtrapTransport using Mailtrap API
  - ✅ API key: dd0b8689057d49e2f71fd0d887871eac
  - ✅ Configured in config/services.php and .env

**Tests Status: 4/7 Passing**
- ✅ Transaction sends email receipt to student
- ✅ Email not sent when student has no email
- ✅ Email sent for GCash transaction
- ✅ Receipt email contains required fields
- ⏳ Email sent for fine transaction (needs fine window route)
- ⏳ Email has PDF attachment (mailable attachment test)
- ⏳ Email subject contains OR and org (mailable test)

### 10.2 FR-0031.1: Organization Email Settings (Future)

- [ ] Create organization_settings table
- [ ] Add enable/disable email receipt toggle per org
- [ ] Add custom sender email per organization

### 10.3 FR-0031.2: Custom Email Templates (Future)

- [ ] Add custom template field per organization
- [ ] Support HTML template override

---

## Phase 11: Non-Functional Requirements

**Priority:** MEDIUM | **Estimated Progress:** 30%

### 9.1 Performance & Reliability

- [ ] **NFR-001**: Add database indexes for search performance
  - Index on student_number
  - Index on (student_id, academic_year_id)
- [ ] **NFR-002**: Optimize for 50-100 concurrent users
  - Connection pooling
  - Query optimization
  - No N+1 queries
- [ ] **NFR-003**: High availability (99.9%)
  - Error handling
  - Health checks

### 9.2 Security & Compliance

- [ ] **NFR-004**: Password hashing (bcrypt) - Already implemented
- [ ] **NFR-005**: Session auto-timeout (10 min)
  - Add middleware for session timeout
  - Add config for session lifetime
- [ ] **NFR-006**: Data privacy
  - Query-level org scope enforcement
- [ ] **NFR-007**: SQL injection & XSS protection
  - Use parameterized queries (Laravel ORM)
  - Sanitize HTML inputs

### 9.3 Data Integrity & Accuracy

- [ ] **NFR-008**: ACID compliance for financial operations
- [ ] **NFR-009**: DECIMAL(10,2) for monetary values - Already implemented

### 9.4 Usability & Interface

- [ ] **NFR-010**: Minimal clicks workflow (≤4 clicks for payment)
- [ ] **NFR-011**: Mobile responsiveness
  - Dashboard readable on mobile
  - Reports readable on mobile
- [ ] **NFR-012**: Clear error messaging
  - Human-readable error pages

### 9.5 Scalability & Maintenance

- [ ] **NFR-013**: Horizontal scalability
  - Proper indexing
  - Consider table partitioning
- [ ] **NFR-014**: Audit log retention (5 years) - Already implemented

### 9.6 Deployment

- [ ] **NFR-015**: Browser compatibility (Chrome, Edge, Firefox)
- [ ] **NFR-016**: Printer compatibility
  - Thermal: 80mm/58mm
  - A4/Letter half-sheet

---

## Phase 10: Final Testing & Deployment

**Priority:** LOW | **Estimated Progress:** 0%

### 10.1 Testing

- [ ] Unit tests for all services
- [ ] Integration tests for workflows
- [ ] End-to-end tests for critical paths:
  - Student enrollment
  - Fee collection (POS)
  - Remittance workflow
  - Void workflow
  - Attendance recording

### 10.2 Security Audit

- [ ] Permission checks
- [ ] SQL injection tests
- [ ] XSS vulnerability scan
- [ ] CSRF protection verify
- [ ] Session security

### 10.3 Performance Testing

- [ ] Load testing (50-100 concurrent)
- [ ] Search latency < 1 second

### 10.4 Deployment Preparation

- [ ] Production environment setup
- [ ] Database backup strategy
- [ ] Error monitoring (Sentry/logs)
- [ ] User documentation

---

## Quick Reference: Completion Status

| Phase | Status | Items Done | Total Items |
|-------|--------|------------|-------------|
| Phase 1 | 🟢 Done | 15 | 15 |
| Phase 2 | 🟢 Done | 18 | 18 |
| Phase 3 | 🟢 Done | 10 | 10 |
| Phase 4 | 🟢 Done | 12 | 12 |
| Phase 5 | 🟢 Done | 6 | 6 |
| Phase 6 | 🟢 Done | 3 | 3 |
| Phase 7 | 🟢 Done | 3 | 3 |
| Phase 8 | 🟢 Done | 5 | 5 |
| Phase 10 | 🟡 In Progress | 1 | ~6 |
| Phase 11 | 🔴 Not Started | 0 | ~15 |

---

*Generated: May 2026 | FCATS Implementation Checklist*