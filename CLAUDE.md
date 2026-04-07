# CLAUDE.md

## Tech Stack
- PHP 7+ (plain, no framework)
- Vanilla JavaScript (ES6+, frontend)
- HTML5 + CSS3
- XAMPP (Apache + PHP local server)

## Project Structure
```
ic3-manager/
├── CLAUDE.md              # Project documentation
├── index.php              # Class selection page
├── quanly.php             # Main dashboard (HTML template)
├── api.php                # Backend API (GET/POST endpoints)
├── export_excel.php       # Excel export
├── save_score.php         # Score receiver (iSpring callback)
├── nhapdiem.php           # Manual score entry (Excel-like)
├── includes/
│   └── helpers.php        # Shared PHP functions (parse, JSON I/O)
├── css/
│   └── quanly.css         # Dashboard styles
├── js/
│   └── quanly.js          # Dashboard logic
├── danhsach/              # Data storage
│   ├── danhsach_*.txt     # Student lists per class
│   ├── target_*.json      # Target scores per class
│   └── ot_config_*.json   # OT assignments per class
├── {class}/               # Score HTML files (e.g., 7.6/)
├── active_class.txt       # Currently selected class
└── classes.json           # Class count config
```

## How to Run
1. Start XAMPP → Enable Apache
2. Open `http://localhost/ic3-manager/`

## API Endpoints

### GET `api.php?action=get_data`
Returns JSON: `{ mapping, scores, targets, otConfigs }`

### GET `api.php?action=get_ip_mapping`
Returns JSON: `{ ipMapping, serverIP, totalPCs, lastModified }`

### POST `api.php`
| action | params | description |
|--------|--------|-------------|
| `save_list` | `data` | Save student list (supports Google Sheet paste) |
| `save_target` | `pc`, `target` | Set/clear target for one PC |
| `save_ot` | `pc`, `ot` | Set/clear OT assignment for one PC |
| `save_bulk_target` | `start`, `end`, `target` | Bulk set/clear targets |
| `save_bulk_ot` | `start`, `end`, `ot` | Bulk set/clear OT assignments |
| `delete_scores` | — | Delete all score files |
| `clear_ip_mapping` | — | Clear all IP mapping data |
| `generate_bat` | `server_ip` | Generate lay_ip.bat with server IP |

## Conventions
- Vietnamese UI labels and comments
- File-based storage (no database)
- PC range: 1–50
- Auto-refresh every 5 seconds on dashboard
- Score files: `PC{nn}_{OT}_Diem_{score}_{hh-mm-ss}_{timestamp}.html`

## Architecture Notes
- `quanly.php` is HTML-only template, links `css/quanly.css` + `js/quanly.js`
- PHP injects config via `<script>const CONFIG = {...}</script>` before JS load
- `api.php` handles all data read/write; parse logic is centralized here
- `export_excel.php` reuses same parse logic for consistency
