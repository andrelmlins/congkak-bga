/*
BGA libs, loaded as ESM modules (see importEsmLib in bga-framework.d.ts). Import them with `import { BgaAnimations } from './libs';`.
Only what the game uses is typed here, instead of the full typing files of the libs (same as feyasswamp):
https://en.doc.boardgamearena.com/BgaAnimations
*/

interface BgaAnimationManager {
  /** Slides the element from where it is to the destination and attaches it there when the slide ends. `bump` is the scale of the element in the middle of the slide (1 for none). */
  slideAndAttach(element: HTMLElement, destination: HTMLElement, settings?: { duration?: number; bump?: number }): Promise<unknown>;
}

interface BgaAnimationsLib {
  Manager: new (settings: { animationsActive: () => boolean }) => BgaAnimationManager;
}

const BgaAnimations: BgaAnimationsLib = await globalThis.importEsmLib('bga-animations', '1.x');

export { BgaAnimations };
export type { BgaAnimationManager };
