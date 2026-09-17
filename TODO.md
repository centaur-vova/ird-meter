# TODO

## Refactoring

- [x] **Extract `BranchCounterBuilder`** — immutable, fluent, declarative
  - Separated counting logic from `IrdCalculator`
  - Added 10 unit tests
  - Covered `elseif` vs `else if` AST difference

- [ ] **Reduce IRD in `src/`** (currently 3.50%)
  - [ ] Target: IRD < 3%

## Features

- [ ] `--ext=php,inc,phtml` — custom file extensions
- [ ] `--format=table` — alternative output format
- [ ] Compare with previous run (cache in `.ird-cache`)
- [ ] Colored output for non-TTY

## CI

- [x] Add PHP 8.5 to test matrix
- [ ] Add PHP 8.6 to test matrix (target GA: November 19, 2026)
- [ ] Add code coverage via Codecov
