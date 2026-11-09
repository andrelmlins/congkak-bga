# Congkak

The game rules are at [docs/RULES.md](docs/RULES.md) (in Portuguese). The original manual was lost, so it was rebuilt on 2026-11-09 from the code and from public sources, and **it is not yet reviewed by André**: section 12 lists what he has to confirm.

**Mandatory rule:** before implementing, investigating, or fixing anything related to game rules (sowing path, the four endings of a sowing, the simultaneous opening, capture, rounds, burnt houses, victory modes), read docs/RULES.md. Never assume correct behavior from the source code alone. Where RULES.md says a point is a **[Decisão]**, it is a deliberate default (for example the clockwise direction), not a bug. If a case is not covered, decide with André and record it in docs/PLAN.md with the date.

The art (board, seeds, background) is described in [docs/ASSETS.md](docs/ASSETS.md). The work plan, the gaps against the newer games and the things never tried on Studio are in [docs/PLAN.md](docs/PLAN.md).

2 players only. Seven houses ("kampong") and one storehouse ("rumah") each, 7 seeds per house, 98 seeds. The first round opens with both players sowing at the same time.

## Older scaffold

This game predates the newer ones (trucomineiro, trucopaulista, buraco): it still uses `states.inc.php`, a `Game` class extending `\Table`, plain `.json` config files and a `tsc` and `sass` build with yarn. Do not copy the structure of the newer games here without asking, and do not migrate it on your own: the plan for that is in docs/PLAN.md.

## Build

After editing any file under `src/`, recompile before considering the change done:

```
yarn build
```

`congkak.js` and `congkak.css` (in the project root) are build outputs, edit the `src/` sources instead.

## Code map

- `modules/php/Game.php`: thin entry point, delegates to the services.
- `modules/php/services/`: `SeedingService` (sowing, the four endings, the opening), `StepService` (rounds, who plays next, reseeding), `HouseService` (houses, path, burnt houses), `PlayerService` (opponent, score, round wins), `OptionsService` (table options), `StatsService`, `ZombieService`.
- `modules/php/entities/`: `GameMode`, `VictoryMode`, `Messages` (all log strings).
- `src/states/sowing.ts`: selection and animations. `src/congkak.ts`: setup and the houses.

## Secrets

`.vscode/sftp.json` holds the BGA Studio SFTP password. It is gitignored and must never be committed.
