# Event View Pages - UI Documentation

## Pages Overview

This document describes the user interface of the event view pages for members and alumni.

## 1. Event List Page (pages/events/index.php)

### URL
`/pages/events/index.php` (accessible via "Events" link in sidebar navigation)

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│ [Sidebar Navigation]                                        │
│                                                              │
│  📅 Events                                                  │
│                                                              │
│  Entdecken Sie kommende Events und melden Sie sich an      │
│                                                              │
│  ┌──────────────┐ ┌──────────────────┐                    │
│  │  📅 Aktuell  │ │ ✓ Meine Anmeld. │                    │
│  └──────────────┘ └──────────────────┘                    │
│                                                              │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐         │
│  │ 🎉 Event 1  │ │ 📚 Event 2  │ │ ⚽ Event 3  │         │
│  │             │ │             │ │             │         │
│  │ ⏰ Noch 3   │ │ ✅ Angemeldet│ │ 📍 External │         │
│  │    Tage, 4  │ │             │ │             │         │
│  │    Std      │ │ 📅 15.03.26 │ │ 🤝 Helfer   │         │
│  │             │ │    18:00    │ │   benötigt  │         │
│  │ 📅 15.03.26 │ │             │ │             │         │
│  │    18:00    │ │ 📍 Aula     │ │ 📅 20.03.26 │         │
│  │             │ │             │ │    14:00    │         │
│  │ 📍 Festsaal │ │ Lorem ipsum │ │             │         │
│  │             │ │ dolor sit... │ │ 📍 Sportplatz│        │
│  │ Lorem ipsum │ │             │ │             │         │
│  │ dolor sit...│ │ [Details    │ │ Lorem ipsum │         │
│  │             │ │  ansehen]   │ │ dolor sit...│         │
│  │ [Details    │ └─────────────┘ │             │         │
│  │  ansehen]   │                 │ [Details    │         │
│  └─────────────┘                 │  ansehen]   │         │
│                                   └─────────────┘         │
└─────────────────────────────────────────────────────────────┘
```

### Features

**Filter Tabs:**
- "Aktuell" (purple gradient when active) - Shows current and upcoming events
- "Meine Anmeldungen" (purple gradient when active) - Shows user's registrations

**Event Cards:**
- Title and icon
- Countdown badge (purple, "Noch X Tage, Y Std")
- Registration badge (green, "✅ Angemeldet") if registered
- Date and time
- Location
- External event indicator
- Helper needed indicator (non-alumni only)
- Description preview (first 150 chars)
- "Details ansehen" button (purple gradient)

**States:**
- Upcoming event with countdown
- Registered event with badge
- External event with indicator
- Event needing helpers (visible to non-alumni)

## 2. Event Detail Page (pages/events/view.php)

### URL
`/pages/events/view.php?id=123`

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│ [← Zurück zur Übersicht]                                    │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ 🎉 IBC Sommerfest 2026                  ✅ Angemeldet  │ │
│ │                                                          │ │
│ │ 📅 Beginn           ⏰ Ende                             │ │
│ │ 15.03.2026 14:00    15.03.2026 22:00                   │ │
│ │                                                          │ │
│ │ 📍 Ort              👤 Ansprechpartner                  │ │
│ │ Festsaal            Max Mustermann                      │ │
│ │                                                          │ │
│ │ Beschreibung                                            │ │
│ │ ───────────                                             │ │
│ │ Unser jährliches Sommerfest mit vielen Aktivitäten,    │ │
│ │ Essen, Getränken und Unterhaltung für alle Mitglieder. │ │
│ │                                                          │ │
│ │ [🚫 Abmelden]                                           │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ 🤝 Helfer-Bereich                                       │ │
│ │                                                          │ │
│ │ Unterstützen Sie uns als Helfer! Wählen Sie einen      │ │
│ │ freien Slot aus.                                        │ │
│ │                                                          │ │
│ │ 🏗️ Aufbau                                               │ │
│ │ Helfen Sie beim Aufbau von Zelten und Tischen          │ │
│ │                                                          │ │
│ │ ┌──────────────────────────────────────────────────┐   │ │
│ │ │ ⏰ 12:00 - 14:00 Uhr        [✅ Eingetragen]     │   │ │
│ │ │ 2/4 belegt                   [❌ Austragen]      │   │ │
│ │ └──────────────────────────────────────────────────┘   │ │
│ │                                                          │ │
│ │ ┌──────────────────────────────────────────────────┐   │ │
│ │ │ ⏰ 14:00 - 16:00 Uhr        [➕ Eintragen]       │   │ │
│ │ │ 1/4 belegt                                        │   │ │
│ │ └──────────────────────────────────────────────────┘   │ │
│ │                                                          │ │
│ │ 🧹 Abbau                                                │ │
│ │ Helfen Sie beim Aufräumen nach dem Event               │ │
│ │                                                          │ │
│ │ ┌──────────────────────────────────────────────────┐   │ │
│ │ │ ⏰ 22:00 - 23:30 Uhr        [📋 Warteliste]      │   │ │
│ │ │ 5/5 belegt                                        │   │ │
│ │ └──────────────────────────────────────────────────┘   │ │
│ └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Features

**Event Header:**
- Title
- Registration status badge (green, "✅ Angemeldet")
- Event metadata in grid:
  - Start date/time
  - End date/time
  - Location
  - Contact person
- Description section

**Participation Buttons:**
- Internal event: "➕ Teilnehmen" (purple gradient)
- External event: "🔗 Zur Anmeldung (extern)" (blue gradient, opens in new tab)
- If registered: "🚫 Abmelden" (red gradient)

**Helper Area (Non-Alumni Only):**
- Section header with icon
- Grouped by helper type
- Each helper type has:
  - Title
  - Description
  - List of time slots

**Time Slots:**
- Time display (HH:MM - HH:MM Uhr)
- Occupancy counter (X/Y belegt)
- Status-dependent buttons:
  - "✅ Eingetragen" (green) + "❌ Austragen" (red) if registered
  - "➕ Eintragen" (orange gradient) if available
  - "📋 Warteliste" (yellow gradient) if full
  - "Belegt" (gray) if neither applies

**Notifications:**
- Toast notifications appear in top-right corner
- Success: green background, check icon
- Error: red background, exclamation icon
- Auto-dismiss after 5 seconds

## 3. Mobile Responsive Design

### Mobile Layout (< 768px)

```
┌─────────────────┐
│ [☰ Menu]       │
│                 │
│ 📅 Events      │
│                 │
│ Entdecken...   │
│                 │
│ [📅 Aktuell]   │
│ [✓ Meine...]   │
│                 │
│ ┌─────────────┐│
│ │ 🎉 Event 1  ││
│ │             ││
│ │ ⏰ Noch 3   ││
│ │    Tage     ││
│ │             ││
│ │ 📅 15.03.26 ││
│ │    18:00    ││
│ │             ││
│ │ 📍 Festsaal ││
│ │             ││
│ │ [Details]   ││
│ └─────────────┘│
│                 │
│ ┌─────────────┐│
│ │ 📚 Event 2  ││
│ │             ││
│ │ ✅ Angemel. ││
│ │             ││
│ │ [Details]   ││
│ └─────────────┘│
└─────────────────┘
```

**Mobile Optimizations:**
- Sidebar collapses to hamburger menu
- Cards stack vertically (1 column)
- Filter tabs wrap if needed
- Buttons become full-width
- Touch-friendly spacing
- Font size prevents iOS zoom

## 4. Color Scheme

### Primary Colors
- **Purple Gradient:** `#667eea` to `#764ba2`
  - Used for: Primary buttons, active filters, countdown badges

### Status Colors
- **Green:** Success, registered, available
  - Background: `#dcfce7` (green-100)
  - Text: `#166534` (green-800)

- **Red:** Cancel, error, remove
  - Background: `#fee2e2` (red-100)
  - Text: `#991b1b` (red-800)

- **Orange:** Helper actions
  - Gradient: `#ea580c` to `#c2410c`

- **Yellow:** Waitlist
  - Gradient: `#ca8a04` to `#a16207`

- **Blue:** External events
  - Color: `#2563eb` (blue-600)

- **Gray:** Disabled, neutral
  - Background: `#f3f4f6` (gray-100)
  - Text: `#4b5563` (gray-600)

### Typography
- **Headers:** Bold, gray-800
- **Body:** Regular, gray-600/700
- **Icons:** FontAwesome 6.4.0

## 5. User Interactions

### Event List Page

**Filter Switching:**
1. Click "Aktuell" or "Meine Anmeldungen" tab
2. Page reloads with new filter
3. Active tab has purple gradient background

**View Event Details:**
1. Click "Details ansehen" button on card
2. Navigate to event detail page

### Event Detail Page

**General Event Signup (Internal):**
1. Click "Teilnehmen" button
2. AJAX request sent to API
3. Toast notification appears
4. Page reloads showing registration status

**General Event Signup (External):**
1. Click "Zur Anmeldung (extern)" button
2. External link opens in new tab

**Helper Slot Signup:**
1. Click "Eintragen" or "Warteliste" button on slot
2. AJAX request with time conflict check
3. If conflict: Error toast with conflicting event name
4. If success: Success toast and page reload
5. Slot shows "Eingetragen" status

**Cancellation:**
1. Click "Abmelden" or "Austragen" button
2. Confirmation dialog appears
3. If confirmed: AJAX request sent
4. Success toast and page reload

**Double Booking Prevention:**
- Automatic check before helper slot signup
- If user has overlapping slot: Error message with details
- User cannot register for two slots at same time

## 6. Alumni vs. Non-Alumni View

### Alumni Users
- **Can see:** Event list, event details, general registration
- **Cannot see:** Helper section in event detail
- **Cannot do:** Register for helper slots

### Non-Alumni Users (Members)
- **Can see:** Everything including helper section
- **Can do:** All actions including helper slot registration

## 7. Accessibility Features

- **Keyboard Navigation:** All buttons and links are keyboard accessible
- **ARIA Labels:** Icons have semantic meaning via FontAwesome
- **Color Contrast:** All text meets WCAG AA standards
- **Touch Targets:** Minimum 44x44px for mobile
- **Focus Indicators:** Visible focus states on interactive elements
- **Screen Reader:** Semantic HTML structure

## 8. Performance Optimizations

- **Lazy Loading:** Images and icons loaded on demand
- **Minimal JavaScript:** Only essential AJAX calls
- **CDN Resources:** Tailwind CSS and FontAwesome from CDN
- **Efficient Queries:** Database queries optimized in Event model
- **Caching:** Event data can be cached at application level

## Summary

The event view pages provide a clean, modern, and user-friendly interface for members and alumni to:
- Browse events with useful filters
- View detailed event information
- Register for events (general and helper slots)
- Manage their registrations
- Stay informed with countdown displays
- Experience alumni-appropriate restrictions

The design is responsive, accessible, and follows modern web design principles while maintaining consistency with the existing IBC Intranet design system.
