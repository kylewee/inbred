# Domain Migration: landimpressions.com → sodjacksonvillefl.com

**Goal:** Move site to new domain without losing SEO rankings

---

## Pre-Migration Checklist

- [ ] Backup everything on landimpressions.com
- [ ] Copy all content, images, pages to sodjacksonvillefl.com
- [ ] Make sure sodjacksonvillefl.com looks identical

---

## Step 1: Set Up sodjacksonvillefl.com

1. Point DNS to your server
2. Set up SSL (https)
3. Copy all files from landimpressions.com
4. Test that everything works on new domain

---

## Step 2: 301 Redirects (CRITICAL)

This tells Google "we moved" and transfers SEO value.

**In .htaccess on landimpressions.com:**
```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^(www\.)?landimpressions\.com$ [NC]
RewriteRule ^(.*)$ https://sodjacksonvillefl.com/$1 [R=301,L]
```

**Or in Caddyfile:**
```
landimpressions.com, www.landimpressions.com {
    redir https://sodjacksonvillefl.com{uri} permanent
}
```

This redirects:
- landimpressions.com/about → sodjacksonvillefl.com/about
- landimpressions.com/services → sodjacksonvillefl.com/services
- etc.

---

## Step 3: Google Search Console

1. Add sodjacksonvillefl.com to Search Console
2. Verify ownership
3. Use "Change of Address" tool:
   - Go to landimpressions.com property
   - Settings → Change of Address
   - Select sodjacksonvillefl.com as new site
4. Submit new sitemap for sodjacksonvillefl.com

---

## Step 4: Update Everything

- [ ] Google Business Profile (if you have one)
- [ ] Any directory listings (Yelp, etc.)
- [ ] Social media links
- [ ] Email signatures
- [ ] Business cards / print materials

---

## Step 5: Keep Redirects Running

**IMPORTANT:** Keep landimpressions.com redirecting for at least 1 year.

Don't let the domain expire. The 301 redirects pass SEO value to the new domain.

---

## Timeline

| When | What |
|------|------|
| Day 1 | Set up sodjacksonvillefl.com with same content |
| Day 2 | Add 301 redirects on landimpressions.com |
| Day 3 | Submit Change of Address in Search Console |
| Week 1 | Monitor rankings (may dip temporarily) |
| Month 1 | Rankings should stabilize/recover |
| Month 3 | New domain fully indexed |
| Year 1+ | Keep redirects active |

---

## What to Expect

- **Temporary ranking dip** - Normal, usually recovers in 2-4 weeks
- **Both domains passing value** - The 18-year-old domain (landimpressions) passes trust to the 17-year-old (sodjacksonvillefl)
- **Combined authority** - You end up with the best of both

---

## Why This Works

You're not abandoning landimpressions.com - you're telling Google "this moved here" with a permanent redirect. Google follows the redirect and transfers the ranking signals.

Since BOTH domains are aged (2007 and 2008), you're combining their authority.

---

*Created: December 31, 2025*
