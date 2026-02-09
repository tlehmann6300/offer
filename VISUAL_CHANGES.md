# Visual Changes Documentation

## Sidebar Navigation Changes

### Before
```
├── Dashboard
├── Mitglieder (conditional)
├── Alumni
├── Projekte
├── Events
│   └── Helfersystem (indented)
├── Inventar
├── Blog
├── Rechnungen (conditional)
└── Verwaltung (Board only - dropdown)
    ├── Benutzer
    ├── Einstellungen
    └── Statistiken
```

### After
```
├── Dashboard
├── Mitglieder (conditional - Board, Head, Member, Candidate)
├── Alumni
├── Projekte
├── Events
│   └── Helfersystem (indented)
├── Inventar
├── Blog
├── Rechnungen (conditional - Board roles, Alumni, Alumni-Board, Honorary)
├── Benutzer (Board roles only)
├── Einstellungen (Board roles only)
└── Statistiken (Board roles only)
```

## Sidebar Footer Changes

### Before
```
┌─────────────────────────────┐
│  [AB]  Guten Tag, Anna B.   │
│        anna@example.com      │
│        [Vorstand Badge]      │
└─────────────────────────────┘
```

### After
```
┌─────────────────────────────┐
│  [AB]  Anna Becker           │
│        anna@example.com      │
│        [Vorstand Badge]      │
└─────────────────────────────┘
```

## New Role Types Display

### User Management Dropdown
The role dropdown now includes:
- Anwärter (Candidate)
- Mitglied (Member)
- Ressortleiter (Head)
- Alumni
- Alumni-Vorstand (Alumni Board)
- Vorstand (Board) ← Legacy
- **Vorstand Intern** ← NEW
- **Vorstand Extern** ← NEW
- **Vorstand Finanzen & Recht** ← NEW
- Ehrenmitglied (Honorary Member)

### Role Badge Translations
New German translations in sidebar footer:
- `vorstand_intern` → "Vorstand Intern"
- `vorstand_extern` → "Vorstand Extern"
- `vorstand_finanzen_recht` → "Vorstand Finanzen & Recht"
- `honorary_member` → "Ehrenmitglied"

## Design Improvements

### Sidebar
- **Gradient Background**: Enhanced blue gradient (from #0066b3 to #004f8c)
- **Hover Effects**: 
  - Background opacity changes to 20%
  - 4px transform on hover (slides items to the right)
  - Smooth 0.2s transition
- **Active State**: 
  - 25% opacity background
  - 3px green border on the right (#00a651)
- **Shadow**: Added subtle shadow (4px 0 20px rgba(0, 102, 179, 0.15))

### Cards
- **Border Radius**: Increased from 12px to 16px
- **Default Shadow**: Softer shadow (0 2px 8px rgba(0, 0, 0, 0.08))
- **Hover Shadow**: Blue-tinted shadow (0 12px 32px rgba(0, 102, 179, 0.15))
- **Hover Transform**: -2px vertical lift
- **Border**: Added 1px subtle border (rgba(0, 0, 0, 0.05))
- **Transition**: Cubic-bezier easing for smooth animation

### Buttons (btn-primary)
- **Gradient**: Green to blue (from #00a651 to #0066b3)
- **Padding**: Increased from 0.5rem to 0.625rem vertical
- **Border Radius**: Increased from 8px to 10px
- **Font Weight**: Added 600 (semi-bold)
- **Shadow**: Green-tinted shadow (0 4px 12px rgba(0, 166, 81, 0.25))
- **Hover Shadow**: Enhanced shadow (0 8px 20px rgba(0, 166, 81, 0.35))
- **Hover Transform**: -3px vertical lift

## Permission Changes

### Invoice Management
| Role | View Invoices | Mark as Paid |
|------|---------------|--------------|
| candidate | ❌ | ❌ |
| member | ❌ | ❌ |
| head | ❌ | ❌ |
| alumni | ✅ | ❌ |
| alumni_board | ✅ | ❌ |
| honorary_member | ✅ | ❌ |
| board (legacy) | ✅ | ❌ |
| vorstand_intern | ✅ | ❌ |
| vorstand_extern | ✅ | ❌ |
| **vorstand_finanzen_recht** | ✅ | **✅** |

### Admin Pages Access
| Role | Benutzer | Einstellungen | Statistiken |
|------|----------|---------------|-------------|
| candidate | ❌ | ❌ | ❌ |
| member | ❌ | ❌ | ❌ |
| head | ❌ | ❌ | ❌ |
| alumni | ❌ | ❌ | ❌ |
| alumni_board | ❌ | ❌ | ❌ |
| honorary_member | ❌ | ❌ | ❌ |
| board (legacy) | ✅ | ✅ | ✅ |
| vorstand_intern | ✅ | ✅ | ✅ |
| vorstand_extern | ✅ | ✅ | ✅ |
| vorstand_finanzen_recht | ✅ | ✅ | ✅ |

## CSS Color Scheme

### IBC Brand Colors (from theme.css)
- Primary Green: `#00a651`
- Primary Blue: `#0066b3`
- Dark Blue: `#004f8c`

### New Gradient Applications
1. **Sidebar**: `linear-gradient(135deg, #0066b3 0%, #004f8c 100%)`
2. **Buttons**: `linear-gradient(135deg, #00a651 0%, #0066b3 100%)`
3. **User Avatar**: `linear-gradient(to-br, #3b82f6, #4f46e5)` (blue to indigo)

## Responsive Design
All changes maintain mobile responsiveness:
- Mobile toggle button works correctly
- Sidebar slides in/out on mobile devices
- All hover effects work on touch devices
- No horizontal scrolling introduced
- Text truncation works properly in sidebar footer

## Accessibility
- All interactive elements have proper focus states
- Color contrast ratios meet WCAG standards
- Hover effects use transform (better performance)
- Smooth transitions (not too fast, not too slow)
- Icons paired with text labels
