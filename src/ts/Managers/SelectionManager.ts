import { Game } from '../Game';

/**
 * Choosing the house to sow (docs/RULES.md, sections 5 and 6): the houses that can be chosen light up, a click selects one (a second click on it
 * unselects it) and the button "Sow" of the status bar confirms. Nothing is sent on the click of a house.
 */
export class SelectionManager {
  private selected: CongkakHouse | null = null;
  private clicks: { element: HTMLElement; handler: () => void }[] = [];

  constructor(private game: Game) {}

  /**
   * @param houses the houses that can be chosen
   * @param onSow what to do with the chosen house when the player confirms
   */
  public start(houses: CongkakHouse[], onSow: (house: CongkakHouse) => void) {
    this.clear();

    houses.forEach((house) => {
      const element = this.game.houseManager.element(house.playerId, house.location);
      const handler = () => this.onClick(house, onSow);

      element.classList.add('selectable');
      element.addEventListener('click', handler);
      this.clicks.push({ element, handler });
    });

    this.refresh(onSow);
  }

  /** Takes the lights, the clicks and the button away. */
  public clear() {
    this.clicks.forEach(({ element, handler }) => {
      element.classList.remove('selectable', 'selected');
      element.removeEventListener('click', handler);
    });

    this.clicks = [];
    this.selected = null;
  }

  private onClick(house: CongkakHouse, onSow: (house: CongkakHouse) => void) {
    const same = this.selected?.playerId === house.playerId && this.selected?.location === house.location;

    if (this.selected) {
      this.game.houseManager.element(this.selected.playerId, this.selected.location).classList.remove('selected');
    }

    this.selected = same ? null : house;

    if (this.selected) {
      this.game.houseManager.element(house.playerId, house.location).classList.add('selected');
    }

    this.refresh(onSow);
  }

  /** The button follows the selection: disabled until a house is selected. */
  private refresh(onSow: (house: CongkakHouse) => void) {
    this.game.bga.statusBar.removeActionButtons();
    this.game.bga.statusBar.addActionButton(_('Sow'), () => onSow(this.selected), { id: 'congkak-sow', disabled: this.selected === null });
  }
}
