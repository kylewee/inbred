# Dependency Audit Report

**Generated:** 2025-12-31
**Scope:** Full codebase analysis for outdated packages, security vulnerabilities, and bloat

---

## Executive Summary

| Category | Status | Priority |
|----------|--------|----------|
| Security Vulnerabilities | ⚠️ **Critical issues found** | High |
| Outdated Dependencies | ⚠️ **Multiple outdated packages** | Medium |
| Unnecessary Bloat | ⚠️ **Significant duplication** | Medium |

---

## 1. Security Vulnerabilities

### 🔴 CRITICAL: JavaScript Libraries

| Library | Version | CVE | Severity | Issue |
|---------|---------|-----|----------|-------|
| jQuery | 1.9.1, 1.11.3 | CVE-2020-11023 | High | XSS vulnerability (CISA known exploited) |
| jQuery | 1.9.1, 1.11.3 | CVE-2019-11358 | Medium | Prototype pollution |
| jQuery UI | 1.10.2, 1.10.3 | CVE-2016-7103 | Medium | XSS via closeText in dialog |
| Bootstrap | 2.3.1, 3.3.7 | CVE-2019-8331 | Medium | XSS via tooltip/popover data-template |
| Bootstrap | 3.3.7 | CVE-2018-14041 | Medium | XSS via data-container property |

**Locations affected:**
- `crm/js/timeline-2.9.1/lib/jquery-1.9.1.js`
- `crm/js/image-map/common/js/jquery-1.11.3.min.js`
- `crm/js/ui/jquery-ui-1.10.3.custom.min.js`
- `crm/ruko/template/plugins/jquery-ui/jquery-ui-1.10.2.custom.min.js`
- `crm/js/image-map/common/css/bootstrap.min.css` (Bootstrap 2.3.1)
- `crm/ruko/template/plugins/bootstrap/` (Bootstrap 3.3.7)

**Recommendation:** Upgrade immediately:
- jQuery → 3.7.1+
- jQuery UI → 1.13.2+
- Bootstrap → 3.4.1+ or 5.3.2+

### 🟡 MEDIUM: PHP Libraries

| Library | Version | Issue |
|---------|---------|-------|
| dompdf | 3.1.0 | Uses vulnerable php-svg-lib - GHSA-97m3-52wr-xvv2 (Feb 2024) - potential RCE via PHAR deserialization |
| Twilio SDK | Master (old) | Running from `twilio-php-master` suggests outdated; requires PHP >=5.5.0 indicates very old version |
| CodeMirror | 5.63.3 | Uses Puppeteer 1.20.0 in devDeps (outdated); main library should update to 5.65+ or CodeMirror 6 |

**Recommendation:**
- Update dompdf to latest 2.0.7+ (fixes SVG-related RCE issues)
- Replace Twilio SDK with versioned release (8.x+)
- Update CodeMirror to 5.65.x or migrate to CodeMirror 6

### 🟢 OK: Reviewed and Current

| Library | Version | Status |
|---------|---------|--------|
| PHPMailer | 6.8.0 | ✅ No known vulnerabilities |
| PHPSpreadsheet | 1.27.0 | ✅ Current as of 2023 |
| Stripe PHP | 9.6.0 | ✅ No known CVEs |
| firebase/php-jwt | ^8.0 compatible | ✅ Current |
| htmlpurifier | 4.15.0 | ✅ Current |

---

## 2. Outdated Dependencies

### Go Backend (`backend/go.mod`)

```
go 1.22.2
```

**Status:** ✅ Good - No external dependencies, using Go stdlib only.
**Note:** Consider updating to Go 1.23+ when production-ready.

### Python (`future-expansion/*/requirements.txt`)

| Package | Current Spec | Latest | Action |
|---------|--------------|--------|--------|
| flask | >=2.3.0 | 3.1.0 | Consider upgrade |
| twilio | >=8.0.0 | 9.4.0 | Consider upgrade |
| openai | >=1.0.0 | 1.58.1 | Consider upgrade |
| mysql-connector-python | >=8.0.0 | 9.1.0 | Consider upgrade |
| requests | >=2.31.0 | 2.32.3 | Minor update |

**Note:** These are in `future-expansion/` - not production. Pin specific versions when moving to production.

### PHP/JavaScript (CRM)

| Library | Current | Latest | Urgency |
|---------|---------|--------|---------|
| jQuery | 3.7.0 (main), 1.9.1/1.11.3 (plugins) | 3.7.1 | Low (main), **HIGH** (old versions) |
| Bootstrap | 3.3.7 | 5.3.2 | **HIGH** (security) |
| CodeMirror | 5.63.3 | 5.65.18 or 6.x | Medium |
| CKEditor | 4.21.0 | 4.25.0 or CKEditor 5 | Medium |
| PHPWord | 1.0.0 | 1.3.0 | Low |
| Google API Client | 2.18.3 | 2.18.3 | ✅ Current |

---

## 3. Bloat Analysis

### 🔴 Critical: Duplicate Vendor Directories

Found **19 separate vendor directories** under `crm/`, with significant duplication:

**Duplicate directories pattern:**
```
crm/includes/libs/          ← Primary
crm/ruko/includes/libs/     ← Complete duplicate
```

**Guzzle HTTP duplications:** 24 occurrences across 23 files
- Same guzzlehttp/guzzle vendored multiple times in:
  - GoogleDrive integration
  - Dropbox integration
  - PHPSpreadsheet
  - PHPStep

**Estimated bloat:** ~50MB+ of duplicate PHP vendor code

### 🟡 Medium: Unused Integration Libraries

Libraries that may be unused based on CLAUDE.md (which describes SignalWire/Twilio only):

| Library | Location | Likely Status |
|---------|----------|---------------|
| Novofon telephony | `crm/plugins/ext/telephony_modules/novofon/` | Possibly unused |
| Zadarma telephony | `crm/plugins/ext/telephony_modules/zadarma/` | Possibly unused |
| Yandex Disk storage | `crm/includes/libs/FileStorage/YandexDisk/` | Possibly unused |
| Steam authentication | `crm/includes/libs/social_login/Steam/` | Possibly unused |
| Twitter login | `crm/includes/libs/social_login/Twitter/` | Possibly unused |
| Square payment | `crm/plugins/ext/payment_modules/squareup/` | Confirm if used |

### 🟡 Medium: Vendored Libraries vs Composer

The CRM uses a non-standard vendoring approach:
- Libraries copied into versioned subdirectories (e.g., `PHPMailer/6.8.0/`)
- No root `composer.json` for unified dependency management
- Makes updates difficult and security patches harder to apply

---

## 4. Recommendations

### Immediate Actions (Security)

1. **jQuery/jQuery UI** - Replace all legacy versions with jQuery 3.7.1+
   ```bash
   # Files requiring update:
   crm/js/timeline-2.9.1/lib/jquery-1.9.1.js
   crm/js/image-map/common/js/jquery-1.11.3.min.js
   crm/ruko/template/plugins/bootstrap-datepicker/tests/assets/jquery-1.7.1.min.js
   ```

2. **Bootstrap** - Update to 3.4.1 minimum (security patches) or 5.x for modern features

3. **dompdf** - Update to 2.0.7+ to address SVG-related RCE vulnerabilities

### Short-Term Actions (Maintainability)

1. **Remove duplicate vendor directories** - `crm/ruko/` appears to be a copy of `crm/`
   - Verify if `ruko/` is actively used
   - If backup, move to archive
   - Saves ~50MB+ and reduces confusion

2. **Audit unused integrations** - Check if Novofon, Zadarma, Yandex, Steam, Twitter are used
   - Remove if unused to reduce attack surface

3. **Pin Python dependencies** - Change from `>=` to exact versions in requirements.txt

### Long-Term Actions (Architecture)

1. **Implement root composer.json** for the CRM to manage all PHP dependencies centrally:
   ```json
   {
     "require": {
       "phpmailer/phpmailer": "^6.8",
       "phpoffice/phpspreadsheet": "^1.29",
       "dompdf/dompdf": "^2.0",
       "stripe/stripe-php": "^15.0"
     }
   }
   ```

2. **Consider npm/yarn for frontend** - Manage jQuery, Bootstrap, CodeMirror through package manager

3. **Set up automated dependency scanning** - Use tools like:
   - `composer audit` (PHP)
   - `npm audit` (Node.js)
   - `govulncheck` (Go)
   - Dependabot or Snyk for automated PRs

---

## Sources

- [CVE Details - jQuery UI](https://www.cvedetails.com/vulnerability-list/vendor_id-6538/product_id-31126/Jquery-Jquery-Ui.html)
- [CVE Details - Bootstrap 3.3.7](https://www.cvedetails.com/vulnerability-list/vendor_id-19522/product_id-51406/version_id-286029/Getbootstrap-Bootstrap-3.3.7.html)
- [dompdf Security Advisories](https://github.com/dompdf/dompdf/security)
- [PHPMailer Security](https://github.com/PHPMailer/PHPMailer/blob/master/SECURITY.md)
- [Snyk - PHPMailer](https://security.snyk.io/package/composer/phpmailer%2Fphpmailer)
