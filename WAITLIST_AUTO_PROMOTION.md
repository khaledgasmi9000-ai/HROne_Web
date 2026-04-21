# Automatic Waitlist Promotion Feature

## Overview
This feature automatically promotes people from the waiting list to fill empty spots in events. When there are available places and people waiting, they are automatically moved to confirmed participants.

## How It Works

### Automatic Promotion Triggers

1. **When viewing inscriptions page** (`/rh/evenements/{id}/inscriptions`)
   - System checks if there are empty spots
   - Automatically promotes people from waiting list
   - Shows success message with number of people promoted

2. **When deleting a participant**
   - After removing a participant, a spot becomes available
   - System automatically promotes the next person from waiting list
   - Sends promotion email to the newly confirmed participant

### Promotion Logic

The `WaitlistPromotionService` handles the automatic promotion:

```php
1. Check if event has maximum capacity (nbMax)
2. Count current participants
3. Calculate available spots = nbMax - current participants
4. Get waiting list ordered by date (oldest first)
5. For each available spot:
   - Create shadow user
   - Create participation record
   - Assign activity from waiting list
   - Send promotion email
   - Remove from waiting list
```

### Priority Order
People are promoted in **FIFO (First In, First Out)** order based on `dateDemande` (request date).

## Example Scenario

**Event: "JPO"**
- Maximum capacity: 50 people
- Current participants: 48
- Waiting list: 5 people

**What happens:**
1. RH opens inscriptions page
2. System detects 2 empty spots (50 - 48 = 2)
3. Automatically promotes first 2 people from waiting list
4. Sends promotion emails to both
5. Shows message: "✅ 2 personne(s) de la liste d'attente ont été automatiquement inscrites !"
6. Remaining waiting list: 3 people

## Files Created/Modified

### New Service
- `src/Service/WaitlistPromotionService.php` - Handles automatic promotion logic

### Modified Controller
- `src/Controller/EvenementController.php`
  - `inscriptions()` method - Calls promotion service on page load
  - `deleteParticipation()` method - Calls promotion service after deletion

## Benefits

✅ **Automatic filling** - No manual intervention needed  
✅ **Fair system** - FIFO order ensures fairness  
✅ **Real-time** - Happens immediately when spots become available  
✅ **Email notifications** - Promoted people receive confirmation emails  
✅ **Efficient** - Fills multiple spots at once if available  
✅ **Error handling** - Continues even if one promotion fails  

## User Experience

### For RH Staff
- Open inscriptions page → See automatic promotions
- Delete participant → See automatic backfill
- Clear feedback messages showing how many were promoted

### For Participants
- Receive promotion email when moved from waiting list
- Can view/print ticket immediately
- No action required on their part

## Technical Details

### Service Dependencies
```php
WaitlistPromotionService(
    EntityManagerInterface $em,
    EmailService $emailService,
    ShadowUserService $shadowUserService
)
```

### Return Value
The service returns the number of people promoted (integer), which is used to display appropriate success messages.

### Error Handling
- Continues processing even if one promotion fails
- Email failures don't block the promotion
- Logs errors but doesn't stop the process

## Testing Checklist

- [ ] Create event with max capacity (e.g., 5 people)
- [ ] Fill event to capacity
- [ ] Add people to waiting list
- [ ] Open inscriptions page → Verify no promotion (event full)
- [ ] Delete one participant
- [ ] Verify first person from waiting list is automatically promoted
- [ ] Check promotion email was sent
- [ ] Verify waiting list count decreased
- [ ] Add more to waiting list
- [ ] Delete multiple participants
- [ ] Verify multiple promotions happen automatically
- [ ] Check all promotion emails sent

## Notes

- Promotion only happens for events with `nbMax` set (limited capacity)
- Events without capacity limits don't use waiting lists
- Promotion respects the activity selected by the waiting person
- If activity not found, uses first activity of the event
