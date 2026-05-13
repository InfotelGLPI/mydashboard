# User Guide — GLPI MyDashboard Plugin

## 1. Overview

The **MyDashboard** plugin replaces or enhances the GLPI home page with a fully customisable dashboard. It provides:

- A **drag-and-drop grid** (GridStack.js) where each user places, resizes, and rearranges widgets
- More than **100 widgets** covering tickets, statistics, KPIs, inventory, planning, reminders, RSS feeds, knowledge base, contracts, and projects
- **Per-user personalisation**: each user saves their own grid layout; administrators define default grids at profile level
- **Visual indicators (KPIs)** as coloured cards with configurable alert thresholds
- **Apache ECharts** charts (bar, line, pie, donut, funnel) with configurable colour themes
- **DataTables** interactive tables with CSV, Excel and PDF export
- An **OpenStreetMap** geographic widget displaying open tickets by GPS location
- **Client-side PDF export** of the entire dashboard
- Integration with the **Service Catalog** plugin
- A **"replace central"** mode that automatically redirects the user to the dashboard on login

---

## 2. Rights Management

Path: `Administration > Profiles > My Dashboard tab`

### 2.1 Available Rights

| Right | Value | Description |
|-------|-------|-------------|
| `plugin_mydashboard` | **1 = Custom** | Dashboard access; only widgets authorised by the profile whitelist are visible |
| `plugin_mydashboard` | **6 = Full** | Full access: all widgets visible (subject to individual GLPI rights) |
| `plugin_mydashboard_config` | CREATE+UPDATE+PURGE | Access to the global plugin configuration page |
| `plugin_mydashboard_edit` | CREATE+UPDATE | Permission to enter edit mode and rearrange widgets |
| `plugin_mydashboard_stockwidget` | READ+CREATE+UPDATE+PURGE | Manage inventory Stock KPI widgets |

> **Note:** With `plugin_mydashboard = 1 (Custom)`, the administrator configures a widget whitelist for that profile (see section 10).

---

## 3. Global Configuration

Path: `Configuration > Plugins > My Dashboard` (requires `plugin_mydashboard_config` right)

### 3.1 Main Options

| Setting | Description |
|---------|-------------|
| Enable fullscreen | Show fullscreen button in the dashboard toolbar |
| Display in menu | Show MyDashboard link in GLPI top menu |
| Replace central page | Globally enable redirect to the dashboard on login |
| Category level | Depth of the ITIL category tree used in charts (default: 2) |
| Impact colours 1 to 5 | Colours for the 5 impact levels in alert KPI widgets |
| Network alerts title | Custom title for the network alerts widget |
| Maintenances title | Custom title for the scheduled maintenances widget |
| Informations title | Custom title for the informations widget |

### 3.2 Configuration Tabs

| Tab | Content |
|-----|---------|
| **Main** | Options listed above |
| **Translations** | Translate configuration labels into other languages |
| **Schema check** | Database table integrity check for the plugin |

---

## 4. User Preferences

Path: `Preferences > My Dashboard tab`

| Setting | Description |
|---------|-------------|
| Automatic refresh | Enable automatic widget refresh |
| Refresh delay | Interval: 1, 2, 5, 10, 30, or 60 minutes |
| Replace central page | Personal override: redirect to dashboard on login |
| Default widget width | Default number of grid columns per widget |
| Preferred technician group(s) | Groups pre-selected in chart widget filters |
| Preferred requester group(s) | Pre-selected requester groups |
| Preferred entity | Pre-selected entity filter |
| Start in edit mode | Begin with edit mode active |
| Enable drag in view mode | Allow drag and drop in view mode |
| Colour palette | ECharts theme for charts |
| Preferred ticket type | Incident / Request / All (pre-selected filter) |
| Preferred category | Pre-selected ITIL category |
| Preferred year | Current or previous year |

> In preferences, users can also **hide widget groups** from third-party plugins integrated into MyDashboard.

> **Link with the global filter bar:** the *Preferred entity*, *Preferred technician group(s)*, *Preferred ticket type*, and *Preferred year* preferences are used to **pre-populate the global filter bar** on every dashboard load (see section 5.2). Changing a filter in the bar does not modify the saved preferences: preferences are only the initial values.

---

## 5. Dashboard Interface

### 5.1 Toolbar

The toolbar (at the top of the dashboard) contains:

| Button | Action |
|--------|--------|
| Edit mode | Toggle drag-and-drop and resize |
| Add widget | Opens the widget catalogue |
| Refresh | Refresh all widgets |
| Predefined layout | Choose from 11 predefined layouts |
| Export to PDF | Export the entire dashboard as PDF |
| Fullscreen | Display fullscreen (if enabled in config) |

### 5.2 Global Filter Bar

Directly below the toolbar is the **global filter bar**. It lets you apply criteria to **all displayed chart widgets simultaneously**, without configuring each widget individually.

#### Available Filters

| Filter | Description |
|--------|-------------|
| **Entity** | Restrict all charts to a specific GLPI entity. Value `0` is the root entity and is a valid filter. |
| **Technician group** | One or more assigned technician groups (multi-select) |
| **Type** | Incident, Request, or All |
| **Year** | Reference year for the data |

#### How It Works

- Selectors are **pre-populated** from user preferences (see section 4) on every dashboard load.
- Whenever a filter is changed, **all visible widgets are automatically refreshed** with the new values.
- Global filters act as the **default value**: if a widget has its own criteria form (⚙ icon in its header) and the user has set values there, **the widget's local criteria take priority** over the global filters.
- Each widget's criteria form is **updated** when the widget refreshes: the internal selectors reflect the current values (global or local).

> **Example:** If the global filter is set to entity "Paris Site" and year 2025, all charts display the matching data. If the user then opens the criteria form of the "Top 10 categories" widget and selects "Lyon Site", that widget shows "Lyon Site" while all others keep "Paris Site".

### 5.3 Edit Mode

In edit mode (requires `plugin_mydashboard_edit` right):

- **Drag and drop** widgets to reposition them
- **Resize** widgets by dragging corners
- **Delete** a widget (cross icon on the widget)
- **Configure** widget filter criteria (gear icon)
- The layout is **saved automatically** to the database

### 5.4 Predefined Layouts

11 predefined layouts are available as starting points:

| Layout | Content |
|--------|---------|
| GLPI Admin | Complete administrator view |
| Inventory Admin | Inventory widgets |
| Helpdesk Supervisor | Supervisor view with global KPIs |
| Incident Supervisor | Incident tracking |
| Request Supervisor | Request tracking |
| Helpdesk Technician | Technician view with queues |
| All pie charts | All pie/donut charts |
| All bar charts | All bar charts |
| All line charts | All line charts |
| All tables | All table widgets |
| All indicators | All KPI widgets |

---

## 6. Widget Types

| Type | Rendering | Description |
|------|-----------|-------------|
| **KPI / Indicator** | Coloured card | Counter with configurable alert threshold and colour |
| **Table** | DataTables.js | Interactive table with sort, search and export |
| **Pie** | ECharts | Pie, donut or polar area chart |
| **Bar** | ECharts | Horizontal or vertical bar chart |
| **Line** | ECharts | Line chart |
| **Map** | OpenStreetMap | Geographic map with markers |
| **Planning** | GLPI view | Embedded GLPI planning |
| **Custom HTML** | HTML render | Custom HTML content |

### Common ECharts Chart Features

Each chart has a built-in toolbar:
- **Data view**: displays raw data as a text table
- **Change type**: switch between bar and line
- **Restore**: reset zoom
- **Save as image**: download the chart as PNG

---

## 7. Widget Catalogue — Overview

### 7.1 Alert / System Widgets

| Widget | Category | Type | Content |
|--------|----------|------|---------|
| Network alerts | System | KPI | Active network alerts (from Reminders type 0), colour-coded by impact level |
| Scheduled maintenances | System | KPI | Maintenance alerts (Reminders type 1) |
| Informations | System | KPI | Information notices (Reminders type 2) |
| Incident alerts | Helpdesk | KPI | Open critical/high incidents with coloured threshold |
| SLA incident alerts | Helpdesk | KPI | Incidents breaching or near-breaching SLA |
| Request alerts | Helpdesk | KPI | Open critical/high requests |
| SLA request alerts | Helpdesk | KPI | Requests breaching or near-breaching SLA |
| GLPI status | System | KPI | GLPI built-in health check results |
| User ticket alerts | Helpdesk | Table | Tickets per user exceeding a configurable threshold |
| Automatic actions in error | System | Table | GLPI cron tasks currently in error state |
| Not imported mails | System | Table | Mailbox entries that failed import |
| Inventory stock alerts | Inventory | KPI | Asset stock alerts (warranty, expiry) |
| Your equipment | Inventory | Table | Assets assigned to the current user |
| Global indicators | Helpdesk | KPI | Open/pending/closed counts |
| Global indicators by week | Helpdesk | KPI | Same counts for the current week |

### 7.2 Bar Chart Widgets (Helpdesk)

| Widget | Content |
|--------|---------|
| Ticket backlog by month | Bar + line mixed; click-through to ticket list |
| Average processing time by technician | Horizontal bar |
| Top 10 categories | Horizontal bar with click-through |
| Tickets by technician | Horizontal bar with category filter |
| Average ticket duration | Dual bar/line |
| Top 10 technicians | Horizontal bar |
| Age of open tickets | Distribution bar |
| Tickets by priority | Bar |
| Tickets by status | Bar |
| Satisfaction per quarter | Line + bar |
| Responsiveness last 12 months | Multi-series bar |
| Request sources evolution | Multi-series bar (12 months) |
| Solution types evolution | Multi-series bar |
| Resolution delay / TTO | Dual Y-axis |
| Satisfaction by year | Bar by month |
| Last computer synchronisation by month | Inventory; computers by sync date |
| TTO compliance evolution | Line + bar |
| TTR compliance evolution | Line + bar |

### 7.3 Pie Chart Widgets (Helpdesk)

| Widget | Sub-type |
|--------|----------|
| Tickets by priority | Pie |
| Top 10 requesters | Pie |
| TTR compliance | Donut |
| TTO compliance | Donut |
| Incidents by category | Pie |
| Requests by category | Pie |
| Opened / closed / unplanned | Pie |
| Solution types | Donut |
| Requester groups | Pie |
| Satisfaction level | Pie |
| Tickets by location | Pie |
| Request sources | Donut |
| By location (polar area) | Polar area |
| By appliance | Pie |

### 7.4 Line Chart Widgets (Helpdesk)

| Widget | Data |
|--------|------|
| Ticket stock by month | Pre-aggregated historical data |
| Opened vs. closed | Live query, 12-month rolling |
| Opened / resolved / closed | 3-series over 12 months |
| Opened / closed / unplanned | 3-series |
| Tickets created each month | By entity/group filter |
| Tickets created each week | Weekly granularity |
| Validation refusals | Monthly count |
| Tickets linked to problems | Monthly count |
| Backlog by week | Weekly stock |
| Monthly in-progress count | Live query |
| Tickets with more than one solution | Monthly count |

### 7.5 Table Widgets

| Widget | Category | Content |
|--------|----------|---------|
| Open tickets by technician and status | Helpdesk | Cross-tab: rows=technicians, columns=statuses |
| Open tickets by group and status | Helpdesk | Cross-tab: rows=groups, columns=statuses |
| Field uniqueness / duplicates | Inventory | Detects duplicate asset field values |
| Unpublished KB articles | Tools | Articles with `is_faq=0` not yet published |
| Internal user directory | Users | Searchable list of active internal users |

### 7.6 Map Widget

| Widget | Category | Content |
|--------|----------|---------|
| Open tickets by location | Helpdesk | OpenStreetMap; markers from GLPI location GPS coordinates; count of open tickets per location |

### 7.7 Funnel Widget

| Widget | Category | Content |
|--------|----------|---------|
| Computer age pyramid | Inventory | Computer age distribution by purchase date (ECharts funnel) |

### 7.8 Ticket Queue Widgets

| Widget | Category | Content |
|--------|----------|---------|
| In-progress tickets | Requester view | Current user's in-progress tickets |
| Observed tickets | Requester view | Tickets where user is observer |
| Rejected tickets | Requester view | Refused tickets |
| Tickets to close | Requester view | Tickets to validate/close |
| Satisfaction surveys | Requester view | Pending satisfaction surveys |
| Tickets to validate | Requester view | Tickets awaiting user's validation |
| New tickets | Technician view | Unassigned new tickets |
| Tickets to process | Technician view | Tickets assigned to current user |
| Pending tickets | Technician view | Technician's pending tickets |
| Tasks to do | Technician view | Current user's tasks |
| Group tickets to process | Group view | Tickets assigned to user's groups |
| Group tasks to do | Group view | Tasks for user's groups |
| Ticket counter | Helpdesk | Status counts with links to ticket lists |

### 7.9 Problem Widgets

| Widget | Category | Content |
|--------|----------|---------|
| Problems to process | Helpdesk | User's assigned problems |
| Waiting problems | Helpdesk | User's waiting problems |
| Problem counter | Helpdesk | Status counts |
| Group problems to process | Group view | Group's assigned problems |
| Group waiting problems | Group view | Group's waiting problems |

> Requires `problem` READALL or READMY right.

### 7.10 Change Widgets

| Widget | Category | Content |
|--------|----------|---------|
| Changes to process | Helpdesk | User's changes |
| Waiting changes | Helpdesk | User's waiting changes |
| Applied changes | Helpdesk | Changes resolved in the last 30 days |
| Change counter | Helpdesk | Status counts |
| Group changes to process | Group view | Group's changes |
| Group waiting changes | Group view | Group's waiting changes |

> Requires `change` READALL or READMY right.

### 7.11 Project and Task Widgets

| Widget | Category | Content |
|--------|----------|---------|
| Project tasks to process | Tools | User's project tasks (not finished) |
| Group project tasks | Group view | Project tasks for user's groups |
| Projects to process | Tools | User's projects |
| Group projects | Group view | Projects for user's groups |

### 7.12 Other Widgets

| Widget | Category | Content |
|--------|----------|---------|
| Planning | Tools | Embedded GLPI planning for the current user |
| Personal reminders | Tools | User's personal reminders |
| Public reminders | Tools | Public reminders visible to the user |
| Last events | System | Last N GLPI system events (requires `logs` READ right) |
| Popular KB articles | Tools | Most-read knowledge base articles |
| Recent KB articles | Tools | Most recently created KB articles |
| Last updated KB articles | Tools | Most recently modified KB articles |
| Contracts | Management | Contract status table: active, expiring soon, expired (requires `contract` READ right) |
| Personal RSS feeds | Tools | User's personal RSS feed items |
| Public RSS feeds | Tools | Public RSS feed items |

---

## 8. Stock Widgets (Inventory KPIs)

Path: `Tools > My Dashboard > Stock Widgets` (requires `plugin_mydashboard_stockwidget` right)

Stock widgets are fully configurable inventory KPIs.

### Stock Widget Fields

| Field | Description |
|-------|-------------|
| Name | Label displayed on the KPI card |
| Item type | GLPI asset type (Computer, Monitor, Network equipment, etc.) |
| States | Item states to count (JSON) |
| Types | Item types to filter (JSON) |
| Group | Filter by owner group |
| Entity | Entity scope |
| Recursive | Include sub-entities |
| Colour | KPI card colour |
| Alert threshold | Count below which alert colour is displayed |

Each saved Stock Widget automatically becomes a KPI widget in the dashboard.

---

## 9. Custom HTML Widgets

Path: `Configuration > Dropdowns > My Dashboard > Custom Widgets`

Custom widgets allow displaying free HTML content in the dashboard.

### Creating a Custom Widget

1. Go to `Configuration > Dropdowns > My Dashboard > Custom Widgets`
2. Create a new item (name, comment)
3. Open the **Content** tab of the created item
4. Enter the HTML content via the rich text editor
5. The widget appears automatically in the **Others** category of the catalogue

> Three default custom widgets are created at installation: "Incidents", "Requests", "Problems".

---

## 10. Profile Rights and Widget Whitelist

Path: `Administration > Profiles > [profile] > My Dashboard tab`

### "Custom" Access (right = 1)

When the `plugin_mydashboard` right is set to **1 (Custom)**, an additional panel appears on the profile form allowing the administrator to tick exactly which widgets are accessible for that profile.

Only ticked widgets will appear in that profile's widget catalogue.

### Default Technician Groups per Profile

On the same profile form, a section allows associating one or more default technician groups. These groups are automatically pre-selected in chart widget filters for users of this profile.

---

## 11. Dashboard Alerts

### 11.1 Linking an Alert to a GLPI Reminder

Alerts displayed in System widgets (Network alerts, Maintenances, Informations) are based on **GLPI Reminders** linked to a dashboard alert.

From a Reminder, Problem, or Change:
1. Open the item record
2. Go to the **Alert Dashboard** tab
3. Create an alert by filling in:
   - Type (0 = Network alert, 1 = Maintenance, 2 = Information)
   - Impact (1 to 5)
   - ITIL Category (optional)
   - Visibility dates (start/end)

### 11.2 Display on the Login Page

When active alerts exist, a **scrolling banner** (newsTicker) is automatically displayed at the bottom of the GLPI login page with the alert text.

---

## 12. Widget Filters and Criteria

### 12.1 Global Filter Bar

The **global filter bar** (section 5.2) filters all widgets in one action. It is initialised from user preferences and triggers an automatic refresh of all widgets on every change.

### 12.2 Per-Widget Criteria

Most chart widgets also have their own filter form, accessible via the ⚙ icon in the widget header. Available criteria vary by widget:

| Criterion | Description |
|-----------|-------------|
| Entity | Filter by GLPI entity. The root entity (id=0) is a valid filter value. |
| Technician group | One or more assigned technician groups |
| Requester group | One or more requester groups |
| Technician | Specific assigned technician |
| Location | Single location |
| Multiple locations | Several locations |
| ITIL category | Ticket category |
| Type | Incident / Request / All |
| Year | Ticket creation year |
| Month | Month (combined with year) |
| Date mode | Switch between Year mode and date range |
| Limit | Maximum number of rows to return (0=All) |
| Computer type | For inventory widgets |

### 12.3 Filter Priority

Filters are applied in the following priority order (highest to lowest):

1. **Widget-local criteria** — set via the widget's own ⚙ form
2. **Global filters** — set in the global filter bar (section 5.2)
3. **User preferences** — saved values from preferences (section 4)

When a widget's criteria form is submitted, the active global filters are passed as the base, and local criteria overwrite any conflicting keys. A widget can therefore refine or override the global filter without affecting other widgets.

> When the global filter bar refreshes a widget, the internal criteria form (⚙ icon) is regenerated and reflects the current values.

---

## 13. Data Export

### 13.1 Dashboard PDF Export

Toolbar button: **Export to PDF**

- Captures all visible widgets as canvas images
- Generates a PDF with header (title + date)
- Automatic portrait/landscape detection based on grid dimensions
- Entirely client-side processing (no data sent to the server)

### 13.2 Export from Tables (DataTables)

Each table widget has export buttons:

| Button | Format |
|--------|--------|
| Copy | Clipboard |
| Excel | .xlsx |
| CSV | .csv |
| PDF | Client-side PDF |
| Print | Browser print |

### 13.3 Chart Export (ECharts)

The toolbar on each chart allows:
- **Data view**: raw data as a text table
- **Change type**: switch between bar and line
- **Save as image**: download as PNG

---

## 14. Replace Central Page

The "Replace central page" mode automatically redirects users to the MyDashboard dashboard instead of the GLPI home page on login.

### Activation

| Level | Path | Description |
|-------|------|-------------|
| Global | `Configuration > Plugins > My Dashboard` | Enables the feature for all users |
| Per user | `Preferences > My Dashboard` | Each user enables it for themselves |

> If the Service Catalog plugin is active, the MyDashboard link is removed from the helpdesk menu to avoid duplicating navigation.

---

## 15. Best Practices

- **Define layouts per profile**: configure a suitable starting grid (supervisor, technician, admin) so users don't begin with a blank page
- **Use default groups per profile**: pre-select technician groups in chart widget filters so each profile sees its own data
- **Enable automatic refresh** on operational dashboards (real-time supervision): set a 5 or 10-minute interval in preferences
- **Restrict rights to "Custom" (1)** for helpdesk profiles and configure the widget whitelist to show only relevant widgets (queues, KPIs)
- **Create Stock widgets** to track critical inventory status (PCs under warranty, expiring licences, etc.)
- **Configure impact colours** in the global configuration so alert widgets reflect the organisation's colour scheme
- **Feed login alerts** (Maintenances, Network alerts) via linked Reminders to inform users from the login page
- **Use custom widgets** to display contextual information (support contact, external portal link, emergency procedures)
