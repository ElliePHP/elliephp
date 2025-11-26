# Examples

This directory contains working examples of ElliePHP Console commands.

## Running Examples

```bash
# List all available commands
php examples/console.php list

# Run the greet command
php examples/console.php greet
php examples/console.php greet "Your Name"

# Run the advanced command
php examples/console.php advanced process --verbose --count=5
```

## Example Commands

### GreetCommand

A simple command demonstrating:
- Optional arguments
- Basic output methods (`success()`, `info()`, `note()`)

### AdvancedCommand

A more complex command demonstrating:
- Required arguments
- Options (flags and values)
- Title and section output
- Table display
- Interactive confirmation

## Try It Out

```bash
# See all commands
php examples/console.php list

# Get help for a command
php examples/console.php greet --help
php examples/console.php advanced --help

# Run commands
php examples/console.php greet "ElliePHP"
php examples/console.php advanced demo --verbose
```

