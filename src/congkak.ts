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

  scoreCtrl: Record<string, Counter>;

  animationManager: AnimationManager;

  counters: Record<string, Record<string, Counter>> = {};

  constructor() {}

  public setup(gamedatas: CongkakGamedatas) {
    this.setupHouses();

    this.setupNotifications();
  }

  public setupHouses() {
    const table = document.getElementById('congkak-table');
    const grid = document.getElementById('congkak-grid');

    this.counters = { [this.gamedatas.playerPosition[0]]: {}, [this.gamedatas.playerPosition[1]]: {} };

    for (let i = 7; i >= 1; i--) {
      grid.insertAdjacentHTML(
        'beforeend',
        `
          <div id="congkak-${this.gamedatas.playerPosition[1]}-kampong-${i}" class="congkak-kampong">
            <span id="congkak-${this.gamedatas.playerPosition[1]}-kampong-${i}-counter" class="congkak-counter top"></span>
          </div>
        `
      );
    }

    for (let i = 1; i <= 7; i++) {
      grid.insertAdjacentHTML(
        'beforeend',
        `
          <div id="congkak-${this.gamedatas.playerPosition[0]}-kampong-${i}" class="congkak-kampong">
            <span id="congkak-${this.gamedatas.playerPosition[0]}-kampong-${i}-counter" class="congkak-counter bottom"></span>
          </div>
        `
      );
    }

    table.insertAdjacentHTML(
      'beforeend',
      `
        <div id="congkak-${this.gamedatas.playerPosition[0]}-rumah" class="congkak-rumah">
          <span id="congkak-${this.gamedatas.playerPosition[0]}-rumah-counter" class="congkak-counter left"></span>
        </div>
      `
    );

    table.insertAdjacentHTML(
      'beforeend',
      `
        <div id="congkak-${this.gamedatas.playerPosition[1]}-rumah" class="congkak-rumah">
          <span id="congkak-${this.gamedatas.playerPosition[1]}-rumah-counter" class="congkak-counter right"></span>
        </div>
      `
    );

    for (let playerId in this.gamedatas.houseList) {
      const playerHouses = this.gamedatas.houseList[playerId];

      for (let position in playerHouses.kampong) {
        this.setSeeds(`kampong-${position}`, playerId, playerHouses.kampong[position]);
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
    //
  }

  public onLeavingState(stateName: string) {
    //
  }

  public onUpdateActionButtons(stateName: string, notif: any) {
    //
  }

  public setupNotifications() {
    //
  }
}
