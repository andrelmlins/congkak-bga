import { PlayerSeeding } from './States/PlayerSeeding';
import { PlayersSeeding } from './States/PlayersSeeding';
import { HouseManager } from './Managers/HouseManager';
import { SeedManager } from './Managers/SeedManager';
import { SelectionManager } from './Managers/SelectionManager';
import { TableManager } from './Managers/TableManager';
import { BgaAnimationManager, BgaAnimations } from './libs';

export class Game {
  public bga: Bga<CongkakPlayer, CongkakGamedatas>;
  public animationManager: BgaAnimationManager;

  public houseManager: HouseManager;
  public seedManager: SeedManager;
  public selectionManager: SelectionManager;
  public tableManager: TableManager;

  constructor(bga: Bga<CongkakPlayer, CongkakGamedatas>) {
    this.bga = bga;
    this.animationManager = new BgaAnimations.Manager({ animationsActive: () => this.bga.gameui.bgaAnimationsActive() });

    // The board, the seeds and the page background are CSS images: load them now, so they do not pop in.
    this.bga.images.preloadImages(['table.png', 'seeds.png', 'background.png']);

    // Declare the Manager classes
    this.houseManager = new HouseManager();
    this.seedManager = new SeedManager(this);
    this.selectionManager = new SelectionManager(this);
    this.tableManager = new TableManager(this);

    // Declare the State classes (registered by the name of the PHP class, see modules/php/States)
    this.bga.states.register('PlayersSeeding', new PlayersSeeding(this, bga));
    this.bga.states.register('PlayerSeeding', new PlayerSeeding(this, bga));
  }

  setup(gamedatas: CongkakGamedatas) {
    this.tableManager.setup(gamedatas);

    this.setupNotifications();
  }

  setupNotifications() {
    // Automatically listens to the notifications, based on the `notif_xxx` methods below. The next notification only starts when the handler of the
    // last one returns, so each one waits for its animation (the server does not send pauses any more).
    this.bga.notifications.setupPromiseNotifications();
  }

  /** One or both players sowed (docs/RULES.md, sections 5 and 6). */
  async notif_playersSeeding(args: PlayersSeedingNotifArgs) {
    await this.seedManager.sow(args);
  }

  async notif_moveAllToRumah(args: MoveAllToRumahNotifArgs) {
    await this.seedManager.capture(args);
  }

  async notif_moveRemainingSeeds(args: MoveRemainingSeedsNotifArgs) {
    await this.seedManager.sweep(args);
  }

  async notif_newRound(args: NewRoundNotifArgs) {
    await this.tableManager.setRound(args.round);
  }

  async notif_moveStorehouseSeeds(args: MoveStorehouseSeedsNotifArgs) {
    await this.seedManager.reseed(args);
  }

  async notif_lockedHouses(args: LockedHousesNotifArgs) {
    this.houseManager.lock(args.playerId, args.lockeds);
  }
}
