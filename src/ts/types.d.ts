interface CongkakPlayer extends Player {}

/** A house of a player: `kampong_1` to `kampong_7`, or `rumah`. The player is the owner, as a string like in the database. */
interface CongkakHouse {
  location: string;
  playerId: string;
}

interface CongkakGamedatas extends Gamedatas<CongkakPlayer> {
  /** The player ids as strings: the player looking at the game first (bottom of the board), the opponent second (top). */
  playerPosition: string[];
  houseList: Record<string, { rumah: number; kampong: Record<string, number> }>;
  /** Per player, per location, whether the kampong is burnt (docs/RULES.md, section 9). */
  houseListLockeds: Record<string, Record<string, boolean>>;
  /** `total` is null when the game has no limit of rounds. */
  roundDetails: { current: number; total: number | null };
}

/** The houses each player may choose, in the state where both choose at the same time (modules/php/States/PlayersSeeding.php). */
interface PlayersSeedingArgs {
  locations: Record<string, CongkakHouse[]>;
}

/** The houses the active player may choose (modules/php/States/PlayerSeeding.php). */
interface PlayerSeedingArgs {
  locations: CongkakHouse[];
}

interface PlayersSeedingNotifArgs {
  /** Per player, the houses each seed lands in, in order. */
  movements: Record<string, CongkakHouse[]>;
  initialHouse: Record<string, CongkakHouse>;
  maxSeeds: number;
}

interface MoveAllToRumahNotifArgs {
  location: string;
  playerId: string;
  opponentLocation: string;
  opponentPlayerId: string;
}

interface NewRoundNotifArgs {
  round: number;
}

interface MoveRemainingSeedsNotifArgs {
  playerId: string;
}

interface MoveStorehouseSeedsNotifArgs {
  playerId: string;
  /** How many seeds go to each kampong. */
  movements: Record<string, number>;
  /** The seeds that leave the game (docs/RULES.md, section 8). */
  outwardMovementsCount: number;
}

interface LockedHousesNotifArgs {
  playerId: string;
  lockeds: string[];
}
