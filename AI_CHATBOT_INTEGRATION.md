# AI Chatbot Integration - HROne Assistant

## Overview
An intelligent AI chatbot assistant that helps employees navigate the HROne platform, find events, understand the registration process, and get instant answers to their questions.

## Features

### 🤖 Smart Keyword Detection
The chatbot uses intelligent keyword matching to understand user intent and provide relevant responses.

### 📊 Real-Time Database Access
The assistant can query the actual event database to provide up-to-date information about available events.

### 💬 Natural Conversation
Supports multiple conversation topics:
- Greetings and introductions
- Platform information
- Event listings (from database)
- Registration workflow guidance
- PDF ticket information
- Waiting list explanations
- Politeness and thanks

### 🎯 Context-Aware Responses
The chatbot provides different responses based on the context and can handle various phrasings of the same question.

## Files Created

### Backend
1. **`src/Controller/ChatbotController.php`**
   - API endpoint: `POST /api/chatbot`
   - Receives user messages
   - Returns AI-generated responses

2. **`src/Service/GeminiService.php`**
   - Core AI logic
   - Keyword matching engine
   - Database integration for events
   - Response generation

### Frontend
3. **`templates/base_front.html.twig`** (modified)
   - Chatbot bubble (floating button)
   - Chat window interface
   - JavaScript for message handling
   - Styling for chat UI

## How It Works

### User Flow
1. User clicks the floating chatbot bubble (🤖) in bottom-right corner
2. Chat window opens with welcome message
3. User types a question
4. Message sent to `/api/chatbot` endpoint
5. GeminiService processes the message
6. AI response displayed in chat window

### AI Logic Flow
```
User Message
    ↓
Normalize & Clean (lowercase, trim)
    ↓
Keyword Matching
    ↓
├─ Greeting? → Random friendly response
├─ Platform info? → Explain HROne
├─ Events? → Query database & list events
├─ Registration? → Step-by-step guide
├─ PDF/Ticket? → Explain ticket system
├─ Waiting list? → Explain promotion system
└─ Default → Generic helpful response
```

## Supported Queries

### Greetings
- "Bonjour", "Hello", "Salut", "Hi", "Bonsoir"
- **Response**: Friendly greeting with offer to help

### Platform Information
- "C'est quoi HROne?", "Qui es-tu?", "Présentation"
- **Response**: Explanation of HROne platform and purpose

### Event Listings
- "Événement", "Event", "Activité", "Quoi faire", "Planning"
- **Response**: Lists top 3 recent events from database with dates

### Registration Help
- "Inscription", "Participer", "Comment", "Aide", "Étapes"
- **Response**: 5-step registration workflow guide

### Ticket Information
- "PDF", "Ticket", "Mail", "Email", "Imprimer", "Confirmation"
- **Response**: Explains PDF ticket generation and email delivery

### Waiting List
- "Liste d'attente", "Complet", "Attente", "Promotion"
- **Response**: Explains automatic promotion system

### Thanks
- "Merci", "OK", "Cool", "Parfait", "Top"
- **Response**: Polite acknowledgment

## Technical Details

### API Endpoint
```
POST /api/chatbot
Content-Type: application/json

Request:
{
    "message": "Bonjour"
}

Response:
{
    "response": "Bonjour ! Je suis votre assistant HROne..."
}
```

### Database Integration
The chatbot queries the `evenement` table to provide real-time event information:
```php
$events = $this->evenementRepository->findBy(
    [], 
    ['ID_Evenement' => 'DESC'], 
    3  // Limit to 3 most recent
);
```

### Keyword Matching
Uses flexible string matching:
```php
private function match(string $msg, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if (str_contains($msg, $keyword)) {
            return true;
        }
    }
    return false;
}
```

## UI Components

### Chatbot Bubble
- Fixed position: bottom-right corner
- Blue gradient background
- Robot emoji icon (🤖)
- Hover animation (scale effect)
- Click to toggle chat window

### Chat Window
- 350px × 500px
- Fixed position above bubble
- Blue header with close button
- Scrollable message area
- Input field with send button
- Enter key support

### Message Styling
- **Bot messages**: White background, left-aligned
- **User messages**: Blue background, right-aligned
- Rounded corners
- Max width 80%
- Word wrap enabled

## Customization

### Adding New Topics
To add new conversation topics, edit `GeminiService.php`:

```php
// Add new keyword detection
if ($this->match($msg, ['your', 'keywords', 'here'])) {
    return "Your custom response here";
}
```

### Modifying Responses
Edit the response strings in `GeminiService.php` to customize the chatbot's personality and information.

### Styling Changes
Modify the `<style>` block in `base_front.html.twig` to change:
- Bubble size and position
- Window dimensions
- Colors and gradients
- Message appearance

## Benefits

✅ **24/7 Availability** - Always ready to help users  
✅ **Instant Responses** - No waiting for human support  
✅ **Database Integration** - Real-time event information  
✅ **Offline Capable** - No external API dependencies  
✅ **Multilingual Ready** - Easy to add more languages  
✅ **Scalable** - Can add more topics easily  
✅ **User-Friendly** - Simple, intuitive interface  

## Future Enhancements

Potential improvements:
- [ ] Add conversation history persistence
- [ ] Implement user authentication awareness
- [ ] Add formation (training) information
- [ ] Support for file attachments
- [ ] Voice input/output
- [ ] Multi-language support
- [ ] Analytics and usage tracking
- [ ] Integration with external AI APIs (optional)

## Testing Checklist

- [ ] Click chatbot bubble → Window opens
- [ ] Type "Bonjour" → Receives greeting
- [ ] Ask "C'est quoi HROne?" → Gets platform info
- [ ] Ask "Événements" → Lists events from database
- [ ] Ask "Comment s'inscrire?" → Gets step-by-step guide
- [ ] Ask "PDF ticket" → Gets ticket information
- [ ] Ask "Liste d'attente" → Gets waiting list explanation
- [ ] Type "Merci" → Gets polite response
- [ ] Press Enter key → Sends message
- [ ] Click X button → Window closes
- [ ] Test on mobile devices
- [ ] Verify scrolling in message area

## Notes

- The chatbot is visible on all employee pages (uses `base_front.html.twig`)
- No external API keys required (fully offline)
- Responses are in French (can be translated)
- Uses keyword matching (not true AI/ML)
- Can be extended with real AI APIs if needed (Gemini, GPT, etc.)


## Visibility

### ⚠️ Important: Chatbot Location
The chatbot is **ONLY visible on événements pages**:
- ✅ `/evenements` - Event listing page
- ✅ `/evenements/{id}` - Event detail page

The chatbot is **NOT visible** on other pages:
- ❌ Communauté page
- ❌ Formations page  
- ❌ Participations page
- ❌ Demande de congés page

### Implementation
The chatbot is included via `{% include 'partials/chatbot.html.twig' %}` in:
- `templates/Topnavbar/evenements.html.twig`
- `templates/Topnavbar/evenement/show.html.twig`

This ensures the chatbot only appears where it's relevant (event-related pages).
