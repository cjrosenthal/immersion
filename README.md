# Immersion - Language Chat Application

A PHP/MySQL web application for language immersion and practice through conversational AI.

## Features (Phase 1 - Complete)

- User authentication (login, logout, password reset)
- User profile management with photo uploads
- Admin panel for user and settings management
- Activity logging
- CSRF protection
- Session-based authentication
- Responsive design

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd immersion
   ```

2. **Configure local settings**
   ```bash
   cp config.local.php.example config.local.php
   ```
   
   Edit `config.local.php` with your database and SMTP credentials.

3. **Create the database**
   ```bash
   mysql -u root -p
   CREATE DATABASE immersion;
   USE immersion;
   SOURCE schema.sql;
   ```

4. **Set up permissions**
   ```bash
   chmod 755 cache/
   ```

5. **Access the application**
   - Navigate to your web server URL
   - Default admin credentials:
     - Email: `cjrosenthal`
     - Password: `super`

## Configuration

### Database
Edit database settings in `config.local.php`:
```php
'db' => [
    'host' => 'localhost',
    'name' => 'immersion',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'charset' => 'utf8mb4'
]
```

### SMTP (for password reset emails)
```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your_email@gmail.com',
    'password' => 'your_smtp_password',
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Immersion'
]
```

### API Keys (for future phases)
```php
'api' => [
    'claude_key' => 'your_claude_api_key',
    'openai_key' => 'your_openai_api_key'
]
```

## Project Structure

```
immersion/
├── admin/                  # Admin pages
│   ├── users/             # User management
│   ├── threads/           # Thread management (future)
│   └── settings.php       # Global settings
├── cache/                 # Image cache directory
├── css/                   # Stylesheets
├── db_migrations/         # Database migrations
├── includes/              # Header and Footer components
├── js/                    # JavaScript files
├── lib/                   # Core library classes
│   ├── Application.php
│   ├── Database.php
│   ├── UserManagement.php
│   ├── SessionManagement.php
│   ├── ImageManagement.php
│   ├── ActivityLogManagement.php
│   ├── SettingsManagement.php
│   ├── EmailService.php
│   └── CSRF.php
├── config.php             # Base configuration
├── config.local.php       # Local configuration (gitignored)
├── schema.sql             # Complete database schema
├── index.php              # Homepage (chat interface)
├── login.php              # Login page
├── profile.php            # User profile
└── README.md
```

## Security Features

- CSRF token protection on all forms
- Password hashing with bcrypt
- Session-based authentication
- Super password for testing (disable in production)
- Activity logging for all write operations
- Prepared SQL statements (PDO)

## User Roles

### Regular Users
- Access chat interface
- Manage own profile
- View conversation history

### Administrators
- All user permissions
- Create/edit/delete users
- View all threads
- Manage global settings
- Access activity logs

## Next Phases

### Phase 2: Chat Functionality
- Integration with Claude API for conversations
- Thread/message management
- Real-time chat interface
- Conversation history

### Phase 3: Voice Integration
- Speech-to-text (Chrome native API)
- Text-to-speech (OpenAI API)
- Real-time voice conversations
- Audio response handling

### Phase 4: Language Immersion Features
- Spanish practice mode
- Conversation seeding
- Skill tracking and analytics
- Custom prompts for language learning

## Development Notes

### Architectural Patterns
- All database queries are in class methods (not inline in pages)
- Execution only happens through explicit calls (not in includes)
- Header/Footer use static render methods
- Single purpose per file (no branching logic at top level)
- Errors thrown as exceptions from lib classes

### Adding New Pages
1. Start with `Application::init()`
2. Use `Application::requireAuth()` or `Application::requireAdmin()`
3. Include Header and Footer classes
4. Call `Header::render()` and `Footer::render()`

### Database Changes
1. Update `schema.sql` with current structure
2. Create migration in `db_migrations/`
3. Both files should be updated together

## License

Proprietary - All rights reserved

## Support

For issues or questions, please contact the development team.
