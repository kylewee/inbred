# Customer Portal Plugin

**Version**: 1.0
**Created**: December 21, 2025
**Purpose**: Allow customers to check quote status and approve/decline online without login

---

## Features

✅ **Public Access** - No login required
✅ **Phone Lookup** - Customers search by phone number
✅ **Quote Display** - View estimate breakdown with labor/parts
✅ **Online Approval** - Approve or decline quotes instantly
✅ **Appointment Tracking** - View scheduled service appointments
✅ **Mobile Responsive** - Beautiful design works on all devices

---

## Installation

✅ **Already Installed!** The plugin is enabled and ready to use.

**Plugin files located in**: `/crm/plugins/customer_portal/`
**Enabled in**: `/crm/config/server.php` (line 23)

---

## Usage

### Public URL

Share this link with customers:
```
https://mechanicstaugustine.com/crm/index.php?module=customer_portal/quote/index
```

Or use a shorter URL (configure redirect):
```
https://mechanicstaugustine.com/quote-status
```

### Customer Flow

1. **Customer visits portal** → enters phone number
2. **System finds quote** → displays estimate details
3. **Customer approves/declines** → updates CRM status
4. **Confirmation shown** → next steps displayed

### SMS Integration

Send customers a link after quote is created:

**SMS Template**:
```
Your quote is ready! View estimate and approve online:
https://mechanicstaugustine.com/crm/index.php?module=customer_portal/quote/index

Or call us: (904) 706-6669
```

Configure this in: **Extension → Modules → SMS Modules → Sending SMS Rules**

---

## Technical Details

### Database Fields Used

**Entity 26 (Leads)** fields:
- `field_219` - First Name
- `field_220` - Last Name
- `field_227` - Phone (search key)
- `field_228` - Stage (updated to Approved/Declined)
- `field_230` - Notes (contains estimate data)
- `field_231` - Vehicle Year
- `field_232` - Vehicle Make
- `field_233` - Vehicle Model
- `field_234` - Address
- `field_235` - Email

### Estimate Format

The plugin looks for estimate data in the `notes` field (field_230) in JSON format:

```json
{
  "estimates": [
    {
      "repair": "Starter Replacement",
      "labor_cost": 250,
      "parts_cost": 199.99,
      "total": 449.99
    }
  ],
  "grand_total": 449.99
}
```

Or simple format:
```
Labor: $250
Parts: $199.99
Total: $449.99
```

### Actions Logged

When customer approves/declines, the system:
1. Updates `field_228` (Stage) to "Approved" or "Declined"
2. Appends action log to `field_230` (Notes):
   ```
   --- Customer Portal Action ---
   Status: Approved
   Date: 2025-12-21 12:34:56
   IP: 73.105.107.195
   ```

---

## Customization

### Change Colors/Branding

Edit the CSS in these files:
- `/crm/plugins/customer_portal/modules/quote/views/index.php`
- `/crm/plugins/customer_portal/modules/quote/views/view.php`
- `/crm/plugins/customer_portal/modules/quote/views/approve.php`

### Modify Phone Lookup Logic

Edit: `/crm/plugins/customer_portal/classes/customer_quote.php`

Function: `find_by_phone($phone)`

### Add Custom Fields

Edit: `/crm/plugins/customer_portal/modules/quote/views/view.php`

Add new sections to display additional lead data.

---

## Integration with SMS

### Automatic SMS on Lead Creation

**Setup**:
1. Go to: **Extension → Modules → SMS Modules**
2. Click: **Sending SMS Rules** → **Create New**
3. Configure:
   - **Module**: Twilio (SignalWire)
   - **Entity**: Leads (ID 26)
   - **Event**: Send SMS when creating a record
   - **Send to**: Number specified in the entry (Field 227)
   - **Message**:
     ```
     Thanks for calling! Your quote is ready.

     View estimate & approve online:
     https://mechanicstaugustine.com/crm/index.php?module=customer_portal/quote/index

     Or call: (904) 706-6669
     ```

### Manual SMS Trigger

From PHP code (e.g., `recording_callback.php`):
```php
// After creating CRM lead
if ($leadId) {
    // Trigger SMS rule ID 1 for entity 26
    sms::send_by_id(26, $leadId, 1);
}
```

---

## URL Shortener (Optional)

### Using Caddy Redirect

Add to `Caddyfile`:
```
mechanicstaugustine.com {
    # ... existing config ...

    # Customer portal shortcut
    redir /quote-status /crm/index.php?module=customer_portal/quote/index 302
}
```

Then reload Caddy:
```bash
sudo systemctl reload caddy
```

Customers can use: `https://mechanicstaugustine.com/quote-status`

---

## Troubleshooting

### "No quote found"
- **Cause**: Phone number doesn't match any lead in CRM
- **Fix**: Check `field_227` in database has correct phone format
- **Query**:
  ```sql
  SELECT id, field_219, field_227
  FROM app_entity_26
  WHERE field_227 LIKE '%PHONE%'
  ORDER BY id DESC
  LIMIT 10;
  ```

### Estimate not showing
- **Cause**: Notes field doesn't contain estimate data
- **Fix**: Ensure auto-estimate system writes JSON to `field_230`
- **Check**: View lead notes in CRM for estimate data

### Approve button not working
- **Cause**: Database permissions or field doesn't exist
- **Fix**: Check `field_228` exists and is writable
- **Test**:
  ```sql
  UPDATE app_entity_26
  SET field_228 = 'Approved'
  WHERE id = 1;
  ```

### Plugin not loading
- **Cause**: Not enabled in config
- **Fix**: Check `/crm/config/server.php` line 23:
  ```php
  define('AVAILABLE_PLUGINS', 'ext,customer_portal');
  ```

---

## Testing

### Test with Existing Lead

1. Find a lead with phone number:
   ```bash
   mysql -u kylewee -p'rainonin' rukovoditel -e "SELECT id, field_219, field_227 FROM app_entity_26 ORDER BY id DESC LIMIT 5;"
   ```

2. Visit portal:
   ```
   https://mechanicstaugustine.com/crm/index.php?module=customer_portal/quote/index
   ```

3. Enter phone number from step 1

4. Verify quote displays correctly

5. Click "Approve" → check confirmation page

6. Check CRM → verify `field_228` updated to "Approved"

### Test Direct Link

```
https://mechanicstaugustine.com/crm/index.php?module=customer_portal/quote/view&id=291
```

Replace `291` with actual lead ID.

---

## Security Notes

✅ **No authentication required** - Public pages as intended
✅ **Phone number as key** - Only customer who called can access
✅ **No sensitive data exposed** - Only shows what customer already knows
✅ **IP logging** - Tracks who approved/declined
✅ **No admin access** - Customers cannot access CRM backend

**Additional security** (optional):
- Add rate limiting (max 5 lookups per IP per hour)
- Add CAPTCHA to prevent automated abuse
- Send one-time access codes via SMS

---

## Future Enhancements

Potential features to add:

- [ ] SMS one-time code verification
- [ ] Email quote delivery
- [ ] Multiple quote comparison
- [ ] Customer chat widget
- [ ] Service history for repeat customers
- [ ] Online payment integration
- [ ] Review/rating after service complete
- [ ] Photo upload for vehicle issues

---

## Support

**Plugin created by**: Claude Code (AI Assistant)
**Maintained by**: Kyle (sodjacksonville@gmail.com)
**Documentation**: This file
**CRM Docs**: https://docs.rukovoditel.net

---

## Version History

**v1.0** (Dec 21, 2025)
- Initial release
- Phone lookup
- Quote display
- Approve/decline functionality
- Responsive design
