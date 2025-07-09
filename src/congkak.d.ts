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
  opponentPlayerId: string;
  houseListLockeds: Record<string, Record<string, boolean>>;
  roundDetails: { current: number; total: number | null };
}

interface CongkakGames {
  sowing: Sowing;
}

interface PlayersSeedingState {
  locations: Record<string, { location: string; playerId: string }[]>;
}

interface PlayerSeedingState {
  locations: { location: string; playerId: string }[];
}

interface PlayersSeedingNotif {
  movements: Record<string, { location: string; playerId: string }[]>;
  initialHouse: Record<string, { location: string; playerId: string }>;
  maxSeeds: number;
}

interface MoveAllToRumahNotif {
  location: string;
  playerId: string;
  opponentLocation: string;
  opponentPlayerId: string;
}

interface ScoreNotif {
  score: number;
  playerId: string;
}

interface NewRoundNotif {
  round: number;
}

interface MoveRemainingSeedsNotif {
  playerId: string;
}

interface MoveStorehouseSeedsNotif {
  playerId: string;
  movements: Record<string, number>;
  outwardMovementsCount: number;
}

interface LockedHousesNotif {
  playerId: string;
  lockeds: string[];
}
