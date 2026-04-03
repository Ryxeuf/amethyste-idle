import type { Position } from './common';

/** Blood Bowl pitch dimensions */
export const PITCH_WIDTH = 26;
export const PITCH_HEIGHT = 15;

/** Named zones on the pitch */
export type PitchZone =
  | 'left_endzone'
  | 'left_half'
  | 'right_half'
  | 'right_endzone'
  | 'wide_left'
  | 'wide_right';

/** Check if a position is within pitch bounds */
export function isInBounds(pos: Position): boolean {
  return pos.x >= 0 && pos.x < PITCH_WIDTH && pos.y >= 0 && pos.y < PITCH_HEIGHT;
}

/** Check if a position is in an endzone */
export function isEndzone(pos: Position): 'left' | 'right' | null {
  if (pos.x === 0) return 'left';
  if (pos.x === PITCH_WIDTH - 1) return 'right';
  return null;
}

/** Check if a position is on the line of scrimmage */
export function isLineOfScrimmage(pos: Position): boolean {
  return pos.x === 12 || pos.x === 13;
}

/** Check if a position is in a wide zone */
export function isWideZone(pos: Position): boolean {
  return pos.y <= 3 || pos.y >= 11;
}

/** Get all adjacent positions (including diagonals) */
export function getAdjacentPositions(pos: Position): readonly Position[] {
  const offsets = [
    { x: -1, y: -1 }, { x: 0, y: -1 }, { x: 1, y: -1 },
    { x: -1, y: 0 },                     { x: 1, y: 0 },
    { x: -1, y: 1 },  { x: 0, y: 1 },  { x: 1, y: 1 },
  ];

  return offsets
    .map((offset) => ({ x: pos.x + offset.x, y: pos.y + offset.y }))
    .filter(isInBounds);
}

/** Manhattan distance between two positions */
export function distance(a: Position, b: Position): number {
  return Math.max(Math.abs(a.x - b.x), Math.abs(a.y - b.y));
}
