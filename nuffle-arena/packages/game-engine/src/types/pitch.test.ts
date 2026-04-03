import { describe, it, expect } from 'vitest';
import {
  isInBounds,
  isEndzone,
  isLineOfScrimmage,
  isWideZone,
  getAdjacentPositions,
  distance,
  PITCH_WIDTH,
  PITCH_HEIGHT,
} from './pitch';

describe('Pitch', () => {
  describe('isInBounds', () => {
    it('returns true for valid positions', () => {
      expect(isInBounds({ x: 0, y: 0 })).toBe(true);
      expect(isInBounds({ x: 13, y: 7 })).toBe(true);
      expect(isInBounds({ x: PITCH_WIDTH - 1, y: PITCH_HEIGHT - 1 })).toBe(true);
    });

    it('returns false for out-of-bounds positions', () => {
      expect(isInBounds({ x: -1, y: 0 })).toBe(false);
      expect(isInBounds({ x: 0, y: -1 })).toBe(false);
      expect(isInBounds({ x: PITCH_WIDTH, y: 0 })).toBe(false);
      expect(isInBounds({ x: 0, y: PITCH_HEIGHT })).toBe(false);
    });
  });

  describe('isEndzone', () => {
    it('identifies the left endzone', () => {
      expect(isEndzone({ x: 0, y: 7 })).toBe('left');
    });

    it('identifies the right endzone', () => {
      expect(isEndzone({ x: PITCH_WIDTH - 1, y: 7 })).toBe('right');
    });

    it('returns null for non-endzone positions', () => {
      expect(isEndzone({ x: 5, y: 7 })).toBeNull();
      expect(isEndzone({ x: 13, y: 3 })).toBeNull();
    });
  });

  describe('isLineOfScrimmage', () => {
    it('identifies line of scrimmage columns', () => {
      expect(isLineOfScrimmage({ x: 12, y: 5 })).toBe(true);
      expect(isLineOfScrimmage({ x: 13, y: 5 })).toBe(true);
    });

    it('returns false for other columns', () => {
      expect(isLineOfScrimmage({ x: 11, y: 5 })).toBe(false);
      expect(isLineOfScrimmage({ x: 14, y: 5 })).toBe(false);
    });
  });

  describe('isWideZone', () => {
    it('identifies wide zone rows', () => {
      expect(isWideZone({ x: 5, y: 0 })).toBe(true);
      expect(isWideZone({ x: 5, y: 3 })).toBe(true);
      expect(isWideZone({ x: 5, y: 11 })).toBe(true);
      expect(isWideZone({ x: 5, y: 14 })).toBe(true);
    });

    it('returns false for non-wide-zone rows', () => {
      expect(isWideZone({ x: 5, y: 4 })).toBe(false);
      expect(isWideZone({ x: 5, y: 7 })).toBe(false);
      expect(isWideZone({ x: 5, y: 10 })).toBe(false);
    });
  });

  describe('getAdjacentPositions', () => {
    it('returns 8 adjacent positions for a center cell', () => {
      const adj = getAdjacentPositions({ x: 10, y: 7 });
      expect(adj).toHaveLength(8);
    });

    it('returns 3 adjacent positions for a corner cell', () => {
      const adj = getAdjacentPositions({ x: 0, y: 0 });
      expect(adj).toHaveLength(3);
      expect(adj).toContainEqual({ x: 1, y: 0 });
      expect(adj).toContainEqual({ x: 0, y: 1 });
      expect(adj).toContainEqual({ x: 1, y: 1 });
    });

    it('filters out out-of-bounds positions', () => {
      const adj = getAdjacentPositions({ x: 0, y: 7 });
      adj.forEach((pos) => {
        expect(isInBounds(pos)).toBe(true);
      });
    });
  });

  describe('distance', () => {
    it('returns 0 for same position', () => {
      expect(distance({ x: 5, y: 5 }, { x: 5, y: 5 })).toBe(0);
    });

    it('returns Chebyshev distance', () => {
      expect(distance({ x: 0, y: 0 }, { x: 3, y: 4 })).toBe(4);
      expect(distance({ x: 2, y: 3 }, { x: 5, y: 3 })).toBe(3);
      expect(distance({ x: 0, y: 0 }, { x: 1, y: 1 })).toBe(1);
    });
  });
});
