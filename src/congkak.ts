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

  constructor() {}

  public setup(gamedatas: CongkakGamedatas) {
    const grid = document.getElementById('congkak-grid');

    for (let i = 7; i >= 1; i--) {
      grid.insertAdjacentHTML('beforeend', `<div id="congkak-kampong-top-${i}" class="congkak-kampong"></div>`);
    }

    for (let i = 1; i <= 7; i++) {
      grid.insertAdjacentHTML('beforeend', `<div id="congkak-kampong-bottom-${i}" class="congkak-kampong"></div>`);
    }

    const container = document.getElementById('congkak-kampong-top-1');
    for (let i = 0; i < 15; i++) {
      container.insertAdjacentHTML('beforeend', `<div class="congkak-seed"></div>`);
    }

    this.setupNotifications();
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
