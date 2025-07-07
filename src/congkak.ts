declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;
declare const g_img_preload;
declare const playSound;

class Congkak implements CongkakGame {
  gamedatas: CongkakGamedatas;

  games: CongkakGames;

  scoreCtrl: Record<string, Counter>;

  animationManager: AnimationManager;

  counters: Record<string, Record<string, Counter>> = {};

  constructor() {
    this.games = {
      sowing: new Sowing(this),
    };
  }

  public setup(gamedatas: CongkakGamedatas) {
    this.setupHouses();

    this.animationManager = new AnimationManager(this);

    this.setupNotifications();
  }

  public setupHouses() {
    const table = document.getElementById('congkak-table');
    const grid = document.getElementById('congkak-grid');

    this.counters = { [this.gamedatas.playerPosition[0]]: {}, [this.gamedatas.playerPosition[1]]: {} };

    for (let i = 7; i >= 1; i--) {
      grid.insertAdjacentHTML('beforeend', this.formatHouse(this.gamedatas.playerPosition[1], `kampong_${i}`));
    }

    for (let i = 1; i <= 7; i++) {
      grid.insertAdjacentHTML('beforeend', this.formatHouse(this.gamedatas.playerPosition[0], `kampong_${i}`));
    }

    table.insertAdjacentHTML('beforeend', this.formatHouse(this.gamedatas.playerPosition[0], 'rumah'));
    table.insertAdjacentHTML('beforeend', this.formatHouse(this.gamedatas.playerPosition[1], 'rumah'));

    const currentPlayer = this.gamedatas.players[this.gamedatas.playerPosition[0]];
    table.insertAdjacentHTML(
      'beforeend',
      `<span style="color: #${currentPlayer.color}" class="congkak-player bottom">${currentPlayer.name}</span>`
    );

    const opponentPlayer = this.gamedatas.players[this.gamedatas.playerPosition[1]];
    table.insertAdjacentHTML(
      'beforeend',
      `<span style="color: #${opponentPlayer.color}" class="congkak-player top">${opponentPlayer.name}</span>`
    );

    for (let playerId in this.gamedatas.houseList) {
      const playerHouses = this.gamedatas.houseList[playerId];

      for (let position in playerHouses.kampong) {
        this.setSeeds(`kampong_${position}`, playerId, playerHouses.kampong[position]);
      }
      this.setSeeds('rumah', playerId, playerHouses.rumah);
    }
  }

  public setSeeds(house: string, playerId: string, seeds: number) {
    this.counters[playerId][house] = new ebg.counter();
    this.counters[playerId][house].create(`congkak-${playerId}-${house}-counter`);
    this.counters[playerId][house].setValue(seeds);

    const box = document.getElementById(`congkak-${playerId}-${house}`);

    for (let i = 0; i < seeds; i++) {
      box.insertAdjacentHTML('beforeend', `<div class="congkak-seed"></div>`);
    }
  }

  public formatHouse(playerId: string, house: string) {
    const locked = this.gamedatas.houseListLockeds[playerId][house] ?? false;
    const className = `congkak-${house == 'rumah' ? 'rumah' : 'kampong'} ${locked ? 'locked' : ''}`;

    return `
      <div id="congkak-${playerId}-${house}" class="${className}">
        <span id="congkak-${playerId}-${house}-counter" class="congkak-counter top"></span>
      </div>
    `;
  }

  public bgaFormatText(log, args) {
    try {
      if (log && args && !args.processed) {
        const formatStrings = new FormatStrings(this, args);
        formatStrings.format();

        args = formatStrings.args;
        args.processed = true;
      }
    } catch (e) {
      console.error(log, args, 'Exception thrown', e.stack);
    }

    return { log, args };
  }

  public onEnteringState(stateName: string, notif: Notif<any>) {
    for (let gameName in this.games) {
      this.games[gameName].onEnteringState(stateName, notif);
    }
  }

  public onLeavingState(stateName: string) {
    for (let gameName in this.games) {
      this.games[gameName].onLeavingState(stateName);
    }
  }

  public onUpdateActionButtons(stateName: string, notif: any) {
    if ((this as any).isCurrentPlayerActive()) {
      for (let gameName in this.games) {
        this.games[gameName].onUpdateActionButtons(stateName, notif);
      }
    } else {
      for (let gameName in this.games) {
        this.games[gameName].onUpdateActionButtonsWithoutActive?.call(this.games[gameName], stateName, notif);
      }
    }
  }

  public setupNotifications() {
    for (let gameName in this.games) {
      this.games[gameName].setupNotifications();
    }

    dojo.subscribe('score', this, (value) => this.scoreNotif(value));
  }

  private async scoreNotif(notif: Notif<ScoreNotif>) {
    this.scoreCtrl[notif.args.playerId].toValue(notif.args.score);
  }
}
