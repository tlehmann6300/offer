# SQL Schema Documentation

This document describes the complete database schema for the IBC Intranet application, organized across three separate databases.

## Overview

The application uses a **three-database architecture**:

1. **User Database (dbs15253086)** - Authentication and user management
2. **Content Database (dbs15161271)** - Main application data
3. **Invoice Database (dbs15251284)** - Invoice management

## Database: dbs15253086 (User Database)

### Tables (4 total)

1. **users** - Core user accounts with authentication and security
   - Primary authentication table
   - Includes role management, 2FA settings, account locking
   
2. **invitation_tokens** - Token-based invitation system
   - Manages invitation links for new users
   - Tracks who created the invitation
   
3. **email_change_requests** - Token-based email change verification
   - Handles secure email change workflow
   - Linked to users table with foreign key
   
4. **user_sessions** - Optional session tracking for security auditing
   - Tracks active user sessions
   - Used for maintenance cleanup operations

## Database: dbs15161271 (Content Database)

### Tables (21 total)

#### User Management
1. **alumni_profiles** - Extended profile data for all users
   - Additional user information beyond authentication
   - Industry, company, graduation details

#### Project Management (4 tables)
2. **projects** - Project management with type classification
   - Client information, priority, status tracking
   - Supports internal and external projects
   
3. **project_applications** - User applications to join projects
   - Manages the application process
   
4. **project_assignments** - User assignments to projects with roles
   - Tracks actual project team members
   
5. **project_files** - Project file attachments
   - Document management for projects

#### Inventory Management (5 tables)
6. **inventory_items** - Inventory items with EasyVerein integration
   - Complete item details with category and location references
   - Includes stock tracking and EasyVerein sync fields
   
7. **categories** - Categories for inventory items
   - Organizational grouping with color coding
   
8. **locations** - Storage locations for inventory items
   - Physical location tracking
   
9. **rentals** - Active and historical rentals of inventory items
   - Tracks items borrowed by users
   - Includes return status and defect tracking
   
10. **inventory_history** - Audit log for all inventory changes
    - Complete change tracking (create, update, delete, checkout, return)
    - Stock level history

#### Event Management (6 tables)
11. **events** - Events and activities management
    - Full event details with registration periods
    - Support for helper signups
    
12. **event_registrations** - User registrations for events
    - Basic event attendance tracking
    
13. **event_helper_types** - Types of helper roles needed for events
    - Defines different volunteer positions
    
14. **event_slots** - Time slots for event helper signups
    - Specific time periods needing helpers
    
15. **event_signups** - User signups for event helper slots
    - Tracks volunteer commitments
    
16. **event_roles** - Allowed roles for event participation
    - Access control for events
    
17. **event_history** - Audit log for event changes and activities
    - Tracks all event modifications

#### Blog/News (3 tables)
18. **blog_posts** - Blog posts with categorization
    - Content management for internal blog
    
19. **blog_comments** - User comments on blog posts
    - Comment system with user tracking
    
20. **blog_likes** - User likes on blog posts
    - Simple like/reaction system

#### System
21. **system_logs** - System-wide audit log for security and tracking
    - Comprehensive activity logging
    - User actions, IP addresses, timestamps

## Database: dbs15251284 (Invoice Database)

### Tables (1 total)

1. **invoices** - Invoice management system
   - Invoice submission and approval workflow
   - File attachment support

## Recent Schema Fixes

### Added Missing Tables
- ✅ `categories` - Referenced in inventory code but missing from schema
- ✅ `locations` - Referenced in inventory code but missing from schema
- ✅ `rentals` - Used for inventory checkouts (replaced conflicting `inventory_rentals`)
- ✅ `inventory_history` - Audit trail (replaced `inventory_transactions`)
- ✅ `project_assignments` - Project team membership
- ✅ `event_helper_types` - Event volunteer types
- ✅ `event_slots` - Time slots for volunteers
- ✅ `event_signups` - Volunteer signups
- ✅ `event_roles` - Event access control
- ✅ `event_history` - Event audit trail
- ✅ `system_logs` - System-wide audit log
- ✅ `user_sessions` - Session tracking

### Fixed Existing Table Schemas

#### inventory_items
- ✅ Added `category_id` (INT, FK to categories)
- ✅ Added `location_id` (INT, FK to locations)
- ✅ Added `serial_number`, `status`, `min_stock`, `unit`, `unit_price`, `purchase_date`
- ✅ Added `is_archived_in_easyverein` flag
- ✅ Removed legacy `category` and `location` VARCHAR fields

#### projects
- ✅ Added `client_name` and `client_contact_details`
- ✅ Added `priority` ENUM field
- ✅ Added `max_consultants` for team size limit
- ✅ Added `image_path` and `documentation` fields

#### events
- ✅ Renamed `start_date`/`end_date` to `start_time`/`end_time` (DATETIME)
- ✅ Added `registration_start` and `registration_end` for signup windows
- ✅ Added `maps_link`, `contact_person`, `is_external`, `external_link`
- ✅ Added `needs_helpers` flag
- ✅ Updated status ENUM to match code: 'planned', 'open', 'closed', 'running', 'past'

#### invitation_tokens
- ✅ Renamed from `user_invitations` (was wrong table name)
- ✅ Added `created_by` field to track who sent invitation

### Removed Duplicate/Incorrect Tables
- ❌ Removed `inventory_transactions` (actual name is `inventory_history`)
- ❌ Removed `inventory_rentals` (actual name is `rentals`)

## Known Issues

### Code Reference Issues
The following issues were identified but are **code bugs**, not schema issues:

1. **EasyVereinSync.php** - Uses "inventory" instead of "inventory_items" in some queries
   - This is inconsistent and should be fixed in the code
   - The correct table name is `inventory_items`

## Deployment

Use `finalize_production_setup_v2.php` to deploy all three database schemas:

1. Navigate to: `https://your-domain.de/finalize_production_setup_v2.php`
2. Follow the deployment wizard
3. Delete the setup file after successful deployment

The script has been updated with the complete table list for verification.

## Validation

All SQL files have been validated for:
- ✅ Balanced parentheses
- ✅ Proper semicolon termination
- ✅ Consistent ENGINE, CHARSET, and COLLATE declarations
- ✅ Foreign key constraints properly defined
- ✅ Index definitions for performance

**Total Tables:** 26 across all databases
- User DB: 4 tables
- Content DB: 21 tables  
- Invoice DB: 1 table

Last updated: 2026-02-08
