# SignalWire SMS Campaign Registration

This document contains the information needed to register your 10DLC SMS campaign with SignalWire for Mechanics Saint Augustine.

## Campaign Registration Form

### Basic Information

**Campaign Name:**
```
Mechanics Saint Augustine Customer Service
```

**Description (minimum 40 characters):**
```
Automated appointment confirmations, service updates, and quote notifications for mobile mechanic services. Customers receive SMS when they request quotes via phone or web, appointment reminders before scheduled service, mechanic arrival notifications, and service completion updates. All messages include opt-out instructions and are transactional in nature related to customer-initiated service requests.
```

### SMS Use Case Category

**Primary Use Case:**
```
Customer Care
```

**Subcategory:**
```
Account Notifications / Service Updates
```

**Justification:**
Your business sends:
- Appointment confirmations and reminders
- Quote acknowledgments
- Service status updates (mechanic en route, service complete)
- Payment reminders
All are transactional and related to customer service requests.

### Campaign Templates

**Example Message Templates:**

**Template 1 - Quote Confirmation:**
```
Thank you for requesting a quote from Mechanics Saint Augustine! We've received your request for [SERVICE] and will contact you shortly at [PHONE]. Reply STOP to opt-out.
```

**Template 2 - Appointment Reminder:**
```
Hi [NAME], your [SERVICE] appointment is scheduled for [DATE] at [TIME]. [MECHANIC] will arrive at [ADDRESS]. Reply STOP to opt-out.
```

**Template 3 - Mechanic En Route:**
```
[MECHANIC] is on the way to your location! ETA: [TIME]. For any questions, call us at (904) 217-5152. Reply STOP to opt-out.
```

**Template 4 - Service Complete:**
```
Your [SERVICE] is complete! Total: $[AMOUNT]. View invoice and pay at: [LINK]. Thank you for choosing Mechanics Saint Augustine! Reply STOP to opt-out.
```

**Template 5 - Payment Reminder:**
```
Reminder: Invoice #[NUMBER] for $[AMOUNT] is pending. Pay online at: [LINK] or call (904) 217-5152. Reply STOP to opt-out.
```

### Opt-in / Opt-out

**Opt-in Method:**
```
Customers opt-in by:
1. Calling our phone number and requesting service
2. Submitting a quote request form on our website
3. Creating an account in our customer portal

By initiating contact with our business, customers consent to receive transactional SMS messages related to their service requests.
```

**Opt-out Instructions:**
```
All SMS messages include "Reply STOP to opt-out" language.
When a customer replies STOP, UNSUBSCRIBE, CANCEL, or similar keywords, they are immediately removed from our messaging list.
Opt-out confirmation is sent: "You have been unsubscribed from Mechanics Saint Augustine SMS notifications. You will no longer receive messages from this number."
```

**Opt-out Processing:**
```
Automated via SignalWire's built-in opt-out handling
Manual opt-outs are processed within 24 hours
Customer records are flagged in CRM to prevent future messaging
```

### Legal Compliance

**Business Information:**
- **Business Name:** Mechanics Saint Augustine
- **Business Type:** Mobile Automotive Repair Service
- **Business Address:** [Your business address]
- **Phone Number:** +1 (904) 217-5152
- **Website:** https://mechanicstaugustine.com
- **EIN/Tax ID:** [Your EIN]

**Compliance Statement:**
```
Mechanics Saint Augustine complies with all TCPA (Telephone Consumer Protection Act) regulations and CTIA guidelines for SMS messaging. We:

1. Only send messages to customers who have initiated contact with our business
2. Include clear opt-out instructions in every message
3. Honor all opt-out requests immediately
4. Do not send marketing or promotional content without explicit opt-in
5. Keep records of customer consent and opt-out requests
6. Provide customer support via phone for any messaging concerns

All messages are transactional in nature and directly related to services requested by the customer.
```

### Terms and Conditions

**Customer Facing Terms:**
```
SMS TERMS AND CONDITIONS

By requesting service from Mechanics Saint Augustine, you consent to receive transactional SMS messages related to your service request including:
- Quote confirmations
- Appointment reminders
- Service updates
- Payment notifications

Message and data rates may apply. Message frequency varies based on your service requests.

To opt-out at any time, reply STOP to any message. For help, reply HELP or call (904) 217-5152.

Privacy Policy: https://mechanicstaugustine.com/privacy
Terms of Service: https://mechanicstaugustine.com/terms
```

### Status Callback URL

**Webhook URL for Delivery Receipts:**
```
https://mechanicstaugustine.com/quote/status_callback.php
```

**Purpose:**
This endpoint receives delivery status updates from SignalWire to track:
- Message delivery confirmation
- Failed delivery notifications
- Opt-out requests

**Security:**
- HTTPS only (TLS 1.2+)
- Validates SignalWire signature
- Logs all callbacks to `api/sms_status.log`

## Additional Information for Approval

### Business Verification

**Documents to Provide:**
1. ✅ Business License or Registration
2. ✅ EIN Letter from IRS
3. ✅ Website URL showing business information
4. ✅ Business phone number verification

### Message Volume Estimates

**Expected Monthly Volume:**
```
- Quote confirmations: ~100-200 per month
- Appointment reminders: ~80-150 per month
- Service updates: ~80-150 per month
- Payment reminders: ~20-50 per month
Total: ~300-550 messages per month
```

### Brand Registration

**Brand Name:** Mechanics Saint Augustine
**Brand Type:** Sole Proprietor / LLC / Corporation (choose applicable)
**Industry:** Automotive Repair and Maintenance
**Business Model:** Mobile mechanic services - on-site vehicle repair

## Campaign Approval Timeline

1. **Submit Campaign:** Provide all information above
2. **Brand Verification:** 2-5 business days
3. **Campaign Review:** 3-5 business days
4. **Approval:** Campaign becomes active
5. **Testing:** Verify SMS sending works

**Total Estimated Time:** 1-2 weeks

## Post-Approval Setup

Once approved, update your configuration:

```php
// In api/.env.local.php

// Enable SMS notifications
define('SMS_ENABLED', true);

// Campaign information
const SMS_CAMPAIGN_ID = 'your-approved-campaign-id';
const SMS_BRAND_ID = 'your-approved-brand-id';

// Ensure this is set
define('TWILIO_SMS_FROM', '+19042175152');
```

## Testing After Approval

```bash
# Test SMS sending
php test_workflow.php

# Check SMS logs
tail -f api/sms_status.log

# Verify opt-out handling
# Send "STOP" to your number and verify it's processed
```

## Support

**SignalWire Support:**
- Dashboard: https://mobilemechanic.signalwire.com
- Support: support@signalwire.com
- Documentation: https://developer.signalwire.com

**Campaign Registration Help:**
- Email: compliance@signalwire.com
- Phone: SignalWire support line

## Important Notes

- ✅ **Only transactional messages** - No marketing without explicit opt-in
- ✅ **Always include opt-out** - Every message must have "Reply STOP"
- ✅ **Honor opt-outs immediately** - Process within seconds/minutes
- ✅ **Keep records** - Log all consent and opt-out events
- ✅ **Validate phone numbers** - Ensure proper E.164 format
- ✅ **Rate limiting** - Respect carrier guidelines (1-2 msgs/sec max)

## Campaign Summary

**Purpose:** Transactional customer service notifications
**Audience:** Customers who request automotive repair services
**Volume:** Low (300-550 msgs/month)
**Compliance:** TCPA compliant, clear opt-out, no marketing
**Industry:** Automotive repair services
**Brand:** Mechanics Saint Augustine (established business with website)

This campaign is low-risk and should be approved quickly as it's purely transactional customer service communication.
