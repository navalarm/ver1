# Ver1 - AI Video Generation Platform

AI-powered video generation platform built with Laravel 11, Blade, Tailwind CSS, and Alpine.js.

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Database**: PostgreSQL (production) / SQLite (local development)
- **AI API**: Runware
- **Queue**: Laravel Jobs
- **Auth**: Laravel Breeze

## Features

- User registration and authentication
- Token-based payment system
- AI video generation from images
- Queue-based processing
- Real-time status updates
- Temporary storage for unauthenticated users
- Multiple pricing plans

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- PostgreSQL or SQLite

### Setup Steps

1. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database**

   Edit `.env` file for PostgreSQL:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=ver1
   DB_USERNAME=ver1user
   DB_PASSWORD=ver1pass
   ```

   For SQLite (local development):
   ```env
   DB_CONNECTION=sqlite
   ```

4. **Configure Runware API**

   Add to `.env`:
   ```env
   RUNWARE_API_KEY=your_api_key_here
   RUNWARE_API_URL=https://api.runware.ai/v1
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed --class=PlanSeeder
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

8. **Start the queue worker (in separate terminal)**
   ```bash
   php artisan queue:work
   ```

## Usage

### User Flow

#### Flow 1: Unauthenticated User

1. Visit the homepage and click "Generate Video"
2. Upload an image and enter a prompt
3. Click "Generate"
4. System prompts for login/registration
5. After authentication, user is redirected back with saved data
6. If no tokens, user is prompted to select a plan
7. After purchasing tokens, generation starts automatically

#### Flow 2: Authenticated User

1. Click "Generate Video"
2. Upload image and enter prompt
3. System checks token balance
4. If sufficient tokens, generation starts immediately
5. If no tokens, user is redirected to pricing plans

### Adding Tokens (Testing)

Use the artisan command to add tokens to a user account:

```bash
php artisan tokens:add user@example.com 10
```

### Pages

- **Home** (`/`) - Main landing page with generation modal
- **Login/Register** (`/login`, `/register`) - Authentication pages
- **Generations** (`/generations`) - List of all user generations
- **Generation Details** (`/generations/{hash}`) - View specific generation with status
- **Pricing Plans** (`/plans`) - Available token packages
- **Profile** (`/profile`) - User profile management

## Database Schema

### Tables

- **users** - User accounts
- **user_tokens** - Token balances
- **plans** - Pricing plans
- **generations** - Generation records
- **temporary_uploads** - Temporary storage for unauthenticated users
- **jobs** - Queue jobs
- **cache** - Cache storage
- **sessions** - User sessions

## API Integration

The application integrates with Runware API for video generation through:

- `App\Jobs\ProcessGeneration` - Queue job for processing
- `config/services.php` - API configuration
- Environment variables for API credentials

## Queue System

The application uses Laravel's database queue driver:

Start the queue worker:
```bash
php artisan queue:work
```

For development with auto-reload:
```bash
php artisan queue:listen
```

## Development

### Creating a test user

```bash
php artisan tinker
```

Then:
```php
$user = User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password')]);
```

Add tokens:
```bash
php artisan tokens:add test@example.com 10
```

## Project Structure

```
ver1/
├── app/
│   ├── Console/Commands/
│   │   └── AddTokensCommand.php
│   ├── Http/Controllers/
│   │   ├── GenerationController.php
│   │   ├── HomeController.php
│   │   └── PlanController.php
│   ├── Jobs/
│   │   └── ProcessGeneration.php
│   └── Models/
│       ├── Generation.php
│       ├── Plan.php
│       ├── TemporaryUpload.php
│       ├── User.php
│       └── UserToken.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── PlanSeeder.php
├── resources/
│   └── views/
│       ├── generations/
│       ├── plans/
│       └── home.blade.php
└── routes/
    └── web.php
```

## Payment Integration

Currently uses artisan command for testing. To integrate real payments:

1. Install payment provider SDK
2. Create payment controller
3. Add payment routes
4. Update PlanController
5. Create webhook handlers

## Future Enhancements

- Real payment gateway integration
- User dashboard with analytics
- Video editing features
- Multiple AI model options
- Admin panel
- Email notifications

## License

Proprietary software.
