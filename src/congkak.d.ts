interface CongkakGame extends Game {
  gamedatas: CongkakGamedatas;

  games: CongkakGames;

  scoreCtrl: Record<string, Counter>;

  animationManager: AnimationManager;

  counters: Record<string, Record<string, Counter>>;
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

interface CongkakGames {
  sowing: Sowing;
}

interface PlayerSeedingState {
  locations: Record<string, { location: string; playerId: string }>;
}

interface PlayersSeeding {
  movements: Record<string, { location: string; playerId: string }[]>;
  initialHouse: Record<string, { location: string; playerId: string }>;
  maxSeeds: number;
}
