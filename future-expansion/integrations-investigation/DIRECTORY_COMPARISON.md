# Directory Comparison Report
**Date**: December 6, 2025

## Directories Compared

**Directory A (Current):** `/home/kylewee/code/idk/projects/mechanicstaugustine.com`
- **Total Size:** 419 MB
- **Last Update:** Dec 6, 2025
- **Git Status:** Up to date with origin/main
- **Latest Commit:** e4845757 (Dec 1, 2025) - SignalWire migration

**Directory B (Outdated):** `/home/kylewee/code/idk/mechanicsaintaugustine.com`
- **Total Size:** 592 MB
- **Last Update:** Nov 11, 2025
- **Git Status:** Behind origin/main by 3 commits
- **Latest Commit:** 595e277f (Oct 10, 2025) - Sanitize secrets

---

## What Directory A Has (That B Doesn't)

### Documentation (All Missing in B)
- `.claude/` - Claude AI integration
- `CONFIGURATION_MASTER_REFERENCE.md`
- `DEPLOYMENT.md`
- `MASTER_CONFIG_DOCUMENT.md`
- `SERVICE_STATUS.md`
- `PROJECT_INVENTORY.md`
- `SYSTEM_ARCHITECTURE.md`
- `TESTING_GUIDE.md`
- `.env.example`

### Code & Features
- `signalwire/` - SignalWire phone system integration
- `integrations-investigation/` - This investigation folder
- `health.php` - Health check endpoint
- `lib/` - Shared PHP libraries
- `test_call_simulation.php` - Testing utilities
- `Caddyfile.dev` - Dev server config

### Recent Improvements (3 commits ahead)
1. `e4845757` - Migrate phone system from Twilio to SignalWire
2. `ee9d16ae` - Improve AI call transcription extraction
3. `84c2abd0` - Codebase refactoring

---

## What Directory B Has (That A Doesn't)

### 1. new-project/ (173 MB)
**Type**: Python project template (untracked)
**Contains**: Placeholder template with `{{project_name}}`, `{{author_name}}`
**Structure**:
```
new-project/
├── src/{{project_name}}/
├── tests/
├── docs/
├── requirements/
└── setup.py
```
**Analysis**: This is a generic Python template, NOT mechanic-specific
**Recommendation**: Move to `/code/idk/templates/` if useful, otherwise delete

### 2. crm/rukovoditel_3.6.2/ (130 MB)
**Type**: Duplicate CRM installation
**Analysis**: Redundant copy of Rukovoditel CRM
**Recommendation**: DELETE - already have main CRM in `crm/`

### 3. Backup Files
- `api/.env.local.php.bak`
- `api/.env.local.php.bak.1757942452`
**Analysis**: Old environment backups (pre-sanitization)
**Recommendation**: DELETE - already cleaned up in A

---

## Size Breakdown

| Directory | Total | CRM Size | Why Larger? |
|-----------|-------|----------|-------------|
| A (Current) | 419 MB | 171 MB | Lean, cleaned up |
| B (Outdated) | 592 MB | 344 MB | Duplicate CRM (130 MB) + Python template (173 MB) |

**Difference**: B is 173 MB larger due to bloat

---

## Git Status Comparison

Both directories point to same remote:
`https://github.com/kylewee/mechanicsaintaugustine.com.git`

**Directory A**: ✅ Up to date with origin/main
**Directory B**: ❌ Behind by 3 commits (2 months outdated)

---

## ⚠️ RECOMMENDATION: DELETE Directory B

### Reasons:
1. **Obsolete** - 2 months behind, missing critical updates
2. **Duplicate** - Same git remote, no backup value
3. **Bloated** - 173 MB larger with redundant files
4. **Missing features** - No SignalWire, no new docs, no improvements
5. **Only unique content** - Generic Python template (not mechanic-related)

### Action Plan:
- [ ] Extract `new-project/` template to `/code/idk/templates/` (if wanted)
- [ ] Delete entire `/home/kylewee/code/idk/mechanicsaintaugustine.com/` directory
- [ ] Keep only Directory A as single source of truth

### Command to Delete:
```bash
rm -rf /home/kylewee/code/idk/mechanicsaintaugustine.com
```

---

## Verdict: 🗑️ FLOP - Delete Directory B

No unique mechanic-related work. Just old code + bloat.
