import { Game } from '../Game';

const STORAGE_KEY = 'congkak-help-seen';

/**
 * The player aid (docs/RULES.md, sections 4 to 7): how a sowing ends, which is what new players ask about most (when the turn goes on, when it ends,
 * when seeds are captured). It takes no room of the page, so the board still fits in the first view: a round "?" button in the corner of the board
 * opens it over the board and it closes with its own button, a click outside or Esc. It opens by itself the first time a player sees the game.
 */
export class HelpManager {
  private panel: HTMLElement;
  private button: HTMLElement;

  constructor(private game: Game) {}

  public setup() {
    document.getElementById('congkak-box').insertAdjacentHTML('beforeend', this.html());

    this.panel = document.getElementById('congkak-help');
    this.button = document.getElementById('congkak-help-button');

    this.button.addEventListener('click', () => this.toggle());
    document.getElementById('congkak-help-close').addEventListener('click', () => this.toggle(false));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && this.toggle(false));
    document.addEventListener('click', (event) => {
      const target = event.target as Node;
      if (!this.panel.hasAttribute('hidden') && !this.panel.contains(target) && !this.button.contains(target)) {
        this.toggle(false);
      }
    });

    if (!this.wasSeen()) {
      this.toggle(true);
      this.markSeen();
    }
  }

  private toggle(open: boolean = this.panel.hasAttribute('hidden')) {
    this.panel.hidden = !open;
    this.button.setAttribute('aria-expanded', String(open));
  }

  private html(): string {
    const endings: [string, string, string][] = [
      ['rumah', _('Your rumah'), _('You play again: choose any of your houses with seeds.')],
      ['seeds', _('A house with seeds'), _('You take all the seeds of that house, the one that just fell included, and sow again from it. It is not a choice.')],
      ['capture', _('An empty house of yours'), _('You capture: that seed and all the seeds of the house in front of it go to your rumah. Your turn ends.')],
      ['opponent', _('An empty house of your opponent'), _('The seed stays there and your turn ends.')],
    ];

    return `
      <button id="congkak-help-button" class="congkak-help-button" type="button" aria-expanded="false" aria-controls="congkak-help" title="${_('How a sowing ends')}">?</button>
      <div id="congkak-help" class="congkak-help" role="dialog" aria-label="${_('How a sowing ends')}" hidden>
        <div class="congkak-help-header">
          <b>${_('How a sowing ends')}</b>
          <button id="congkak-help-close" class="congkak-help-close" type="button" aria-label="${_('Close')}">&times;</button>
        </div>
        <div class="congkak-help-body">
          <p>${_('Choose one of your houses with seeds, take them all and sow clockwise, one seed in each house: your houses from 7 to 1, your rumah (the big one), then the houses of your opponent from 7 to 1. The rumah of your opponent and burnt houses are skipped. Where the last seed falls decides what happens:')}</p>
          <ol class="congkak-help-endings">
            ${endings
              .map(
                ([type, title, text]) => `
                  <li class="congkak-help-ending" type="${type}">
                    <b>${title}</b>
                    <span>${text}</span>
                  </li>`
              )
              .join('')}
          </ol>
          <p>${_('The first sowing of the game is made by both players at the same time. A round ends when one side has no seeds left in its houses: the seeds that remain on the other side go to the rumah of their owner, and whoever has more seeds in the rumah wins the round.')}</p>
        </div>
      </div>
    `;
  }

  private wasSeen(): boolean {
    try {
      return localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  private markSeen() {
    try {
      localStorage.setItem(STORAGE_KEY, '1');
    } catch (e) {
      // Only a convenience: without storage the aid opens by itself every time.
    }
  }
}
