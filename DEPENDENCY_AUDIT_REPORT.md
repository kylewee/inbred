# Dependency Audit Report
**Generated:** 2025-12-19
**Project:** Mechanic Saint Augustine Mobile App

---

## Executive Summary

This audit identified **critical security vulnerabilities** in outdated frontend dependencies and opportunities for optimization. The primary concerns are:

- **jQuery 2.2.0** (2016) - Multiple XSS vulnerabilities
- **Bootstrap 3.3.6** (2016) - XSS vulnerabilities, EOL reached
- **phpMyAdmin 5** - Outdated, missing security patches from 2025
- **Frontend bloat** - Duplicate libraries and unused files (2.3MB JS)

**Risk Level:** HIGH - Immediate action recommended for production deployment.

---

## 1. Outdated Packages

### Frontend Dependencies

#### jQuery 2.2.0 → Upgrade to 3.7.x
- **Current:** v2.2.0 (Released: 2016)
- **Latest:** v3.7.1 (December 2024)
- **Status:** 🔴 CRITICAL - 8+ years outdated
- **Issues:**
  - CVE-2020-11022: XSS via HTML manipulation methods
  - CVE-2020-11023: XSS via `<option>` elements
  - CVE-2019-11358: Object.prototype pollution
- **Impact:** Attackers can execute arbitrary code through DOM manipulation
- **Action:** Upgrade to jQuery 3.7.1 immediately

#### Bootstrap 3.3.6 → Upgrade to 5.3.x
- **Current:** v3.3.6 (Released: 2016)
- **Latest:** v5.3.3 (2024)
- **Status:** 🔴 CRITICAL - EOL reached
- **Issues:**
  - CVE-2024-6485: XSS in Button component
  - CVE-2024-6484: XSS in Carousel component
  - CVE-2024-6531: Input sanitization issues
- **Impact:** XSS attacks possible, no official patches available
- **Action:** Migrate to Bootstrap 5.3.3 (breaking changes expected)

### Docker Images

#### PHP 8.2-fpm-alpine → Update to 8.2.30 (or upgrade to 8.3/8.4)
- **Current:** php:8.2-fpm-alpine (unspecified minor version)
- **Latest:** php:8.2.30-fpm-alpine
- **Status:** 🟡 WARNING - Security-only support since Dec 2024
- **Issues:** PHP 8.2 EOL: December 31, 2026
- **Vulnerabilities Fixed in 8.2.30:**
  - CVE-2025-14177, CVE-2025-14178, CVE-2025-14180
- **Recommendation:**
  - Short-term: Pin to `php:8.2.30-fpm-alpine`
  - Long-term: Upgrade to PHP 8.4 (active support until Nov 2026)

#### MariaDB 10.11 → Update to 10.11.15
- **Current:** mariadb:10.11 (unspecified minor)
- **Latest:** mariadb:10.11.15
- **Status:** 🟢 GOOD - LTS supported until 2026
- **Action:** Pin to `mariadb:10.11.15` for security patches

#### phpMyAdmin 5 → Upgrade to 5.2.2
- **Current:** phpmyadmin/phpmyadmin:5 (likely 5.2.0 or 5.2.1)
- **Latest:** phpmyadmin/phpmyadmin:5.2.2 (Jan 2025)
- **Status:** 🟡 WARNING
- **Issues:**
  - CVE-2025-24530: XSS in Check Tables feature
  - CVE-2025-24529: XSS in Insert tab
  - CVE-2024-2961: glibc/iconv vulnerability
- **Action:** Update to `phpmyadmin/phpmyadmin:5.2.2`

#### Caddy 2-alpine
- **Current:** caddy:2-alpine
- **Latest:** caddy:2.10.2-alpine
- **Status:** 🟢 EXCELLENT - No known vulnerabilities in 2025
- **Action:** Pin to `caddy:2.10.2-alpine` for reproducibility

---

## 2. Security Vulnerabilities Summary

| Package | Current | Vulnerabilities | Severity | CVEs |
|---------|---------|-----------------|----------|------|
| jQuery | 2.2.0 | XSS, Prototype Pollution | HIGH | CVE-2020-11022, CVE-2020-11023, CVE-2019-11358 |
| Bootstrap | 3.3.6 | XSS (Button, Carousel) | HIGH | CVE-2024-6485, CVE-2024-6484, CVE-2024-6531 |
| PHP | 8.2.x | Multiple (if not 8.2.30) | MEDIUM | CVE-2025-14177/8/180 |
| phpMyAdmin | 5.x | XSS, DoS potential | MEDIUM | CVE-2025-24530, CVE-2025-24529 |
| MariaDB | 10.11.x | Unknown (if not .15) | LOW | N/A |
| Caddy | 2.x | None in 2025 | NONE | N/A |

---

## 3. Unnecessary Bloat

### Frontend Assets (2.3MB)

#### Duplicate jQuery Versions
**Issue:** Multiple jQuery versions present
- `/Mobile-mechanic/JS/jquery.min.js` (jQuery 2.2.0) - 85KB
- `/Mobile-mechanic/JS/jquery-3.3.1.min.js` - 85KB

**Action:** Remove jQuery 2.2.0, keep only jQuery 3.7.1 (upgraded version)

#### Duplicate Bootstrap Files
**Issue:** Both minified and non-minified versions, plus source maps
- `bootstrap.js` (121KB) + `bootstrap.min.js` (50KB)
- `bootstrap.bundle.js` (208KB) + `bootstrap.bundle.min.js` (70KB)
- 4 source map files (1MB total)

**Action:**
- Production: Keep only `.min.js` files
- Remove: Source maps, non-minified versions
- **Savings:** ~900KB

#### Unused/Redundant Libraries
Files potentially unnecessary for production:
- `modernizr.js` - Feature detection (often unused in modern browsers)
- `jquery-ui.js` (509KB) - Heavy library, check if actually used
- `jquery.easing.min.js` - Redundant if using CSS transitions
- `jquery.hoverTransition.js` - Can be replaced with CSS
- `jquery.picEyes.js` - Specific plugin, verify usage
- `bgfader.js` - Custom script, verify necessity
- Multiple small scripts (classie.js, grayscale.js) - Consolidate if possible

**Action:** Audit usage in HTML files, remove unused scripts
**Potential Savings:** 500KB-800KB

#### CSS Bloat
- `bootstrap.css` (139KB) + `bootstrap.min.css` (119KB) - Keep only minified
- `font-awesome.min.css` (29KB) - Consider switching to SVG icons or subset
- Multiple CSS files that could be concatenated/minified

**Action:**
- Remove non-minified CSS files
- Consider using only needed Font Awesome icons
- **Savings:** ~150KB

### Backend Dependencies

#### No Composer Dependencies
**Finding:** No `composer.json` found - PHP project has no package manager
**Issue:** Manual dependency management is error-prone
**Recommendation:**
- Initialize Composer for any future PHP libraries
- Consider adding autoloading for custom classes

---

## 4. Recommendations

### Critical (Do Immediately)

1. **Update jQuery to 3.7.1**
   ```html
   <!-- Replace in HTML files -->
   <script src="https://code.jquery.com/jquery-3.7.1.min.js"
           integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
           crossorigin="anonymous"></script>
   ```

2. **Update phpMyAdmin to 5.2.2**
   ```yaml
   # docker-compose.yml
   phpmyadmin:
     image: phpmyadmin/phpmyadmin:5.2.2
   ```

3. **Pin PHP to 8.2.30**
   ```dockerfile
   # Dockerfile
   FROM php:8.2.30-fpm-alpine
   ```

4. **Pin MariaDB to 10.11.15**
   ```yaml
   # docker-compose.yml
   db:
     image: mariadb:10.11.15
   ```

5. **Remove jQuery 2.2.0 completely**
   ```bash
   rm /Mobile-mechanic/JS/jquery.min.js
   ```

### High Priority (This Sprint)

6. **Upgrade Bootstrap to 5.3.3**
   - **Warning:** Breaking changes - requires template updates
   - Migration guide: https://getbootstrap.com/docs/5.3/migration/
   - Update: grid system, utilities, components
   - Test thoroughly after migration

7. **Clean up frontend bloat**
   ```bash
   # Remove non-minified files
   cd Mobile-mechanic/JS
   rm bootstrap.js bootstrap.bundle.js *.js.map

   cd ../css2
   rm bootstrap.css
   ```

8. **Audit JavaScript usage**
   - Create inventory of which scripts are actually used
   - Remove unused libraries
   - Consolidate small utilities into one file

### Medium Priority (Next Month)

9. **Plan PHP 8.4 upgrade**
   - Test application with PHP 8.4-rc
   - Review deprecated features
   - Plan migration before PHP 8.2 EOL (2026)

10. **Implement Composer for PHP**
    ```bash
    composer init
    composer require --dev phpunit/phpunit
    ```

11. **Add dependency scanning to CI/CD**
    - Use GitHub Dependabot or Snyk
    - Automate security vulnerability alerts

12. **Optimize asset delivery**
    - Implement asset concatenation/minification
    - Add cache busting (file hashes)
    - Consider CDN for jQuery/Bootstrap

### Low Priority (Nice to Have)

13. **Replace Font Awesome with SVG icons**
    - Reduce payload by 90%
    - Better customization

14. **Migrate to modern build tools**
    - Consider Vite or Webpack
    - Tree shaking for smaller bundles
    - Modern CSS (PostCSS)

15. **Consider API-only backend**
    - Remove phpMyAdmin from production
    - Use SSH tunneling for admin access

---

## 5. Implementation Plan

### Phase 1: Critical Security Fixes (Week 1)
- [ ] Update all Docker image versions in docker-compose.yml
- [ ] Upgrade jQuery to 3.7.1 in all HTML files
- [ ] Remove jQuery 2.2.0 files
- [ ] Test all interactive features
- [ ] Deploy to staging

### Phase 2: Frontend Cleanup (Week 2)
- [ ] Audit script usage across all HTML files
- [ ] Remove unused JavaScript libraries
- [ ] Remove non-minified files and source maps
- [ ] Test all pages for broken functionality
- [ ] Deploy to staging

### Phase 3: Bootstrap Migration (Weeks 3-4)
- [ ] Create feature branch for Bootstrap 5 migration
- [ ] Update Bootstrap CDN links or local files
- [ ] Update HTML templates (class names, components)
- [ ] Update custom CSS (variable names, utilities)
- [ ] Comprehensive testing
- [ ] Deploy to staging
- [ ] Production deployment after validation

### Phase 4: Long-term Improvements (Month 2+)
- [ ] Set up Composer for PHP dependencies
- [ ] Add automated security scanning
- [ ] Optimize asset delivery pipeline
- [ ] Plan PHP 8.4 migration

---

## 6. Testing Checklist

After implementing changes, verify:

- [ ] All forms submit correctly (jQuery AJAX)
- [ ] Interactive UI components work (Bootstrap modals, dropdowns, etc.)
- [ ] Mobile responsiveness maintained
- [ ] No JavaScript console errors
- [ ] Database connections functional
- [ ] phpMyAdmin accessible and functional
- [ ] SMS/SignalWire integrations working
- [ ] Quote intake system operational
- [ ] Health check endpoint returns 200

---

## 7. Estimated Impact

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Security Vulnerabilities | 10+ CVEs | 0 CVEs | -100% |
| Frontend Asset Size | 2.3MB | ~1.2MB | -48% |
| Page Load Time | Baseline | -30-40% | Faster |
| Maintenance Risk | High | Low | Better |
| Docker Image Versions | Floating | Pinned | Reproducible |

---

## 8. References

- [jQuery Security Vulnerabilities](https://www.cvedetails.com/vulnerability-list/vendor_id-6538/product_id-11031/Jquery-Jquery.html)
- [Bootstrap Security Vulnerabilities](https://www.cvedetails.com/version/1010402/Getbootstrap-Bootstrap-3.3.6.html)
- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [phpMyAdmin 5.2.2 Release](https://www.phpmyadmin.net/news/2025/1/21/phpMyAdmin-522-is-released/)
- [MariaDB LTS Status](https://mariadb.org/mariadb-10-11-is-lts/)
- [Caddy 2025 Security Record](https://stack.watch/product/caddyserver/caddy/)

---

## Appendix: Commands for Updates

### Update docker-compose.yml
```yaml
services:
  db:
    image: mariadb:10.11.15
    # ... rest of config

  phpmyadmin:
    image: phpmyadmin/phpmyadmin:5.2.2
    # ... rest of config

  caddy:
    image: caddy:2.10.2-alpine
    # ... rest of config
```

### Update Dockerfile
```dockerfile
FROM php:8.2.30-fpm-alpine

# ... rest of config
```

### Clean up frontend bloat
```bash
# Remove old jQuery
rm Mobile-mechanic/JS/jquery.min.js

# Remove non-minified JS files
find Mobile-mechanic/JS -type f \( -name "*.js" ! -name "*.min.js" \) -delete

# Remove source maps
find Mobile-mechanic/JS -type f -name "*.map" -delete

# Remove non-minified CSS
find Mobile-mechanic/css2 -type f \( -name "*.css" ! -name "*.min.css" \) -delete
```

---

**Report End**
