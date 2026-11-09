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
    /**
     * The seeds now in a house, as they are on the screen, the last one first. The position of a seed in the house is the order it has among its siblings
     * (see src/scss/Seeds.scss), so the seeds that leave must be the last ones: the others then stay where they are instead of jumping to the empty place.
     */
    seeds(playerId, location) {
        return Array.from(this.element(playerId, location).querySelectorAll('.congkak-seed')).reverse();
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

/*
 * The bga-animations of André's fork (github.com/andrelmlins/bga-animations, commit 43194a9, version 1.1.2), compiled, as the game used before the
 * client was moved to the Game class (docs/MIGRATION.md, Phases 5 and 6). The official ESM lib of BGA (importEsmLib) made the seeds shake when they left
 * their house, so the game goes back to this one. Used by src/ts/Managers/SeedManager.ts; typed in bga-animations.d.ts.
 *
 * Only changes to the original: the export line at the end, the console.log calls of logAnimation() left out (they are not used) and the `this && this.__x`
 * checks of the TypeScript helpers replaced by `false` (`this` is undefined in a module, so they always built their own helper).
 */
var BgaAnimation = /** @class */ (function () {
    function BgaAnimation(animationFunction, settings) {
        this.animationFunction = animationFunction;
        this.settings = settings;
        this.played = null;
        this.result = null;
        this.playWhenNoAnimation = false;
    }
    return BgaAnimation;
}());
function shouldAnimate(settings) {
    var _a;
    return document.visibilityState !== 'hidden' && !((_a = settings === null || settings === void 0 ? void 0 : settings.game) === null || _a === void 0 ? void 0 : _a.instantaneousMode);
}
/**
 * Return the x and y delta, based on the animation settings;
 *
 * @param settings an `AnimationSettings` object
 * @returns a promise when animation ends
 */
function getDeltaCoordinates(element, settings, animationManager) {
    var _a;
    if (!settings.fromDelta && !settings.fromRect && !settings.fromElement) {
        throw new Error("[bga-animation] fromDelta, fromRect or fromElement need to be set");
    }
    var x = 0;
    var y = 0;
    if (settings.fromDelta) {
        x = settings.fromDelta.x;
        y = settings.fromDelta.y;
    }
    else {
        var originBR = (_a = settings.fromRect) !== null && _a !== void 0 ? _a : animationManager.game.getBoundingClientRectIgnoreZoom(settings.fromElement);
        // TODO make it an option ?
        var originalTransform = element.style.transform;
        element.style.transform = '';
        var destinationBR = animationManager.game.getBoundingClientRectIgnoreZoom(element);
        element.style.transform = originalTransform;
        x = (destinationBR.left + destinationBR.right) / 2 - (originBR.left + originBR.right) / 2;
        y = (destinationBR.top + destinationBR.bottom) / 2 - (originBR.top + originBR.bottom) / 2;
    }
    if (settings.scale) {
        x /= settings.scale;
        y /= settings.scale;
    }
    return { x: x, y: y };
}
function logAnimation(animationManager, animation) {
    var settings = animation.settings;
    var element = settings.element;
    if (element) {
    }
    else {
    }
    return Promise.resolve(false);
}
var __extends = false || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
/**
 * Slide of the element from origin to destination.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a, _b, _c, _d, _e;
        var settings = animation.settings;
        var element = settings.element;
        var _f = getDeltaCoordinates(element, settings, animationManager), x = _f.x, y = _f.y;
        var duration = (_a = settings.duration) !== null && _a !== void 0 ? _a : 500;
        var originalZIndex = element.style.zIndex;
        var originalTransition = element.style.transition;
        var transitionTimingFunction = (_b = settings.transitionTimingFunction) !== null && _b !== void 0 ? _b : 'linear';
        element.style.zIndex = "".concat((_c = settings === null || settings === void 0 ? void 0 : settings.zIndex) !== null && _c !== void 0 ? _c : 10);
        element.style.transition = null;
        element.offsetHeight;
        element.style.transform = "translate(".concat(-x, "px, ").concat(-y, "px) rotate(").concat((_d = settings === null || settings === void 0 ? void 0 : settings.rotationDelta) !== null && _d !== void 0 ? _d : 0, "deg)");
        var timeoutId = null;
        var cleanOnTransitionEnd = function () {
            element.style.zIndex = originalZIndex;
            element.style.transition = originalTransition;
            success();
            element.removeEventListener('transitioncancel', cleanOnTransitionEnd);
            element.removeEventListener('transitionend', cleanOnTransitionEnd);
            document.removeEventListener('visibilitychange', cleanOnTransitionEnd);
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        };
        var cleanOnTransitionCancel = function () {
            var _a;
            element.style.transition = "";
            element.offsetHeight;
            element.style.transform = (_a = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _a !== void 0 ? _a : null;
            element.offsetHeight;
            cleanOnTransitionEnd();
        };
        element.addEventListener('transitioncancel', cleanOnTransitionCancel);
        element.addEventListener('transitionend', cleanOnTransitionEnd);
        document.addEventListener('visibilitychange', cleanOnTransitionCancel);
        element.offsetHeight;
        element.style.transition = "transform ".concat(duration, "ms ").concat(transitionTimingFunction);
        element.offsetHeight;
        element.style.transform = (_e = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _e !== void 0 ? _e : null;
        // safety in case transitionend and transitioncancel are not called
        timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
    });
    return promise;
}
var BgaSlideAnimation = /** @class */ (function (_super) {
    __extends(BgaSlideAnimation, _super);
    function BgaSlideAnimation(settings) {
        return _super.call(this, slideAnimation, settings) || this;
    }
    return BgaSlideAnimation;
}(BgaAnimation));
/**
 * Slide of the element from destination to origin.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideToAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a, _b, _c, _d, _e;
        var settings = animation.settings;
        var element = settings.element;
        var _f = getDeltaCoordinates(element, settings, animationManager), x = _f.x, y = _f.y;
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        var originalZIndex = element.style.zIndex;
        var originalTransition = element.style.transition;
        var transitionTimingFunction = (_b = settings.transitionTimingFunction) !== null && _b !== void 0 ? _b : 'linear';
        element.style.zIndex = "".concat((_c = settings === null || settings === void 0 ? void 0 : settings.zIndex) !== null && _c !== void 0 ? _c : 10);
        var timeoutId = null;
        var cleanOnTransitionEnd = function () {
            element.style.zIndex = originalZIndex;
            element.style.transition = originalTransition;
            success();
            element.removeEventListener('transitioncancel', cleanOnTransitionEnd);
            element.removeEventListener('transitionend', cleanOnTransitionEnd);
            document.removeEventListener('visibilitychange', cleanOnTransitionEnd);
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        };
        var cleanOnTransitionCancel = function () {
            var _a;
            element.style.transition = "";
            element.offsetHeight;
            element.style.transform = (_a = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _a !== void 0 ? _a : null;
            element.offsetHeight;
            cleanOnTransitionEnd();
        };
        element.addEventListener('transitioncancel', cleanOnTransitionEnd);
        element.addEventListener('transitionend', cleanOnTransitionEnd);
        document.addEventListener('visibilitychange', cleanOnTransitionCancel);
        element.offsetHeight;
        element.style.transition = "transform ".concat(duration, "ms ").concat(transitionTimingFunction);
        element.offsetHeight;
        element.style.transform = "translate(".concat(-x, "px, ").concat(-y, "px) rotate(").concat((_d = settings === null || settings === void 0 ? void 0 : settings.rotationDelta) !== null && _d !== void 0 ? _d : 0, "deg) scale(").concat((_e = settings.scale) !== null && _e !== void 0 ? _e : 1, ")");
        // safety in case transitionend and transitioncancel are not called
        timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
    });
    return promise;
}
var BgaSlideToAnimation = /** @class */ (function (_super) {
    __extends(BgaSlideToAnimation, _super);
    function BgaSlideToAnimation(settings) {
        return _super.call(this, slideToAnimation, settings) || this;
    }
    return BgaSlideToAnimation;
}(BgaAnimation));
/**
 * Show the element at the center of the screen
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function showScreenCenterAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a, _b, _c, _d;
        var settings = animation.settings;
        var element = settings.element;
        var elementBR = animationManager.game.getBoundingClientRectIgnoreZoom(element);
        var xCenter = (elementBR.left + elementBR.right) / 2;
        var yCenter = (elementBR.top + elementBR.bottom) / 2;
        var x = xCenter - (window.innerWidth / 2);
        var y = yCenter - (window.innerHeight / 2);
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        var originalZIndex = element.style.zIndex;
        var originalTransition = element.style.transition;
        var transitionTimingFunction = (_b = settings.transitionTimingFunction) !== null && _b !== void 0 ? _b : 'linear';
        element.style.zIndex = "".concat((_c = settings === null || settings === void 0 ? void 0 : settings.zIndex) !== null && _c !== void 0 ? _c : 10);
        var timeoutId = null;
        var cleanOnTransitionEnd = function () {
            element.style.zIndex = originalZIndex;
            element.style.transition = originalTransition;
            success();
            element.removeEventListener('transitioncancel', cleanOnTransitionEnd);
            element.removeEventListener('transitionend', cleanOnTransitionEnd);
            document.removeEventListener('visibilitychange', cleanOnTransitionEnd);
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        };
        var cleanOnTransitionCancel = function () {
            var _a;
            element.style.transition = "";
            element.offsetHeight;
            element.style.transform = (_a = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _a !== void 0 ? _a : null;
            element.offsetHeight;
            cleanOnTransitionEnd();
        };
        element.addEventListener('transitioncancel', cleanOnTransitionEnd);
        element.addEventListener('transitionend', cleanOnTransitionEnd);
        document.addEventListener('visibilitychange', cleanOnTransitionCancel);
        element.offsetHeight;
        element.style.transition = "transform ".concat(duration, "ms ").concat(transitionTimingFunction);
        element.offsetHeight;
        element.style.transform = "translate(".concat(-x, "px, ").concat(-y, "px) rotate(").concat((_d = settings === null || settings === void 0 ? void 0 : settings.rotationDelta) !== null && _d !== void 0 ? _d : 0, "deg)");
        // safety in case transitionend and transitioncancel are not called
        timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
    });
    return promise;
}
var BgaShowScreenCenterAnimation = /** @class */ (function (_super) {
    __extends(BgaShowScreenCenterAnimation, _super);
    function BgaShowScreenCenterAnimation(settings) {
        return _super.call(this, showScreenCenterAnimation, settings) || this;
    }
    return BgaShowScreenCenterAnimation;
}(BgaAnimation));
/**
 * Just does nothing for the duration
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function pauseAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a;
        var settings = animation.settings;
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        setTimeout(function () { return success(); }, duration);
    });
    return promise;
}
var BgaPauseAnimation = /** @class */ (function (_super) {
    __extends(BgaPauseAnimation, _super);
    function BgaPauseAnimation(settings) {
        return _super.call(this, pauseAnimation, settings) || this;
    }
    return BgaPauseAnimation;
}(BgaAnimation));
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function attachWithAnimation(animationManager, animation) {
    var _a;
    var settings = animation.settings;
    var element = settings.animation.settings.element;
    var fromRect = animationManager.game.getBoundingClientRectIgnoreZoom(element);
    settings.animation.settings.fromRect = fromRect;
    settings.attachElement.appendChild(element);
    (_a = settings.afterAttach) === null || _a === void 0 ? void 0 : _a.call(settings, element, settings.attachElement);
    return animationManager.play(settings.animation);
}
var BgaAttachWithAnimation = /** @class */ (function (_super) {
    __extends(BgaAttachWithAnimation, _super);
    function BgaAttachWithAnimation(settings) {
        var _this = _super.call(this, attachWithAnimation, settings) || this;
        _this.playWhenNoAnimation = true;
        return _this;
    }
    return BgaAttachWithAnimation;
}(BgaAnimation));
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function cumulatedAnimations(animationManager, animation) {
    return animationManager.playSequence(animation.settings.animations);
}
var BgaCumulatedAnimation = /** @class */ (function (_super) {
    __extends(BgaCumulatedAnimation, _super);
    function BgaCumulatedAnimation(settings) {
        var _this = _super.call(this, cumulatedAnimations, settings) || this;
        _this.playWhenNoAnimation = true;
        return _this;
    }
    return BgaCumulatedAnimation;
}(BgaAnimation));
var __assign = false || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __awaiter = false || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = false || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArray = false || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
var AnimationManager = /** @class */ (function () {
    /**
     * @param game the BGA game class, usually it will be `this`
     * @param settings: a `AnimationManagerSettings` object
     */
    function AnimationManager(game, settings) {
        this.game = game;
        this.settings = settings;
        this.zoomManager = settings === null || settings === void 0 ? void 0 : settings.zoomManager;
        if (!game) {
            throw new Error('You must set your game as the first parameter of AnimationManager');
        }
    }
    AnimationManager.prototype.getZoomManager = function () {
        return this.zoomManager;
    };
    /**
     * Set the zoom manager, to get the scale of the current game.
     *
     * @param zoomManager the zoom manager
     */
    AnimationManager.prototype.setZoomManager = function (zoomManager) {
        this.zoomManager = zoomManager;
    };
    AnimationManager.prototype.getSettings = function () {
        return this.settings;
    };
    /**
     * Returns if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
     *
     * @returns if the animations are active.
     */
    AnimationManager.prototype.animationsActive = function () {
        return document.visibilityState !== 'hidden' && !this.game.instantaneousMode;
    };
    /**
     * Plays an animation if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
     *
     * @param animation the animation to play
     * @returns the animation promise.
     */
    AnimationManager.prototype.play = function (animation) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q;
        return __awaiter(this, void 0, void 0, function () {
            var settings, _r;
            return __generator(this, function (_s) {
                switch (_s.label) {
                    case 0:
                        animation.played = animation.playWhenNoAnimation || this.animationsActive();
                        if (!animation.played) return [3 /*break*/, 2];
                        settings = animation.settings;
                        (_a = settings.animationStart) === null || _a === void 0 ? void 0 : _a.call(settings, animation);
                        (_b = settings.element) === null || _b === void 0 ? void 0 : _b.classList.add((_c = settings.animationClass) !== null && _c !== void 0 ? _c : 'bga-animations_animated');
                        animation.settings = __assign({ duration: (_g = (_e = (_d = animation.settings) === null || _d === void 0 ? void 0 : _d.duration) !== null && _e !== void 0 ? _e : (_f = this.settings) === null || _f === void 0 ? void 0 : _f.duration) !== null && _g !== void 0 ? _g : 500, scale: (_l = (_j = (_h = animation.settings) === null || _h === void 0 ? void 0 : _h.scale) !== null && _j !== void 0 ? _j : (_k = this.zoomManager) === null || _k === void 0 ? void 0 : _k.zoom) !== null && _l !== void 0 ? _l : undefined }, animation.settings);
                        _r = animation;
                        return [4 /*yield*/, animation.animationFunction(this, animation)];
                    case 1:
                        _r.result = _s.sent();
                        (_o = (_m = animation.settings).animationEnd) === null || _o === void 0 ? void 0 : _o.call(_m, animation);
                        (_p = settings.element) === null || _p === void 0 ? void 0 : _p.classList.remove((_q = settings.animationClass) !== null && _q !== void 0 ? _q : 'bga-animations_animated');
                        return [3 /*break*/, 3];
                    case 2: return [2 /*return*/, Promise.resolve(animation)];
                    case 3: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Plays multiple animations in parallel.
     *
     * @param animations the animations to play
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playParallel = function (animations) {
        return __awaiter(this, void 0, void 0, function () {
            var _this = this;
            return __generator(this, function (_a) {
                return [2 /*return*/, Promise.all(animations.map(function (animation) { return _this.play(animation); }))];
            });
        });
    };
    /**
     * Plays multiple animations in sequence (the second when the first ends, ...).
     *
     * @param animations the animations to play
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playSequence = function (animations) {
        return __awaiter(this, void 0, void 0, function () {
            var result, others;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!animations.length) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.play(animations[0])];
                    case 1:
                        result = _a.sent();
                        return [4 /*yield*/, this.playSequence(animations.slice(1))];
                    case 2:
                        others = _a.sent();
                        return [2 /*return*/, __spreadArray([result], others, true)];
                    case 3: return [2 /*return*/, Promise.resolve([])];
                }
            });
        });
    };
    /**
     * Plays multiple animations with a delay between each animation start.
     *
     * @param animations the animations to play
     * @param delay the delay (in ms)
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playWithDelay = function (animations, delay) {
        return __awaiter(this, void 0, void 0, function () {
            var promise;
            var _this = this;
            return __generator(this, function (_a) {
                promise = new Promise(function (success) {
                    var promises = [];
                    var _loop_1 = function (i) {
                        setTimeout(function () {
                            promises.push(_this.play(animations[i]));
                            if (i == animations.length - 1) {
                                Promise.all(promises).then(function (result) {
                                    success(result);
                                });
                            }
                        }, i * delay);
                    };
                    for (var i = 0; i < animations.length; i++) {
                        _loop_1(i);
                    }
                });
                return [2 /*return*/, promise];
            });
        });
    };
    /**
     * Attach an element to a parent, then play animation from element's origin to its new position.
     *
     * @param animation the animation function
     * @param attachElement the destination parent
     * @returns a promise when animation ends
     */
    AnimationManager.prototype.attachWithAnimation = function (animation, attachElement) {
        var attachWithAnimation = new BgaAttachWithAnimation({
            animation: animation,
            attachElement: attachElement
        });
        return this.play(attachWithAnimation);
    };
    return AnimationManager;
}());
define({
    // utils functions
    shouldAnimate: shouldAnimate,
    getDeltaCoordinates: getDeltaCoordinates,
    // animation functions
    BgaSlideAnimation: BgaSlideAnimation,
    BgaShowScreenCenterAnimation: BgaShowScreenCenterAnimation,
    BgaPauseAnimation: BgaPauseAnimation,
    BgaCumulatedAnimation: BgaCumulatedAnimation,
    BgaAttachWithAnimation: BgaAttachWithAnimation,
});

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
    /** The seed slides from its house to the destination and is attached to it (the same as before the client moved to the Game class). */
    move(seed, destination) {
        const slide = new BgaSlideAnimation({ element: seed, duration: SEED_DURATION, rotationDelta: 0 });
        return this.game.animationManager.play(new BgaAttachWithAnimation({ animation: slide, where: 'beforeend', attachElement: destination }));
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
    /** A new round started: the number counts up, and the next notification (the reseeding) waits a moment. */
    async setRound(round) {
        this.roundCounter.toValue(round);
        await delayTime(PAUSE);
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

class Game {
    constructor(bga) {
        this.bga = bga;
        this.animationManager = new AnimationManager(this.bga.gameui);
        // The board, the seeds and the page background are CSS images: load them now, so they do not pop in.
        this.bga.images.preloadImages(['table.png', 'seeds.png', 'background.png']);
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
        await this.tableManager.setRound(args.round);
    }
    async notif_moveStorehouseSeeds(args) {
        await this.seedManager.reseed(args);
    }
    async notif_lockedHouses(args) {
        this.houseManager.lock(args.playerId, args.lockeds);
    }
}

export { Game };
