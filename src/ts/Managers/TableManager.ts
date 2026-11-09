import { Game } from '../Game';
import { KAMPONGS, RUMAH } from '../Constants';

/**
 * The game area: the board with the 14 kampongs and the 2 rumah, the names of the players and the round (docs/RULES.md, sections 1 and 8). The player
 * looking at the game is always at the bottom, the opponent at the top.
 */
export class TableManager {
  private roundCounter: Counter;

  constructor(private game: Game) {}

  public setup(gamedatas: CongkakGamedatas) {
    this.setupGameArea();
    this.setupHouses(gamedatas);
    this.setupRound(gamedatas.roundDetails);
  }

  /** A new round started. */
  public setRound(round: number) {
    this.roundCounter.toValue(round);
  }

  private setupGameArea() {
    this.game.bga.gameArea.getElement().insertAdjacentHTML(
      'beforeend',
      `
        <div id="congkak-box" class="congkak-box">
          <div id="congkak-round-box" class="congkak-round-box">
            <span id="congkak-round-current"></span> / <span id="congkak-round-total"></span>
          </div>
          <div id="congkak-table" class="congkak-table">
            <div id="congkak-grid" class="congkak-grid"></div>
          </div>
        </div>
      `
    );
  }

  private setupHouses(gamedatas: CongkakGamedatas) {
    const houses = this.game.houseManager;
    const [me, opponent] = gamedatas.playerPosition;
    const table = document.getElementById('congkak-table');
    const grid = document.getElementById('congkak-grid');
    const house = (playerId: string, location: string) => houses.html(playerId, location, gamedatas.houseListLockeds[playerId][location] ?? false);

    // Each player sows from the kampong 7 to the 1 and then goes into their rumah, so the opponent's row is shown in the opposite order.
    for (let i = KAMPONGS; i >= 1; i--) {
      grid.insertAdjacentHTML('beforeend', house(opponent, `kampong_${i}`));
    }
    for (let i = 1; i <= KAMPONGS; i++) {
      grid.insertAdjacentHTML('beforeend', house(me, `kampong_${i}`));
    }

    table.insertAdjacentHTML('beforeend', house(me, RUMAH));
    table.insertAdjacentHTML('beforeend', house(opponent, RUMAH));

    table.insertAdjacentHTML('beforeend', this.playerLabel(gamedatas.players[me], 'bottom', 'left'));
    table.insertAdjacentHTML('beforeend', this.playerLabel(gamedatas.players[opponent], 'top', 'right'));

    houses.setup(gamedatas.houseList);
  }

  private playerLabel(player: CongkakPlayer, position: 'top' | 'bottom', arrow: 'left' | 'right'): string {
    return `
      <span style="color: #${player.color}" class="congkak-player ${position}">
        <i class="fa fa-long-arrow-${arrow}" aria-hidden="true"></i>
        ${player.name}
      </span>
    `;
  }

  private setupRound(round: CongkakGamedatas['roundDetails']) {
    document.getElementById('congkak-round-box').insertAdjacentText('afterbegin', _('Round: '));
    document.getElementById('congkak-round-total').innerHTML = `${round.total ?? '&infin;'}`;

    this.roundCounter = new ebg.counter();
    this.roundCounter.create('congkak-round-current', { value: round.current });
  }
}
