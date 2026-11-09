# Congkak

The game rules are at [docs/RULES.md](docs/RULES.md) (in Portuguese). The original manual was lost, so it was rebuilt on 2026-11-09 from the code and from public sources, and **it is not yet reviewed by André**: section 12 lists what he has to confirm.

**Mandatory rule:** before implementing, investigating, or fixing anything related to game rules (sowing path, the four endings of a sowing, the simultaneous opening, capture, rounds, burnt houses, victory modes), read docs/RULES.md. Never assume correct behavior from the source code alone. Where RULES.md says a point is a **[Decisão]**, it is a deliberate default (for example the clockwise direction), not a bug. If a case is not covered, decide with André and record it in docs/PLAN.md with the date.

The art (board, seeds, background) is described in [docs/ASSETS.md](docs/ASSETS.md). The work plan, the gaps against the newer games and the things never tried on Studio are in [docs/PLAN.md](docs/PLAN.md).

2 players only. Seven houses ("kampong") and one storehouse ("rumah") each, 7 seeds per house, 98 seeds. The first round opens with both players sowing at the same time.

## Older scaffold

This game predates the newer ones (trucomineiro, trucopaulista, buraco): its game states are classes in `modules/php/States/`, but it still has a `Game` class extending `\Table`, plain `.json` config files and a `tsc` and `sass` build with yarn. Do not copy the structure of the newer games here without asking, and do not migrate it on your own: the phased plan for that is in [docs/MIGRATION.md](docs/MIGRATION.md) (inventory in docs/PLAN.md).

## Build

After editing any file under `src/`, recompile before considering the change done:

```
yarn build
```

`congkak.js` and `congkak.css` (in the project root) are build outputs, edit the `src/` sources instead.

## Code map

- `modules/php/Game.php`: thin entry point, delegates to the services.
- `modules/php/services/`: `SeedingService` (sowing, the four endings, the opening), `StepService` (rounds, who plays next, reseeding), `HouseService` (houses, path, burnt houses), `PlayerService` (opponent, score, round wins), `OptionsService` (table options), `StatsService`.
- `modules/php/States/`: one class per state (`NewRound`, `PlayersSeeding`, `PlayerSeeding`, `NextMultiplayers`, `NextPlayer`), they only delegate to the services.
- `modules/php/Rules/`: the pure rules (see Rules and tests).
- `modules/php/entities/`: `GameMode`, `VictoryMode`, `Messages` (all log strings).
- `src/states/sowing.ts`: selection and animations. `src/congkak.ts`: setup, the game area HTML (no `.tpl`) and the houses.

## Rules and tests

The pure rules (sowing path, the four endings of a sowing, the simultaneous opening, reseeding, round and game end, scoring) live in `modules/php/Rules/`, with no framework and no database. Each has a plain-PHP test in `tests/` (shared helpers in `tests/Check.php`); after changing a rule, run them all:

```
for t in tests/*Test.php; do php "$t" || exit 1; done
```

The services in `modules/php/services/` only read the database, save, notify and answer the states. A change to a rule goes in `Rules/` with a test, and its section in docs/RULES.md changes in the same commit.

## Secrets

`.vscode/sftp.json` holds the BGA Studio SFTP password. It is gitignored and must never be committed.
