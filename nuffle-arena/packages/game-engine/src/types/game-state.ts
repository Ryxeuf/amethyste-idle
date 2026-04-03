import type { MatchId, PlayerId, Position } from './common';
import type { Team } from './team';

/** Phases of a Blood Bowl match */
export type MatchPhase =
  | 'coin_toss'
  | 'setup'
  | 'kickoff'
  | 'turn'
  | 'touchdown'
  | 'halftime'
  | 'end';

/** Which half of the match */
export type Half = 1 | 2;

/** Weather conditions affecting the match */
export type Weather =
  | 'nice'
  | 'sweltering_heat'
  | 'very_sunny'
  | 'pouring_rain'
  | 'blizzard';

/** The complete state of a Blood Bowl match — immutable */
export interface GameState {
  readonly id: MatchId;
  readonly teams: readonly [Team, Team];
  readonly phase: MatchPhase;
  readonly half: Half;
  readonly activeTeamIndex: 0 | 1;
  readonly weather: Weather;
  readonly ballPosition: Position | null;
  readonly ballCarrier: PlayerId | null;
  /** Whether a reroll has been used this turn */
  readonly rerollUsedThisTurn: boolean;
  /** Sequence of dice rolls for replay determinism */
  readonly seed: number;
  /** Action log for this match */
  readonly log: readonly GameEvent[];
}

/** Base interface for game events */
export interface GameEvent {
  readonly type: string;
  readonly timestamp: number;
}

/** Factory to create an initial GameState */
export function createInitialGameState(
  id: MatchId,
  homeTeam: Team,
  awayTeam: Team,
  seed: number,
): GameState {
  return {
    id,
    teams: [homeTeam, awayTeam],
    phase: 'coin_toss',
    half: 1,
    activeTeamIndex: 0,
    weather: 'nice',
    ballPosition: null,
    ballCarrier: null,
    rerollUsedThisTurn: false,
    seed,
    log: [],
  };
}
