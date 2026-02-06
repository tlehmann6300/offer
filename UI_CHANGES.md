# UI Changes Overview

## Summary of Visual Changes

This document describes the user interface changes made to implement project type classification and notification preferences.

## 1. Project Management Page (manage.php)

### Location: `/pages/projects/manage.php`

### Changes:
1. **Project Type Dropdown** (Edit/Create Form)
   - **Location:** Form section, alongside Priority dropdown
   - **Label:** "Projekt-Typ"
   - **Options:**
     - "Intern" (Internal)
     - "Extern" (External)
   - **Default:** Internal
   - **Layout:** Two-column grid with Priority

2. **Type Badge** (Project List Cards)
   - **Location:** Below status/priority badges on each project card
   - **Colors:**
     - Internal: Blue background (#dbeafe), Blue text (#1e40af)
     - External: Green background (#dcfce7), Green text (#166534)
   - **Icon:** Tag icon (fa-tag)
   - **Format:** "🏷️ Intern" or "🏷️ Extern"

### Visual Layout:
```
┌─────────────────────────────────────────┐
│ [Project Card]                          │
│                                         │
│ [Status Badge]  [Priority Badge]        │
│                                         │
│ 🏷️ [Type Badge]                         │
│                                         │
│ Project Title                           │
│ Description...                          │
│ ...                                     │
└─────────────────────────────────────────┘
```

## 2. Projects List Page (index.php)

### Location: `/pages/projects/index.php`

### Changes:
1. **Filter Bar**
   - **Location:** Below page header, above project grid
   - **Layout:** Horizontal button group
   - **Buttons:**
     - "📋 Alle" (All) - Purple when active, gray when inactive
     - "🏢 Intern" (Internal) - Blue when active, light blue when inactive
     - "👥 Extern" (External) - Green when active, light green when inactive
   - **Functionality:** Clicking filters the project list

2. **Type Badges on Project Cards**
   - Same styling as manage.php
   - Displayed below status/priority badges
   - Visible on all project cards

### Visual Layout:
```
┌─────────────────────────────────────────────────┐
│ Projekte                                        │
│ Entdecken Sie aktuelle Projekte...             │
│                                                 │
│ ┌─────────────────────────────────────────┐   │
│ │ Filter: [Alle] [Intern] [Extern]        │   │
│ └─────────────────────────────────────────┘   │
│                                                 │
│ ┌────────┐  ┌────────┐  ┌────────┐           │
│ │Project │  │Project │  │Project │           │
│ │Card 1  │  │Card 2  │  │Card 3  │           │
│ │🏷️ Intern│  │🏷️ Extern│  │🏷️ Intern│           │
│ └────────┘  └────────┘  └────────┘           │
└─────────────────────────────────────────────────┘
```

## 3. Project Detail Page (view.php)

### Location: `/pages/projects/view.php`

### Changes:
1. **Type Badge**
   - **Location:** Alongside status and priority badges at top of page
   - **Same styling** as other pages
   - **Format:** Icon + text label

### Visual Layout:
```
┌─────────────────────────────────────────────────┐
│ [Status Badge] [Priority Badge] 🏷️ [Type Badge] │
│                                                 │
│ Project Title                                   │
│ ─────────────────                              │
│                                                 │
│ Project Details...                             │
└─────────────────────────────────────────────────┘
```

## 4. User Profile Page (profile.php)

### Location: `/pages/auth/profile.php`

### Changes:
1. **Notifications Section**
   - **Location:** Below 2FA settings (full-width card)
   - **Title:** "🔔 Benachrichtigungen"
   - **Description:** "Wählen Sie aus, über welche Ereignisse Sie per E-Mail benachrichtigt werden möchten"

2. **Notification Checkboxes**
   - **Layout:** Vertical stack of styled checkbox cards
   - **Styling:** Gray background, rounded corners, border
   - **Checkbox 1:**
     - Label: "Neue Projekte"
     - Description: "Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Projekt veröffentlicht wird"
     - Default: **Checked** ✓
   - **Checkbox 2:**
     - Label: "Neue Events"
     - Description: "Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Event erstellt wird"
     - Default: **Unchecked** ☐

3. **Save Button**
   - **Text:** "💾 Benachrichtigungseinstellungen speichern"
   - **Style:** Full-width purple button
   - **Location:** Below checkboxes

### Visual Layout:
```
┌─────────────────────────────────────────────────┐
│ 🔔 Benachrichtigungen                           │
│ ─────────────────────────────────────────────── │
│ Wählen Sie aus, über welche Ereignisse...      │
│                                                 │
│ ┌─────────────────────────────────────────┐   │
│ │ ☑️ Neue Projekte                         │   │
│ │    Erhalten Sie eine E-Mail...           │   │
│ └─────────────────────────────────────────┘   │
│                                                 │
│ ┌─────────────────────────────────────────┐   │
│ │ ☐ Neue Events                            │   │
│ │    Erhalten Sie eine E-Mail...           │   │
│ └─────────────────────────────────────────┘   │
│                                                 │
│ [💾 Benachrichtigungseinstellungen speichern]  │
└─────────────────────────────────────────────────┘
```

## Color Scheme

### Project Type Badges
- **Internal (Intern):**
  - Background: `#dbeafe` (bg-blue-100)
  - Text: `#1e40af` (text-blue-800)
  - Icon: fa-building (when in filter)
  - Icon: fa-tag (when on cards)

- **External (Extern):**
  - Background: `#dcfce7` (bg-green-100)
  - Text: `#166534` (text-green-800)
  - Icon: fa-users (when in filter)
  - Icon: fa-tag (when on cards)

### Filter Buttons (Active State)
- **All:** Purple (`bg-purple-600`, `text-white`)
- **Internal:** Blue (`bg-blue-600`, `text-white`)
- **External:** Green (`bg-green-600`, `text-white`)

### Filter Buttons (Inactive State)
- **All:** Gray (`bg-gray-200`, `text-gray-700`)
- **Internal:** Light Blue (`bg-blue-100`, `text-blue-700`)
- **External:** Light Green (`bg-green-100`, `text-green-700`)

## Accessibility Features

1. **Color Contrast:**
   - All text meets WCAG AA standards
   - Badges use high-contrast colors
   - Active/inactive states clearly distinguishable

2. **Icons:**
   - All badges include icons for visual identification
   - Icons complement text labels (not replacing them)
   - Font Awesome icons used consistently

3. **Form Labels:**
   - Clear, descriptive labels for all inputs
   - Helper text provided for checkboxes
   - Proper HTML label associations

4. **Responsive Design:**
   - Filter bar wraps on mobile devices
   - Cards stack vertically on smaller screens
   - Checkboxes remain readable on all devices

## User Experience Flow

### Creating a Project with Type:
1. Navigate to Projects > Manage
2. Click "Neues Projekt"
3. Fill in standard fields
4. Select "Projekt-Typ" from dropdown (Intern/Extern)
5. Save as draft or publish
6. Type badge appears on project card

### Filtering Projects by Type:
1. Navigate to Projects
2. See filter bar with three options
3. Click desired filter (Alle/Intern/Extern)
4. Page reloads with filtered results
5. Active filter highlighted in primary color

### Managing Notification Preferences:
1. Navigate to Profile
2. Scroll to "Benachrichtigungen" section
3. Check/uncheck desired notifications
4. Click save button
5. See success message
6. Preferences applied immediately

## Browser Compatibility

All UI elements tested and compatible with:
- Chrome/Edge (Chromium)
- Firefox
- Safari
- Mobile browsers (iOS Safari, Chrome Mobile)

Requires:
- Font Awesome 5+ for icons
- Tailwind CSS for styling
- Modern browser with CSS Grid support
