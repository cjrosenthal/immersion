# Immersion - Language Chat Application

## Project Vision

Build a web-based language immersion application that helps people practice Spanish through conversational AI. The application will feature both text and voice interactions, with intelligent conversation management and skill tracking.

---

## Current Status

**Phase 1: COMPLETE** ✅
- User authentication and authorization system
- Profile management with photo uploads
- Admin panel for user and settings management
- Basic chat interface UI
- All architectural foundations in place

---

## Feature Roadmap

### Phase 1: Authentication & Site Foundation ✅ COMPLETE

**Core Features:**
1. User authentication (login, logout, password reset via email)
2. User profile management including profile photo uploads
3. Admin panel for managing users and global settings
4. Activity logging for all write operations
5. CSRF protection on all forms
6. Session-based authentication
7. Basic chat interface UI ("Let's chat.")

**User Types:**
- **Regular Users:** Can access chat, manage their profile
- **Admins:** All user permissions + manage users, view threads, edit global settings

**Key User Flows:**
1. Login → redirects to login page if not authenticated
2. Forgot password → generates email with reset token
3. View homepage → ChatGPT-like interface with "Let's chat." title
4. Logout

**Key Admin Flows:**
1. All user flows
2. Manage users (list, create, edit)
3. View threads (list, view individual threads)
4. Global settings page (currently: site title)

---

### Phase 2: Chat Functionality (NEXT)

**Typing Questions and Receiving Answers:**
- Users type questions and hit submit
- Questions submitted to Claude's API
- Responses displayed in chat interface
- Users can continue threads with follow-up prompts
- Conversation stored as threads
- Earlier parts of thread sent for context to Claude
- Threads stored by user
- Thread names based on first few words of first query

**Technical Requirements:**
- Integration with Claude API
- Thread and message management
- Real-time chat interface updates
- Conversation history and retrieval
- Context management for multi-turn conversations

---

### Phase 3: Voice Integration

**Voice Input:**
- "Use Voice" button allows user to speak questions
- Uses **Chrome native speech-to-text API** (streaming)
- Text sent as it is spoken (not waiting for user to finish)
- Best practices to minimize wait time after user finishes speaking

**Voice Output:**
- Responses converted to speech using **OpenAI text-to-speech API**
- Audio played back to user

**Interruption Handling:**
- User can interrupt response to ask another question
- Partial response that was spoken stored for context
- Exact context of what was said passed to agent for next response

**Design Considerations:**
- Process speech as user speaks (when they pause)
- No button press required to process ongoing speech
- Streaming approach for real-time feedback

---

### Phase 4: Language Immersion Features

**Spanish Practice Mode:**
- Seed conversations with practice topics
- Prompt engineering to guide conversation style
- Structured practice sessions

**Skill Tracking:**
- Analyze what skills user has practiced
- Track progress over time
- Identify areas for improvement

**Custom Conversation Seeding:**
- Predefined topics and scenarios
- Difficulty levels
- Grammar and vocabulary focus areas

---

## Architectural Principles

### Code Organization

**SQL Queries:**
- SQL queries ONLY in class methods, never directly in PHP files
- Either add to existing class or create new class
- No inline database queries in page files

**File Execution:**
- No silent execution in include files
- Execution only through explicit calls (e.g., `Application::init()`)
- No branching logic at top of files that handles form evaluation
- Separate files for different purposes:
  - Form display in File 1
  - Form processing in File 2
  - AJAX endpoints in File 3

**Header/Footer Pattern:**
- Create `Header` and `Footer` classes
- Call explicitly: `Header::render()` and `Footer::render()`
- No inline code execution in includes

**Single Purpose Per File:**
- Each file has ONE clear purpose
- Avoid if-branches at top that handle multiple scenarios
- Forms submit to separate processing files

**GET vs POST:**
- GET for requests that don't modify data
- POST for requests that modify persistent database data
- Traffic logging/disk logging doesn't count as "modification"

---

### Database Management

**Schema Documentation:**
- `schema.sql` - Complete current database structure
- Must be kept up-to-date with ALL changes
- Should work standalone without requiring migrations

**Migrations:**
- Located in `db_migrations/` directory
- Help upgrade production installations
- Both schema.sql AND migration must be updated together

**Key Principle:**
> When making database changes, ALWAYS update schema.sql to reflect current state AND create a migration file.

---

### Error Handling

**Library Classes:**
- Errors thrown as exceptions
- High-level callers catch and decide what to do
- Never swallow errors - pass error messages to user

**Page-Level Handling:**
- Redirect to appropriate page with error message
- For AJAX: send error back for display in correct location
- Error messages must be shown, not hidden

---

### Security

**CSRF Protection:**
- Forms protected with CSRF tokens
- Use `CSRF::getTokenField()` in forms
- Validate in processing files

**Passwords:**
- Minimum 4 characters (by user preference)
- No other restrictions
- Hashed with bcrypt

**Super Password:**
- Testing feature: allows login as anyone
- Intended to disable in production
- Configured in `config.local.php`

**Activity Logging:**
- Log all write actions
- Log all logins
- Store user_id, action, details, IP address, timestamp

---

### Image Handling

**Storage:**
- `images` table stores blobs by id
- Profile photos reference `image_id`
- Binary data stored in database

**Rendering:**
- `render_image.php?id=X` serves images
- Images cached to filesystem in `cache/` directory
- When rendering links, check cache first:
  - If cached: use `/cache/image_X.ext`
  - If not cached: use `/render_image.php?id=X`
- Image tag written with cached path or render_image.php
- render_image.php does NOT check cache (pre-checked)

---

### Modal Dialog Pattern

When implementing modals requiring server-side processing:

1. **Modal UI:** Include in main page via UI manager classes
   - Provides consistent modal experience across pages
   - Example: `EventUIManager`

2. **AJAX Endpoints:** Create dedicated PHP endpoints
   - Return JSON responses
   - Examples: `admin_event_emails.php`, `event_attendees_export.php`
   - Contain only server-side logic for modal
   - No full page rendering

3. **JavaScript Integration:**
   - Modal JS makes AJAX calls to dedicated endpoints
   - Handles success/error responses
   - Updates modal content
   - Provides user feedback
   - Direct page links still work without modals

**Separation:** Keep modal logic separate from page-specific logic for maintainability.

---

## Technical Stack

### Backend
- **Language:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Email:** Native PHP `mail()` function
- **Sessions:** PHP native file-based sessions

### Frontend
- **HTML/CSS:** Custom CSS (no frameworks to avoid bloat)
- **JavaScript:** Vanilla JS
- **UI Style:** ChatGPT-inspired interface

### APIs (Future Phases)
- **Conversation AI:** Claude API
- **Speech-to-Text:** Chrome native Web Speech API
- **Text-to-Speech:** OpenAI API

### Configuration
- `config.php` - Base config (checked into git)
- `config.local.php` - Local overrides (gitignored)
- Contains: DB credentials, SMTP settings, API keys, super password

---

## Data Model

See schema.sql

## Naming Conventions

### General Principle
> Function and method names should express intent. Always suggest better names if you have them.

### Class Naming
- Data entity management classes use "Management" suffix
  - `UserManagement` (not `User`)
  - `ImageManagement` (not `Image`)
  - `ActivityLogManagement`
  - `SettingsManagement`

### Method Naming
- Be descriptive and clear
- Indicate what the method does
- Examples:
  - `getUserById()` - retrieves user
  - `createUser()` - creates new user
  - `validatePasswordResetToken()` - checks token validity

---

## Quality Standards

### Code Review Checklist

When changing a file, verify:
- ✅ Called functions actually exist
- ✅ Function signatures match what you're calling
- ✅ Data-fetching functions are verified (common issue area)
- ✅ File parses correctly (no syntax errors)
- ✅ Consider implications of changes on other parts of codebase
- ✅ Use search to find all call sites if refactoring

### Thoroughness
- Ensure functions exist before calling
- Verify signatures match expectations
- Check for syntax errors
- Consider cascade effects of changes

---

## Page Template Pattern

Every page should follow this pattern:

```php
<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth(); // or requireAdmin()

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

// Page logic here

Header::render('Page Title');
?>

<!-- Page HTML content -->

<?php
Footer::render();
?>
```

---

## API Key Configuration

Located in `config.local.php`:

```php
'api' => [
    'claude_key' => 'your_claude_api_key',
    'openai_key' => 'your_openai_api_key'
]
```

**Note:** These will be needed for Phase 2 (Claude) and Phase 3 (OpenAI TTS).


*Last Updated: March 7, 2026*
*Current Phase: Phase 1 Complete ✅*
