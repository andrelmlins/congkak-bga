/** Only what the game uses of the fork of bga-animations (see bga-animations.js). */

interface BgaSlideSettings {
  element: HTMLElement;
  /** In ms. */
  duration?: number;
  rotationDelta?: number;
  scale?: number;
}

export class BgaSlideAnimation {
  constructor(settings: BgaSlideSettings);
}

export class BgaAttachWithAnimation {
  constructor(settings: { animation: BgaSlideAnimation; where: InsertPosition; attachElement: HTMLElement; afterAttach?: (element: HTMLElement) => void });
}

export class AnimationManager {
  /** `game` is the GameGui (it has getBoundingClientRectIgnoreZoom and instantaneousMode). */
  constructor(game: unknown);
  play(animation: BgaSlideAnimation | BgaAttachWithAnimation): Promise<unknown>;
}
