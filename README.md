# IRD — If/Row Density

> How linear is your code? Number of `if` statements per 100 lines of code.

[![CI](https://github.com/centaur-vova/ird-meter/actions/workflows/ci.yaml/badge.svg)](https://github.com/centaur-vova/ird-meter/actions)
[![Packagist](https://img.shields.io/packagist/v/centaur-vova/ird-meter)](https://packagist.org/packages/centaur-vova/ird-meter)
[![Downloads](https://img.shields.io/packagist/dt/centaur-vova/ird-meter)](https://packagist.org/packages/centaur-vova/ird-meter)
[![PHP](https://img.shields.io/badge/php-8.1%2B-blue)](https://www.php.net/)
[![IRD](https://img.shields.io/badge/IRD-3.50%25-yellow?style=flat)](#what-is-ird)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen)](https://github.com/centaur-vova/ird-meter)

## What is IRD?

**IRD** = number of `if` statements per 100 lines of code.

It shows how **linear** your code is. Fewer `if`s — more predictable flow.

| IRD   | Meaning            |
| ----- | ------------------ |
| < 2%  | 🟢 clean           |
| 2–5%  | 🟡 good            |
| 5–10% | 🟠 needs attention |
| > 10% | 🔴 for ponies      |

## Install

```bash
composer require --dev centaur-vova/ird-meter
```

## Usage

```bash
vendor/bin/ird-meter app
```

## Example

```text
PHP IRD (If/Row Density)
  If count: 111
  Total lines: 5807
  Files: 96
  IRD: 1.91%

  clean — this code is linear like a horse's path
```

With `--ignore-comments`:

```text
PHP IRD (If/Row Density)
  If count: 111
  Total lines: 3826
  Files: 96
  IRD: 2.90%

  good — some branches, but manageable
```

The difference: 1981 lines (34%) are comments and PHPDoc.

## Self-check (Dogfooding)

```text
PHP IRD (If/Row Density)
  If count: 9
  Total lines: 257
  Files: 3
  IRD: 3.50%

  good — some branches, but manageable
```

Yes, our own parser has a few `if`s. It's a parser — it does nothing but check conditions. Irony appreciated.

See [TODO.md](TODO.md) — we're working on reducing it. PRs welcome.

## Flags

| Flag                | Description                                                                            |
| ------------------- | -------------------------------------------------------------------------------------- |
| `--raw`             | Output only the IRD number                                                             |
| `--json`            | Output JSON                                                                            |
| `--threshold=N`     | Exit 1 if IRD > N (for CI)                                                             |
| `--exclude=A,B`     | Comma-separated directories to exclude (default: `vendor,.git,node_modules,var/cache`) |
| `--ignore-comments` | Count only non-comment, non-empty lines                                                |
| `--include-elseif`  | Count `elseif` branches too                                                            |
| `--include-ternary` | Count ternary operators                                                                |
| `--include-match`   | Count `match` expressions                                                              |
| `--all`             | Enable all optional counters                                                           |
| `--help, -h`        | Show this help                                                                         |

## CI Integration

```yaml
- name: Check IRD
  run: vendor/bin/ird-meter app --threshold=5
```

If IRD exceeds threshold — exit code 1, CI fails.

## Why IRD?

Because `if` is the source of all evil:

- Each `if` is a branch
- Each branch is a potential bug
- Fewer branches — fewer bugs

Based on `nikic/php-parser` — accurate AST analysis, no regex, no false positives from comments or strings.

## Requirements

- PHP 8.1+
- `nikic/php-parser` 5.x

## License

MIT. Or KBL v4.0 — horses don't abandon horses, and ponies 🦄
