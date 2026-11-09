/**
 * One player sows alone (see modules/php/States/PlayerSeeding.php, docs/RULES.md, section 6): a free choice among the houses, or the one they must go
 * on from.
 */
class PlayerSeeding {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }
    onEnteringState(args, isCurrentPlayerActive) {
        if (!isCurrentPlayerActive) {
            return;
        }
        this.game.selectionManager.start(args.locations, (house) => this.bga.actions.performAction('actPlayerSeeding', { playerId: house.playerId, house: parseInt(house.location.split('_')[1]) }));
    }
    onLeavingState() {
        this.game.selectionManager.clear();
    }
}

/**
 * Both players choose a house at the same time (see modules/php/States/PlayersSeeding.php, docs/RULES.md, section 5). A player who chose is not active
 * any more, so the houses go dark while they wait for the other one.
 */
class PlayersSeeding {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }
    /** Called when the player becomes active (to choose) or stops being active (chose, or has nothing to choose). */
    onPlayerActivationChange(args, isCurrentPlayerActive) {
        if (!isCurrentPlayerActive) {
            this.game.selectionManager.clear();
            this.bga.statusBar.removeActionButtons();
            return;
        }
        this.game.selectionManager.start(args.locations[this.bga.players.getCurrentPlayerId()], (house) => 
        // No lock: the interface must stay free while the other player is choosing.
        this.bga.actions.performAction('actPlayersSeeding', { playerId: house.playerId, house: parseInt(house.location.split('_')[1]) }, { lock: false }));
    }
    onLeavingState() {
        this.game.selectionManager.clear();
    }
}

/**
 * Shared constants of the client, not tied to one manager. Import them from here instead of redeclaring them. The game data (houses, rounds) mirrors
 * modules/php/Rules/Board.php; the positions of the seeds inside a house are in src/scss/Constants.scss.
 */
/** The name of the storehouse among the locations of a player (the kampongs are `kampong_1` to `kampong_7`), same as Board::RUMAH. */
const RUMAH = 'rumah';
const KAMPONGS = 7;
/** The time a seed takes to slide from one house to another, in ms. */
const SEED_DURATION = 800;
/** The time between two seeds of a sowing starting to slide, in ms: they leave one after the other, not all at once. */
const SEED_STAGGER = 300;
/** A pause after each group of moves, so the players can follow what happened before the next notification starts, in ms. */
const PAUSE = 500;
const delayTime = (time) => new Promise((resolve) => setTimeout(resolve, time));

/**
 * The houses on the board (docs/RULES.md, section 1): the HTML of each one, the seeds inside it and the counter with their number (a BGA counter, so it
 * counts up and down). A house is told apart by its owner and its location: `kampong_1` to `kampong_7`, or `rumah`.
 */
class HouseManager {
    constructor() {
        this.counters = {};
    }
    /** The HTML of a house, to put on the board (see TableManager). */
    html(playerId, location, locked) {
        const className = `congkak-${location === RUMAH ? 'rumah' : 'kampong'} ${locked ? 'locked' : ''}`;
        return `
      <div id="congkak-${playerId}-${location}" class="${className}">
        <span id="congkak-${playerId}-${location}-counter" class="congkak-counter top"></span>
      </div>
    `;
    }
    /** Puts the seeds of every house (after the houses are on the board). */
    setup(houseList) {
        for (const playerId in houseList) {
            this.counters[playerId] = {};
            for (const position in houseList[playerId].kampong) {
                this.fill(playerId, `kampong_${position}`, houseList[playerId].kampong[position]);
            }
            this.fill(playerId, RUMAH, houseList[playerId].rumah);
        }
    }
    element(playerId, location) {
        return document.getElementById(`congkak-${playerId}-${location}`);
    }
    /** The seeds now in a house, as they are on the screen. */
    seeds(playerId, location) {
        return Array.from(this.element(playerId, location).querySelectorAll('.congkak-seed'));
    }
    /** Changes the number shown on a house, once the seeds have moved (the seeds themselves are moved by SeedManager). */
    change(playerId, location, by) {
        this.counters[playerId][location].incValue(by);
    }
    lock(playerId, locations) {
        locations.forEach((location) => this.element(playerId, location).classList.add('locked'));
    }
    fill(playerId, location, seeds) {
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

/**
 * The seeds moving between houses, one method for each kind of move of docs/RULES.md: a sowing (section 5 and 6), a capture (section 6), the seeds going
 * to the rumah when a round ends and the reseeding of the next round (section 8). Each one returns when the seeds have landed, and only then the
 * counters change, so the numbers follow what the player sees; the next notification does not start before that (promise notifications).
 */
class SeedManager {
    constructor(game) {
        this.game = game;
    }
    /** The seeds of one or both players leave their first house and each lands in the next house of the path, one after the other. */
    async sow(args) {
        const houses = this.game.houseManager;
        const sources = {};
        for (const playerId in args.initialHouse) {
            const house = args.initialHouse[playerId];
            sources[playerId] = houses.seeds(house.playerId, house.location);
        }
        const moves = [];
        for (let i = 0; i < args.maxSeeds; i++) {
            for (const playerId in args.movements) {
                const movement = args.movements[playerId][i];
                const seed = sources[playerId][i];
                if (!movement || !seed)
                    continue;
                const initial = args.initialHouse[playerId];
                moves.push(this.move(seed, houses.element(movement.playerId, movement.location)).then(() => {
                    houses.change(movement.playerId, movement.location, 1);
                    houses.change(initial.playerId, initial.location, -1);
                }));
            }
            await delayTime(SEED_STAGGER);
        }
        await Promise.all(moves);
        await delayTime(PAUSE);
    }
    /** A sowing ended in an own empty house: that seed and the seeds of the opposite house go to the rumah. */
    async capture(args) {
        const houses = this.game.houseManager;
        const own = houses.seeds(args.playerId, args.location);
        const opposite = houses.seeds(args.opponentPlayerId, args.opponentLocation);
        const rumah = houses.element(args.playerId, RUMAH);
        await Promise.all([...own, ...opposite].map((seed) => this.move(seed, rumah)));
        houses.change(args.playerId, RUMAH, own.length + opposite.length);
        houses.change(args.playerId, args.location, -own.length);
        houses.change(args.opponentPlayerId, args.opponentLocation, -opposite.length);
        await delayTime(PAUSE);
    }
    /** A round ended: what is left in the kampongs of a player goes to their rumah. */
    async sweep(args) {
        const houses = this.game.houseManager;
        const rumah = houses.element(args.playerId, RUMAH);
        const moves = [];
        for (let i = 1; i <= KAMPONGS; i++) {
            const location = `kampong_${i}`;
            const seeds = houses.seeds(args.playerId, location);
            moves.push(Promise.all(seeds.map((seed) => this.move(seed, rumah))).then(() => {
                houses.change(args.playerId, RUMAH, seeds.length);
                houses.change(args.playerId, location, -seeds.length);
            }));
        }
        await Promise.all(moves);
        await delayTime(PAUSE);
    }
    /** The next round: the rumah of a player refills their kampongs, and what does not fit leaves the game, towards the panel of the player. */
    async reseed(args) {
        const houses = this.game.houseManager;
        const seeds = houses.seeds(args.playerId, RUMAH);
        let used = 0;
        for (const location in args.movements) {
            const count = args.movements[location];
            const destination = houses.element(args.playerId, location);
            await Promise.all(seeds.slice(used, used + count).map((seed) => this.move(seed, destination)));
            used += count;
            houses.change(args.playerId, location, count);
            houses.change(args.playerId, RUMAH, -count);
        }
        const panel = this.game.bga.playerPanels.getElement(Number(args.playerId));
        const leaving = seeds.slice(used, used + args.outwardMovementsCount);
        await Promise.all(leaving.map((seed) => this.move(seed, panel).then(() => seed.remove())));
        houses.change(args.playerId, RUMAH, -leaving.length);
        await delayTime(PAUSE);
    }
    move(seed, destination) {
        return this.game.animationManager.slideAndAttach(seed, destination, { duration: SEED_DURATION, bump: 1 });
    }
}

/**
 * Choosing the house to sow (docs/RULES.md, sections 5 and 6): the houses that can be chosen light up, a click selects one (a second click on it
 * unselects it) and the button "Sow" of the status bar confirms. Nothing is sent on the click of a house.
 */
class SelectionManager {
    constructor(game) {
        this.game = game;
        this.selected = null;
        this.clicks = [];
    }
    /**
     * @param houses the houses that can be chosen
     * @param onSow what to do with the chosen house when the player confirms
     */
    start(houses, onSow) {
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
    clear() {
        this.clicks.forEach(({ element, handler }) => {
            element.classList.remove('selectable', 'selected');
            element.removeEventListener('click', handler);
        });
        this.clicks = [];
        this.selected = null;
    }
    onClick(house, onSow) {
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
    refresh(onSow) {
        this.game.bga.statusBar.removeActionButtons();
        this.game.bga.statusBar.addActionButton(_('Sow'), () => onSow(this.selected), { id: 'congkak-sow', disabled: this.selected === null });
    }
}

/**
 * The game area: the board with the 14 kampongs and the 2 rumah, the names of the players and the round (docs/RULES.md, sections 1 and 8). The player
 * looking at the game is always at the bottom, the opponent at the top.
 */
class TableManager {
    constructor(game) {
        this.game = game;
    }
    setup(gamedatas) {
        this.setupGameArea();
        this.setupHouses(gamedatas);
        this.setupRound(gamedatas.roundDetails);
    }
    /** A new round started. */
    setRound(round) {
        this.roundCounter.toValue(round);
    }
    setupGameArea() {
        this.game.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
        <div id="congkak-box" class="congkak-box">
          <div id="congkak-round-box" class="congkak-round-box">
            <span id="congkak-round-current"></span> / <span id="congkak-round-total"></span>
          </div>
          <div id="congkak-table" class="congkak-table">
            <div id="congkak-grid" class="congkak-grid"></div>
          </div>
        </div>
      `);
    }
    setupHouses(gamedatas) {
        const houses = this.game.houseManager;
        const [me, opponent] = gamedatas.playerPosition;
        const table = document.getElementById('congkak-table');
        const grid = document.getElementById('congkak-grid');
        const house = (playerId, location) => houses.html(playerId, location, gamedatas.houseListLockeds[playerId][location] ?? false);
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
    playerLabel(player, position, arrow) {
        return `
      <span style="color: #${player.color}" class="congkak-player ${position}">
        <i class="fa fa-long-arrow-${arrow}" aria-hidden="true"></i>
        ${player.name}
      </span>
    `;
    }
    setupRound(round) {
        document.getElementById('congkak-round-box').insertAdjacentText('afterbegin', _('Round: '));
        document.getElementById('congkak-round-total').innerHTML = `${round.total ?? '&infin;'}`;
        this.roundCounter = new ebg.counter();
        this.roundCounter.create('congkak-round-current', { value: round.current });
    }
}

/*
BGA libs, loaded as ESM modules (see importEsmLib in bga-framework.d.ts). Import them with `import { BgaAnimations } from './libs';`.
Only what the game uses is typed here, instead of the full typing files of the libs (same as feyasswamp):
https://en.doc.boardgamearena.com/BgaAnimations
*/
const BgaAnimations = await globalThis.importEsmLib('bga-animations', '1.x');

class Game {
    constructor(bga) {
        this.bga = bga;
        this.animationManager = new BgaAnimations.Manager({ animationsActive: () => this.bga.gameui.bgaAnimationsActive() });
        // Declare the Manager classes
        this.houseManager = new HouseManager();
        this.seedManager = new SeedManager(this);
        this.selectionManager = new SelectionManager(this);
        this.tableManager = new TableManager(this);
        // Declare the State classes (registered by the name of the PHP class, see modules/php/States)
        this.bga.states.register('PlayersSeeding', new PlayersSeeding(this, bga));
        this.bga.states.register('PlayerSeeding', new PlayerSeeding(this, bga));
    }
    setup(gamedatas) {
        this.tableManager.setup(gamedatas);
        this.setupNotifications();
    }
    setupNotifications() {
        // Automatically listens to the notifications, based on the `notif_xxx` methods below. The next notification only starts when the handler of the
        // last one returns, so each one waits for its animation (the server does not send pauses any more).
        this.bga.notifications.setupPromiseNotifications();
    }
    /** One or both players sowed (docs/RULES.md, sections 5 and 6). */
    async notif_playersSeeding(args) {
        await this.seedManager.sow(args);
    }
    async notif_moveAllToRumah(args) {
        await this.seedManager.capture(args);
    }
    async notif_moveRemainingSeeds(args) {
        await this.seedManager.sweep(args);
    }
    async notif_newRound(args) {
        this.tableManager.setRound(args.round);
    }
    async notif_moveStorehouseSeeds(args) {
        await this.seedManager.reseed(args);
    }
    async notif_lockedHouses(args) {
        this.houseManager.lock(args.playerId, args.lockeds);
    }
}

export { Game };
