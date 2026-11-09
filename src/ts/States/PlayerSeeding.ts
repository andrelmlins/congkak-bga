import { Game } from '../Game';

/**
 * One player sows alone (see modules/php/States/PlayerSeeding.php, docs/RULES.md, section 6): a free choice among the houses, or the one they must go
 * on from.
 */
export class PlayerSeeding {
  constructor(
    private game: Game,
    private bga: Bga<CongkakPlayer, CongkakGamedatas>,
  ) {}

  onEnteringState(args: PlayerSeedingArgs, isCurrentPlayerActive: boolean) {
    if (!isCurrentPlayerActive) {
      return;
    }

    this.game.selectionManager.start(args.locations, (house) =>
      this.bga.actions.performAction('actPlayerSeeding', { playerId: house.playerId, house: parseInt(house.location.split('_')[1]) })
    );
  }

  onLeavingState() {
    this.game.selectionManager.clear();
  }
}
