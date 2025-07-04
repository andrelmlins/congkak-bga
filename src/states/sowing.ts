class Sowing implements Game {
  private handlers: any[];
  private elementSelected: HTMLElement;

  constructor(public game: CongkakGame) {
    this.handlers = [];
    this.elementSelected = null;
  }

  public setup(gamedatas: CongkakGamedatas) {
    //
  }

  public onEnteringState(stateName: string, notif: Notif<PlayerSeedingState>) {
    if (stateName === 'playerSeeding' && (this.game as any).isCurrentPlayerActive()) {
      this.setSelectedHouses(notif.args.location);
    }
  }

  public onLeavingState(stateName: string) {
    if (stateName === 'playersSeeding') {
      this.removeSelecteds();
    } else if (stateName === 'playerSeeding') {
      this.removeSelecteds();
    }
  }

  public onUpdateActionButtons(stateName: string, args: PlayersSeedingState) {
    if (stateName === 'playersSeeding') {
      this.removeSelecteds();
      this.setSelectedHouses(args.locations[(this.game as any).getCurrentPlayerId()]);

      (this.game as any).addActionButton('sowAction', _('Sow'), () => this.onSowMulti());

      document.getElementById('sowAction').classList.add('disabled');
    } else if (stateName === 'playerSeeding') {
      (this.game as any).addActionButton('sowAction', _('Sow'), () => this.onSow());

      document.getElementById('sowAction').classList.add('disabled');
    }
  }

  public onUpdateActionButtonsWithoutActive(stateName: string) {
    if (stateName === 'playersSeeding') {
      this.removeSelecteds();
    }
  }

  public setupNotifications() {
    dojo.subscribe('playersSeeding', this, (notif) => this.playersSeedingNotif(notif));
    dojo.subscribe('moveAllToRumah', this, (notif) => this.moveAllToRumahNotif(notif));
    dojo.subscribe('moveRemainingSeeds', this, (notif) => this.moveRemainingSeedsNotif(notif));
  }

  public setSelectedHouses(location: { location: string; playerId: string }) {
    if (location.location === 'initial') {
      const playerId = (this.game as any).getCurrentPlayerId();

      for (let i = 1; i <= 7; i++) {
        const element = document.getElementById(`congkak-${playerId}-kampong_${i}`);

        if (element.querySelectorAll('.congkak-seed').length > 0) {
          element.classList.add('selectable');
          this.handlers.push(dojo.connect(element, 'onclick', this, () => this.onClick(element)));
        }
      }
    } else {
      const element = document.getElementById(`congkak-${location.playerId}-${location.location}`);

      if (element.querySelectorAll('.congkak-seed').length > 0) {
        element.classList.add('selectable');
        this.handlers.push(dojo.connect(element, 'onclick', this, () => this.onClick(element)));
      }
    }
  }

  public removeSelecteds() {
    const table = document.getElementById('congkak-table');

    const elements = table.querySelectorAll<HTMLElement>('.congkak-kampong.selectable');

    elements.forEach((cardElement) => {
      cardElement.classList.remove('selectable');
      cardElement.classList.remove('selected');
    });

    this.handlers.forEach((handler) => dojo.disconnect(handler));
    this.handlers = [];

    this.elementSelected = null;
  }

  public onClick(element: HTMLElement) {
    if (this.elementSelected) {
      this.elementSelected.classList.remove('selected');
    }

    if (this.elementSelected != element) {
      this.elementSelected = element;
      this.elementSelected.classList.add('selected');
      document.getElementById('sowAction').classList.remove('disabled');
    } else {
      this.elementSelected = null;
      document.getElementById('sowAction').classList.add('disabled');
    }
  }

  public onSowMulti() {
    const array = this.elementSelected.id.split('-');
    const [_, house] = this.elementSelected.id.split('_');

    (this.game as any).bgaPerformAction(
      'actPlayersSeeding',
      {
        playerId: array[1],
        house: parseInt(house),
      },
      { lock: false }
    );
  }

  public onSow() {
    const array = this.elementSelected.id.split('-');
    const [_, house] = this.elementSelected.id.split('_');

    (this.game as any).bgaPerformAction('actPlayerSeeding', {
      playerId: array[1],
      house: parseInt(house),
    });
  }

  public async playersSeedingNotif(notif: Notif<PlayersSeedingNotif>) {
    const seeds: Record<string, NodeListOf<HTMLElement>> = {};

    for (let playerId in notif.args.initialHouse) {
      const house = notif.args.initialHouse[playerId];

      seeds[playerId] = document
        .getElementById(`congkak-${house.playerId}-${house.location}`)
        .querySelectorAll<HTMLElement>('.congkak-seed');
    }

    for (let i = 0; i < notif.args.maxSeeds; i++) {
      for (const playerId in notif.args.movements) {
        const movement = notif.args.movements[playerId][i];
        if (!movement) continue;

        const initialHouse = notif.args.initialHouse[playerId];

        const seed = seeds[playerId][i];
        const destination = document.getElementById(`congkak-${movement.playerId}-${movement.location}`);

        const animation = new BgaLocalAnimation(this.game);
        animation.setOptions(seed, destination, 800);

        animation
          .call((_) => true)
          .then(() => {
            this.game.counters[movement.playerId][movement.location].incValue(1);
            this.game.counters[initialHouse.playerId][initialHouse.location].incValue(-1);
          });
      }

      await delayTime(300);
    }
  }

  public async moveAllToRumahNotif(notif: Notif<MoveAllToRumahNotif>) {
    const seeds = document
      .getElementById(`congkak-${notif.args.playerId}-${notif.args.location}`)
      .querySelectorAll<HTMLElement>('.congkak-seed');
    const opponentSeeds = document
      .getElementById(`congkak-${notif.args.opponentPlayerId}-${notif.args.opponentLocation}`)
      .querySelectorAll<HTMLElement>('.congkak-seed');
    const destination = document.getElementById(`congkak-${notif.args.playerId}-rumah`);

    seeds.forEach((item) => {
      const animation = new BgaLocalAnimation(this.game);
      animation.setOptions(item, destination, 800);

      animation.call((_) => true);
    });

    opponentSeeds.forEach((item) => {
      const animation = new BgaLocalAnimation(this.game);
      animation.setOptions(item, destination, 800);

      animation.call((_) => true);
    });

    this.game.counters[notif.args.playerId]['rumah'].incValue(seeds.length);
    this.game.counters[notif.args.playerId][notif.args.location].incValue(seeds.length * -1);

    this.game.counters[notif.args.playerId]['rumah'].incValue(opponentSeeds.length);
    this.game.counters[notif.args.opponentPlayerId][notif.args.opponentLocation].incValue(opponentSeeds.length * -1);
  }

  public async moveRemainingSeedsNotif(notif: Notif<MoveRemainingSeedsNotif>) {
    const destination = document.getElementById(`congkak-${notif.args.playerId}-rumah`);

    for (let i = 1; i <= 7; i++) {
      const seeds = document
        .getElementById(`congkak-${notif.args.playerId}-kampong_${i}`)
        .querySelectorAll<HTMLElement>('.congkak-seed');

      seeds.forEach((item) => {
        const animation = new BgaLocalAnimation(this.game);
        animation.setOptions(item, destination, 800);

        animation.call((_) => true);
      });

      this.game.counters[notif.args.playerId]['rumah'].incValue(seeds.length);
      this.game.counters[notif.args.playerId][`kampong_${i}`].incValue(seeds.length * -1);
    }
  }
}
