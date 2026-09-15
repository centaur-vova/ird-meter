# TODO

## Refactoring

- [ ] **Reduce IRD in `src/IrdCalculator.php`** (currently 5.56%)
  - Extract token type checks into a dedicated method (`isSignificantToken`)
  - Use `match` instead of `if-else` chains
  - Apply early return instead of nested `if`
  - Target: IRD < 3%

## Features

- [ ] `--ext=php,inc,phtml` — custom file extensions
- [ ] `--format=table` — alternative output format
- [ ] Compare with previous run (cache in `.ird-cache`)
- [ ] Colored output for non-TTY

## CI

- [ ] Add PHP 8.5 to test matrix (when released)
- [ ] Add code coverage via Codecov
