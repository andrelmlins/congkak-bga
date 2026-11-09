/**
 * Shared constants of the client, not tied to one manager. Import them from here instead of redeclaring them. The game data (houses, rounds) mirrors
 * modules/php/Rules/Board.php; the positions of the seeds inside a house are in src/scss/Constants.scss.
 */

/** The name of the storehouse among the locations of a player (the kampongs are `kampong_1` to `kampong_7`), same as Board::RUMAH. */
export const RUMAH = 'rumah';
export const KAMPONGS = 7;

/** The time a seed takes to slide from one house to another, in ms. */
export const SEED_DURATION = 800;
/** The time between two seeds of a sowing starting to slide, in ms: they leave one after the other, not all at once. */
export const SEED_STAGGER = 300;
/** A pause after each group of moves, so the players can follow what happened before the next notification starts, in ms. */
export const PAUSE = 500;

export const delayTime = (time: number) => new Promise<void>((resolve) => setTimeout(resolve, time));
