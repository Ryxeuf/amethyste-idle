import type { TeamId } from './common';
import type { Player } from './player';

/** Supported Blood Bowl races */
export type Race = 'human' | 'orc' | 'skaven' | 'dwarf';

/** A team participating in a match */
export interface Team {
  readonly id: TeamId;
  readonly name: string;
  readonly race: Race;
  readonly players: readonly Player[];
  /** Rerolls remaining for this half */
  readonly rerolls: number;
  /** Total rerolls purchased */
  readonly maxRerolls: number;
  /** Score (touchdowns) */
  readonly score: number;
  /** Turn number within the current half (1-8) */
  readonly turn: number;
}
