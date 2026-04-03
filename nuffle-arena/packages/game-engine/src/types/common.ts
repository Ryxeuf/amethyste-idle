/** Unique identifier for game entities */
export type PlayerId = string;
export type TeamId = string;
export type MatchId = string;

/** Position on the Blood Bowl pitch (26 wide x 15 tall) */
export interface Position {
  readonly x: number;
  readonly y: number;
}

/** Cardinal + diagonal directions */
export type Direction = 'N' | 'NE' | 'E' | 'SE' | 'S' | 'SW' | 'W' | 'NW';

/** D6 result (1-6) */
export type D6 = 1 | 2 | 3 | 4 | 5 | 6;

/** D8 result (1-8) */
export type D8 = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8;

/** Block dice faces */
export type BlockDieResult =
  | 'pow'
  | 'stumble'
  | 'push'
  | 'skull'
  | 'both_down';
