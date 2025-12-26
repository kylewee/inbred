# Agent Instructions

## Build & Test Commands
- **Go Test All:** `make test` (in `backend/` dir)
- **Go Single Test:** `go test ./internal/pkg -run TestName`
- **PHP Syntax Check:** `php -l <filename>`
- **System Workflow Test:** `php test_workflow.php`

## Code Style & Guidelines
- **Configuration:** NEVER delete config lines. Comment them out (`//` or `#`).
- **PHP:** Use `snake_case` for functions, `camelCase` for variables.
- **Go:** Follow standard `gofmt` and idiomatic Go practices.
- **Documentation:** You MUST update `MASTER_CONFIG_DOCUMENT.md` if you change any configuration.

## Key References
- **`CLAUDE.md`**: Primary source of truth for architecture, deployment, and logs.
- **`backend/Makefile`**: Reference for Go-specific build tasks.
