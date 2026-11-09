import { RUMAH } from '../Constants';

/**
 * The houses on the board (docs/RULES.md, section 1): the HTML of each one, the seeds inside it and the counter with their number (a BGA counter, so it
 * counts up and down). A house is told apart by its owner and its location: `kampong_1` to `kampong_7`, or `rumah`.
 */
export class HouseManager {
  private counters: Record<string, Record<string, Counter>> = {};

  /** The HTML of a house, to put on the board (see TableManager). */
  public html(playerId: string, location: string, locked: boolean): string {
    const className = `congkak-${location === RUMAH ? 'rumah' : 'kampong'} ${locked ? 'locked' : ''}`;

    return `
      <div id="congkak-${playerId}-${location}" class="${className}">
        <span id="congkak-${playerId}-${location}-counter" class="congkak-counter top"></span>
      </div>
    `;
  }

  /** Puts the seeds of every house (after the houses are on the board). */
  public setup(houseList: CongkakGamedatas['houseList']) {
    for (const playerId in houseList) {
      this.counters[playerId] = {};

      for (const position in houseList[playerId].kampong) {
        this.fill(playerId, `kampong_${position}`, houseList[playerId].kampong[position]);
      }
      this.fill(playerId, RUMAH, houseList[playerId].rumah);
    }
  }

  public element(playerId: string, location: string): HTMLElement {
    return document.getElementById(`congkak-${playerId}-${location}`);
  }

  /**
   * The seeds now in a house, as they are on the screen, the last one first. The position of a seed in the house is the order it has among its siblings
   * (see src/scss/Seeds.scss), so the seeds that leave must be the last ones: the others then stay where they are instead of jumping to the empty place.
   */
  public seeds(playerId: string, location: string): HTMLElement[] {
    return Array.from(this.element(playerId, location).querySelectorAll<HTMLElement>('.congkak-seed')).reverse();
  }

  /** Changes the number shown on a house, once the seeds have moved (the seeds themselves are moved by SeedManager). */
  public change(playerId: string, location: string, by: number) {
    this.counters[playerId][location].incValue(by);
  }

  public lock(playerId: string, locations: string[]) {
    locations.forEach((location) => this.element(playerId, location).classList.add('locked'));
  }

  private fill(playerId: string, location: string, seeds: number) {
    const counter = new ebg.counter();
    counter.create(`congkak-${playerId}-${location}-counter`, { value: seeds });
    this.counters[playerId][location] = counter;

    const box = this.element(playerId, location);

    for (let i = 0; i < seeds; i++) {
      // Which of the 4 marbles of the sprite this seed shows, at random.
      const position = Math.floor(Math.random() * 4 + 1);

      box.insertAdjacentHTML('beforeend', `<div class="congkak-seed" position="${position}"></div>`);
    }
  }
}
