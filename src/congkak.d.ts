interface CongkakGame extends Game {
  gamedatas: CongkakGamedatas;

  scoreCtrl: Record<string, Counter>;

  animationManager: AnimationManager;
}

interface CongkakGamedatas {
  gamestate: Gamestate;
  playerPosition: string[];
  gamestates: { [gamestateId: number]: Gamestate };
  players: Record<string, Player>;
  playerorder: (string | number)[];
  playerColors: Record<string, string>;
  houseList: Record<string, { rumah: number; kampong: Record<string, number> }>;
}
