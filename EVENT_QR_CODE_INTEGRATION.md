# Event QR Code & PDF Ticket Integration

## Overview
This document describes the integration of QR code generation and PDF ticket functionality for the event management system using external bundles.

## External Bundles Used

### 1. **endroid/qr-code-bundle** (v6.0)
- Generates QR codes for event participation tickets
- Used to create scannable codes containing participant information
- Supports both PNG and SVG output formats

### 2. **nucleos/dompdf-bundle** (v4.3)
- Generates PDF tickets from HTML templates
- Allows participants to download printable tickets
- Supports embedded images (including QR codes)

## New Files Created

### Services
- `src/Service/EventQrCodeService.php` - QR code generation service for event tickets

### Templates
- `templates/Topnavbar/evenement/participation_details.html.twig` - Participant ticket view page
- `templates/Topnavbar/evenement/participation_pdf.html.twig` - PDF ticket template

### Updated Files
- `src/Controller/FrontEvenementController.php` - Added 3 new routes
- `templates/emails/confirmed.html.twig` - Added QR code section
- `templates/emails/promotion.html.twig` - Added QR code section

## New Routes

### 1. Participation Details Page
**Route:** `/participation/{id}/details`  
**Name:** `app_front_participation_details`  
**Method:** GET  
**Description:** Displays the full ticket with QR code, participant info, and print/download options

### 2. QR Code Generation
**Route:** `/participation/{id}/qr-code`  
**Name:** `app_front_participation_qr_code`  
**Method:** GET  
**Description:** Generates and returns a QR code image (PNG/SVG) containing:
- Ticket reference number
- Participant name and email
- Event title
- Activity title

### 3. PDF Ticket Download
**Route:** `/participation/{id}/pdf`  
**Name:** `app_front_participation_pdf`  
**Method:** GET  
**Description:** Generates and downloads a PDF ticket with embedded QR code

## Features

### Email Enhancements
Both confirmation and promotion emails now include:
- **QR Code Image** - Embedded directly in the email
- **View Online Button** - Links to the participation details page
- Professional styling matching the HR One brand

### Participation Details Page
- **Ticket-style design** with event header
- **Participant information** (name, email, reference number)
- **Event details** (title, location, price)
- **Selected activity** with description
- **QR Code** for entry verification
- **Download PDF button** - Downloads printable ticket
- **Print button** - Browser print with optimized layout
- **Breadcrumb navigation** back to events

### PDF Ticket
- **Professional layout** optimized for printing
- **Embedded QR code** using absolute URL
- **All participant and event details**
- **Footer** with reference information
- **Compact design** suitable for A4 printing

## QR Code Content
Each QR code contains:
```
HR One Event Ticket
Ref: #HR1-{participant_id}
Participant: {full_name}
Email: {email}
Event: {event_title}
Activity: {activity_title}
```

## Technical Implementation

### EventQrCodeService
```php
- buildParticipationQr(string $data): ResultInterface
  - Size: 300x300 pixels
  - Error correction: High
  - Colors: HR One brand colors (#0f172a foreground, white background)
  - Format: PNG (if GD available) or SVG
```

### Controller Methods
```php
- participationDetails(ParticipationEvenement $participation): Response
- participationQrCode(ParticipationEvenement $participation, EventQrCodeService $qrCodeService): Response
- participationPdf(ParticipationEvenement $participation, DompdfWrapperInterface $dompdfWrapper): Response
```

## User Flow

1. **User registers for event** → Receives confirmation email with QR code
2. **Clicks "View online"** → Opens participation details page
3. **Can download PDF** → Gets printable ticket with QR code
4. **Can print directly** → Browser print with optimized layout
5. **Presents QR code** → At event entrance for verification

## Benefits

✅ **Professional tickets** with QR codes for easy verification  
✅ **Email integration** with embedded QR codes  
✅ **PDF download** for offline access  
✅ **Print-friendly** design  
✅ **Mobile-responsive** ticket view  
✅ **Secure** - QR codes contain participant verification data  

## Testing Checklist

- [ ] Register for an event
- [ ] Receive confirmation email with QR code
- [ ] Click "View online" button in email
- [ ] Verify participation details page displays correctly
- [ ] Download PDF ticket
- [ ] Print ticket using browser print
- [ ] Scan QR code to verify data
- [ ] Test on mobile devices
- [ ] Test promotion email with QR code

## Notes

- QR codes are generated on-the-fly for each request
- PDF generation uses absolute URLs for QR code embedding
- All templates follow HR One brand guidelines
- Print styles hide navigation and buttons for clean output
