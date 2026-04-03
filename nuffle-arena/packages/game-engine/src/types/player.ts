import type { PlayerId, TeamId, Position } from './common';
import type { Skill } from './skill';

/** Blood Bowl player characteristics */
export interface PlayerStats {
  /** Movement Allowance — squares the player can move per turn */
  readonly ma: number;
  /** Strength — used for block dice calculation */
  readonly st: number;
  /** Agility — target number for agility rolls (dodge, catch, pickup) */
  readonly ag: number;
  /** Passing — target number for pass rolls */
  readonly pa: number | null;
  /** Armour Value — target number to break armour */
  readonly av: number;
}

/** Status of a player on the pitch */
export type PlayerStatus =
  | 'standing'
  | 'prone'
  | 'stunned'
  | 'ko'
  | 'casualty'
  | 'sent_off';

/** A player on the field during a match */
export interface Player {
  readonly id: PlayerId;
  readonly teamId: TeamId;
  readonly name: string;
  readonly number: number;
  readonly position: Position | null;
  readonly stats: PlayerStats;
  readonly skills: readonly Skill[];
  readonly status: PlayerStatus;
  /** Movement allowance remaining this turn */
  readonly maRemaining: number;
  /** Whether this player has already performed an action this turn */
  readonly hasActed: boolean;
}

/** Player definition from a roster (template, not in-game instance) */
export interface PlayerTemplate {
  readonly name: string;
  readonly stats: PlayerStats;
  readonly skills: readonly Skill[];
  readonly cost: number;
  /** Maximum number of this positional allowed on a team */
  readonly max: number;
}
