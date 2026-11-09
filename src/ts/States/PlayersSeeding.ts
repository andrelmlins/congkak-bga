import { Game } from '../Game';

/**
 * Both players choose a house at the same time (see modules/php/States/PlayersSeeding.php, docs/RULES.md, section 5). A player who chose is not active
 * any more, so the houses go dark while they wait for the other one.
 */
export class PlayersSeeding {
  constructor(
    private game: Game,
    private bga: Bga<CongkakPlayer, CongkakGamedatas>,
  ) {}

  /** Called when the player becomes active (to choose) or stops being active (chose, or has nothing to choose). */
  onPlayerActivationChange(args: PlayersSeedingArgs, isCurrentPlayerActive: boolean) {
    if (!isCurrentPlayerActive) {
      this.game.selectionManager.clear();
      this.bga.statusBar.removeActionButtons();
      return;
    }

    this.game.selectionManager.start(args.locations[this.bga.players.getCurrentPlayerId()], (house) =>
      // No lock: the interface must stay free while the other player is choosing.
      this.bga.actions.performAction('actPlayersSeeding', { playerId: house.playerId, house: parseInt(house.location.split('_')[1]) }, { lock: false })
    );
  }

  onLeavingState() {
    this.game.selectionManager.clear();
  }
}
