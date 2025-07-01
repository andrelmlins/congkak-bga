/**
 * Framework interfaces
 * Copied from https://github.com/thoun/kingoftokyo
 */

interface Game {
  setup: (gamedatas: any) => void;
  onEnteringState: (stateName: string, notif: any) => void;
  onLeavingState: (stateName: string) => void;
  onUpdateActionButtons: (stateName: string, args: any) => void;
  setupNotifications: () => void;
}

interface NotifBase {
  log: string;
  move_id: number;
  table_id: string;
  time: number;
  type: string;
  uid: string;
}

interface ArgsPrivate<T> {
  _private: T;
}

interface NotifPrivate<T> extends NotifBase {
  args: ArgsPrivate<T>;
}

interface Notif<T> extends NotifBase {
  args: T;
}

/* TODO repace Function by (..params) => void */
interface Dojo {
  place: (html: string | HTMLElement, node: string | HTMLElement | Element, position?: number | string) => void;
  style: Function;
  hitch: Function;
  hasClass: (node: string | HTMLElement, className: string) => boolean;
  addClass: (node: string | HTMLElement, className: string) => void;
  removeClass: (node: string | HTMLElement, className?: string) => void;
  toggleClass: (node: string | HTMLElement, className: string, forceValue?: boolean) => void;
  connect: Function;
  disconnect: Function;
  query: (query: string) => any; //HTMLElement[]; with some more functions
  forEach: Function;
  subscribe: Function;
  string: any;
  fx: {
    slideTo: (params: {
      node: HTMLElement;
      top: number;
      left: number;
      delay: number;
      duration: number;
      unit: string;
    }) => any;
  };
  animateProperty: (params: { node: string; properties: any }) => any;
  marginBox: Function;
  fadeIn: Function;
  trim: Function;
  stopEvent: (evt) => void;
  destroy: (node: string | HTMLElement | Element) => void;
  position: (obj: HTMLElement, includeScroll?: boolean) => { w: number; h: number; x: number; y: number };
  clone: (obj: HTMLElement) => HTMLElement;
  removeAttr: (obj: HTMLElement, attr: string) => void;
  fadeOut: (obj: { node: HTMLElement; duration: number }) => { play: () => void };
}

interface Player {
  beginner: boolean;
  color: string;
  color_back: any | null;
  eliminated: number;
  id: string;
  is_ai: string;
  name: string;
  score: string;
  zombie: number;
}

interface Counter {
  create: (nodeId: string) => void;
  getValue: () => number;
  incValue: (by: number) => void;
  setValue: (value: number) => void;
  toValue: (value: number) => void;
  disable: () => void;
}
