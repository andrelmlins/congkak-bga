import { Game } from '../Game';
import { BgaAttachWithAnimation, BgaSlideAnimation } from '../vendor/bga-animations.js';
import { PAUSE, RUMAH, SEED_DURATION, SEED_STAGGER, KAMPONGS, delayTime } from '../Constants';

/**
 * The seeds moving between houses, one method for each kind of move of docs/RULES.md: a sowing (section 5 and 6), a capture (section 6), the seeds going
 * to the rumah when a round ends and the reseeding of the next round (section 8). Each one returns when the seeds have landed, and only then the
 * counters change, so the numbers follow what the player sees; the next notification does not start before that (promise notifications).
 */
export class SeedManager {
  constructor(private game: Game) {}

  /** The seeds of one or both players leave their first house and each lands in the next house of the path, one after the other. */
  public async sow(args: PlayersSeedingNotifArgs) {
    const houses = this.game.houseManager;
    const sources: Record<string, HTMLElement[]> = {};

    for (const playerId in args.initialHouse) {
      const house = args.initialHouse[playerId];
      sources[playerId] = houses.seeds(house.playerId, house.location);
    }

    const moves: Promise<void>[] = [];

    for (let i = 0; i < args.maxSeeds; i++) {
      for (const playerId in args.movements) {
        const movement = args.movements[playerId][i];
        const seed = sources[playerId][i];
        if (!movement || !seed) continue;

        const initial = args.initialHouse[playerId];

        moves.push(
          this.move(seed, houses.element(movement.playerId, movement.location)).then(() => {
            houses.change(movement.playerId, movement.location, 1);
            houses.change(initial.playerId, initial.location, -1);
          })
        );
      }

      await delayTime(SEED_STAGGER);
    }

    await Promise.all(moves);
    await delayTime(PAUSE);
  }

  /** A sowing ended in an own empty house: that seed and the seeds of the opposite house go to the rumah. */
  public async capture(args: MoveAllToRumahNotifArgs) {
    const houses = this.game.houseManager;
    const own = houses.seeds(args.playerId, args.location);
    const opposite = houses.seeds(args.opponentPlayerId, args.opponentLocation);
    const rumah = houses.element(args.playerId, RUMAH);

    await Promise.all([...own, ...opposite].map((seed) => this.move(seed, rumah)));

    houses.change(args.playerId, RUMAH, own.length + opposite.length);
    houses.change(args.playerId, args.location, -own.length);
    houses.change(args.opponentPlayerId, args.opponentLocation, -opposite.length);
    await delayTime(PAUSE);
  }

  /** A round ended: what is left in the kampongs of a player goes to their rumah. */
  public async sweep(args: MoveRemainingSeedsNotifArgs) {
    const houses = this.game.houseManager;
    const rumah = houses.element(args.playerId, RUMAH);

    const moves: Promise<void>[] = [];
    for (let i = 1; i <= KAMPONGS; i++) {
      const location = `kampong_${i}`;
      const seeds = houses.seeds(args.playerId, location);

      moves.push(
        Promise.all(seeds.map((seed) => this.move(seed, rumah))).then(() => {
          houses.change(args.playerId, RUMAH, seeds.length);
          houses.change(args.playerId, location, -seeds.length);
        })
      );
    }

    await Promise.all(moves);
    await delayTime(PAUSE);
  }

  /** The next round: the rumah of a player refills their kampongs, and what does not fit leaves the game, towards the panel of the player. */
  public async reseed(args: MoveStorehouseSeedsNotifArgs) {
    const houses = this.game.houseManager;
    const seeds = houses.seeds(args.playerId, RUMAH);
    let used = 0;

    for (const location in args.movements) {
      const count = args.movements[location];
      const destination = houses.element(args.playerId, location);

      await Promise.all(seeds.slice(used, used + count).map((seed) => this.move(seed, destination)));
      used += count;

      houses.change(args.playerId, location, count);
      houses.change(args.playerId, RUMAH, -count);
    }

    const panel = this.game.bga.playerPanels.getElement(Number(args.playerId));
    const leaving = seeds.slice(used, used + args.outwardMovementsCount);

    await Promise.all(leaving.map((seed) => this.move(seed, panel).then(() => seed.remove())));
    houses.change(args.playerId, RUMAH, -leaving.length);
    await delayTime(PAUSE);
  }

  /** The seed slides from its house to the destination and is attached to it (the same as before the client moved to the Game class). */
  private move(seed: HTMLElement, destination: HTMLElement): Promise<unknown> {
    const slide = new BgaSlideAnimation({ element: seed, duration: SEED_DURATION, rotationDelta: 0 });

    return this.game.animationManager.play(new BgaAttachWithAnimation({ animation: slide, where: 'beforeend', attachElement: destination }));
  }
}
