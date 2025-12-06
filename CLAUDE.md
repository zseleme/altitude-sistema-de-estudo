# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Altitude Sistema de Estudo** is a Learning Management System (LMS) built with vanilla PHP, supporting both PostgreSQL and SQLite databases. The system includes course management, video lessons, quiz/exam simulations with AI analysis, English study tools, and certificate generation.

## Development Commands

### Database Setup

**First-time installation is automatic** - the system uses auto-installation via `includes/auto_install.php`. On first access, it creates SQLite database and all tables automatically.

Manual database setup (if needed):
```bash
# PostgreSQL (production)
php setup_postgres.php

# Test PHP syntax before deploy
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
```

### Deployment

Deployment is **fully automated via GitHub Actions**:
- Push to `main` → deploys to production (seleme.pt)
- Push to `develop` → deploys to staging (dev.seleme.pt)

The workflow validates PHP syntax, generates version.json, and deploys via FTP.

### Running the Application

This is a vanilla PHP application - no build step required. Simply serve with PHP's built-in server or Apache/Nginx:

```bash
php -S localhost:8000
```

Default admin credentials (auto-created):
- Email: `admin@teste.com`
- Password: `admin123`

## Architecture

### Database Abstraction Layer

**Critical**: The system supports **both PostgreSQL and SQLite** via a custom abstraction layer in `config/database.php`. The `Database` singleton class handles cross-database compatibility:

```php
$db = Database::getInstance();

// Check database type
if ($db->isSQLite()) {
    // SQLite-specific SQL
} else {
    // PostgreSQL-specific SQL
}

// Helper methods for compatibility
$db->getBoolTrue()   // Returns TRUE or 1
$db->getBoolFalse()  // Returns FALSE or 0
```

**Key differences to handle**:
- **Auto-increment**: `SERIAL` (PostgreSQL) vs `INTEGER PRIMARY KEY AUTOINCREMENT` (SQLite)
- **Booleans**: `BOOLEAN` (PostgreSQL) vs `INTEGER` (SQLite)
- **Timestamps**: `TIMESTAMP` (PostgreSQL) vs `DATETIME` (SQLite)
- **Unique constraints**: Use `ON CONFLICT` carefully (syntax differs)

**Common pitfall**: Never use MySQL-specific syntax like `ON DUPLICATE KEY UPDATE`. Use manual check-then-update/insert pattern instead (see `api/simulados.php:133-190` for example).

### Auto-Installation System

The `includes/auto_install.php` file is executed on every page load via `includes/auth.php`. It:

1. Creates `config/database.php` from `config/database.example.php` if missing
2. Creates SQLite database with all tables if missing
3. Inserts initial data (admin user, categories, sample courses, AI configurations)

**All database schema is defined in `includes/auto_install.php`**. There is no separate migrations folder. When adding new tables:
1. Add table creation in `createTables()` function
2. Add initial data (if needed) in `insertInitialData()` function
3. Ensure SQL works for both PostgreSQL and SQLite

### AI Integration Architecture

The system integrates with multiple AI providers through `includes/ai_helper.php`:

**Supported providers**:
- OpenAI (GPT-4o-mini)
- Google Gemini (gemini-2.5-flash) - default
- Groq (llama-3.1-8b-instant)

**Configuration storage**: All AI settings stored in `configuracoes` table, **not** in files. Managed via admin panel at `admin/configuracoes_ia.php`.

**API versions**:
- **Gemini**: Uses v1 (stable), NOT v1beta
- **Model names**: Use current model names (e.g., `gemini-2.5-flash`, not `gemini-1.5-flash`)

**Usage pattern**:
```php
if (!AIHelper::isConfigured()) {
    // Handle AI not configured
}

$ai = new AIHelper();
$result = $ai->analyzeQuestion($prompt);
// or
$result = $ai->generateContent($systemPrompt, $userPrompt);
```

**Token limits**: Default 4000, increased to 8000 for question analysis (`analyzeQuestion()` method).

### Page Structure & Authentication

**File organization**:
- Root level: Student-facing pages (`home.php`, `curso.php`, `aula.php`, etc.)
- `admin/`: Admin-only pages (require `requireAdmin()`)
- `api/`: JSON API endpoints (AJAX calls)
- `includes/`: Shared utilities, auth, helpers
- `ingles/`: English study section

**Authentication pattern**: All protected pages use:
```php
require_once 'includes/auth.php';
requireLogin();    // For any authenticated user
requireAdmin();    // For admin-only pages
```

Session variables available:
- `$_SESSION['user_id']`
- `$_SESSION['user_name']`
- `$_SESSION['is_admin']`

### Layout System

Centralized layout in `includes/layout.php` with two modes:

**Full layout** (sidebar + content):
```php
require_once 'includes/layout.php';
renderLayout('Page Title', function() {
    // Page content here
});
```

**Simple layout** (no sidebar):
```php
renderSimpleLayout('Page Title', function() {
    // Page content here
});
```

The layout includes:
- Tailwind CSS (via CDN)
- Font Awesome icons
- Responsive sidebar navigation
- Version info in footer (`includes/version.php`)

### API Endpoints Pattern

All API files in `api/` folder follow this structure:

```php
<?php
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        // Handle create
        break;
    case 'update':
        // Handle update
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
```

**Key API files**:
- `api/simulados.php`: Quiz/exam operations (create, answer, finalize)
- `api/analise_questao_ia.php`: AI-powered question analysis
- `api/progresso.php`: Course progress tracking
- `api/anotacoes.php`: Student notes
- `api/importar_playlist_youtube.php`: YouTube playlist import

### Quiz/Simulado System

Complex multi-table system for exams:

**Tables**:
- `simulados`: Exam metadata
- `simulado_questoes`: Questions (a/b/c/d/e alternatives)
- `simulado_tentativas`: User attempts (tracks score, completion)
- `simulado_respostas`: Individual answers (with AI analysis)

**Flow**:
1. Student starts exam → creates `tentativa` record
2. For each question → saves answer to `respostas` table
3. AI analysis triggered on wrong answers → stores in `analise_ia` field
4. Finalize exam → updates `tentativa` with final score

**Important**: Answers use SQLite-compatible INSERT/UPDATE pattern (see `api/simulados.php:133-190`).

### Version Tracking

`version.json` file is auto-generated by GitHub Actions during deployment. It contains:
- Version (timestamp)
- Git commit hash
- Branch name
- Environment (Produção/Desenvolvimento)

**Detection logic** (`includes/version.php`):
1. If `version.json` exists → deployed environment (reads environment from file)
2. Else if `.git/` exists → local dev environment ("Dev")
3. Else → fallback ("Local")

**Display**: Version shown in sidebar footer with color coding (green=prod, yellow=dev, gray=local).

## Important Patterns & Conventions

### SQL Compatibility

When writing queries that work for both databases:

```php
// ✅ GOOD: Compatible query
$query = "
    CREATE TABLE IF NOT EXISTS example (
        id INTEGER PRIMARY KEY " . ($db->isPostgreSQL() ? "SERIAL" : "AUTOINCREMENT") . ",
        active INTEGER DEFAULT " . ($db->isSQLite() ? "1" : "TRUE") . "
    )
";

// ❌ BAD: MySQL-specific syntax
$query = "INSERT INTO ... ON DUPLICATE KEY UPDATE ...";

// ✅ GOOD: Manual check pattern
$existing = $db->fetchOne("SELECT id FROM table WHERE key = ?", [$key]);
if ($existing) {
    $db->execute("UPDATE table SET ...");
} else {
    $db->execute("INSERT INTO table ...");
}
```

### Security

The codebase follows these security practices:
- All queries use prepared statements (PDO)
- Passwords hashed with `password_hash()`
- HTML escaped with `htmlspecialchars()`
- Session-based authentication
- No raw SQL concatenation

### AI Prompts

When modifying AI analysis features (`api/analise_questao_ia.php`):
- Keep prompts concise (max 200 words for optimal token usage)
- System prompt defines persona ("professor de cursinho experiente")
- User prompt includes question context, answer, and specific instructions
- Response format: Markdown with sections (Análise, Por que errou, etc.)

### Deployment & Environment

**Git branches**:
- `main`: Production (seleme.pt)
- `develop`: Staging (dev.seleme.pt)

**Files ignored in deployment** (`.github/workflows/ftp-deploy.yml`):
- `.git*`, `.github/`
- `node_modules/`, `vendor/`
- `.env`, `config/database.php`, `config/estudos.db`
- Test files, markdown docs

**Version file**: Created at project root during deploy, excluded from git (in `.gitignore`).

## Common Gotchas

1. **Database type detection**: Always check `$db->isSQLite()` when using DB-specific features
2. **Boolean values**: Use `$db->getBoolTrue()` instead of hardcoding `TRUE` or `1`
3. **Gemini API**: Use v1 (stable), not v1beta; model names change frequently
4. **Auto-install**: Don't create separate migration files; update `includes/auto_install.php`
5. **AI configuration**: Stored in database (`configuracoes` table), not in PHP config files
6. **Table names**: English study tables use `ingles_*` (Portuguese), not `english_*`

## Key Files Reference

- `includes/auto_install.php`: Complete database schema (407 lines)
- `includes/ai_helper.php`: AI provider abstraction
- `config/database.php`: Database singleton with compatibility layer
- `includes/auth.php`: Authentication functions
- `includes/layout.php`: Page layout renderer
- `api/simulados.php`: Quiz system API
- `api/analise_questao_ia.php`: AI question analysis
