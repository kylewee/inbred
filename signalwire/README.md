# SignalWire Campaign Setup

## Brand Information
- **Brand Name**: Mobilemechanic.best
- **Brand ID**: ff9797fe-a05e-4131-a6cf-6f5d2ca7bf33
- **Status**: Pending (as of Dec 4, 2025)

## Campaign Details

### Name
Mobilemechanic.best - Customer Service & Scheduling

### Description
Professional mobile automotive repair service communications. Used for appointment scheduling, service confirmations, technician dispatch notifications, estimate delivery, invoice reminders, emergency roadside assistance coordination, and customer support messages for on-site mechanic services in the Jacksonville area.

### SMS Use Case Category
**Customer Care** (or Mixed for promotional messages)

## Sample Message Templates

### 1. Appointment Confirmation
```
Hi {Name}, your mobile mechanic appointment is confirmed for {Date} at {Time}. Our technician will arrive at {Location}. Reply STOP to opt-out.
```

### 2. Technician En Route
```
Your technician {TechName} is on the way and will arrive in approximately {Minutes} minutes. Call us at {Phone} with questions.
```

### 3. Estimate Ready
```
Your vehicle estimate is ready. Total: ${Amount}. View details and approve at {Link}. Questions? Reply or call {Phone}.
```

## Opt-in/Opt-out

**Opt-in Method**: Customers provide phone number when booking service online or via phone. Explicit consent obtained for service-related SMS.

**Opt-out**: All messages include "Reply STOP to opt-out" instructions. Opt-outs processed immediately.

## Status Callback URL
- Production: `https://mechanicstaugustine.com/api/campaign-status`
- Development: TBD

## Port-In Request
- **Ticket**: #34404
- **Contact**: Hale White (Carrier Operations) - carrierops@signalwire.zohodesk.com

## Next Steps
1. Wait for brand approval
2. Register campaign once brand is approved
3. Complete port-in process
4. Set up webhook endpoint (optional)
