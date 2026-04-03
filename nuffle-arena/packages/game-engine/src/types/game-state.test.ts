import { describe, it, expect } from 'vitest';
import { createInitialGameState } from './game-state';
import type { Team } from './team';

function makeTeam(id: string, name: string): Team {
  return {
    id,
    name,
    race: 'human',
    players: [],
    rerolls: 3,
    maxRerolls: 3,
    score: 0,
    turn: 0,
  };
}

describe('GameState', () => {
  describe('createInitialGameState', () => {
    it('creates a valid initial state', () => {
      const home = makeTeam('home-1', 'Reikland Reavers');
      const away = makeTeam('away-1', 'Gouged Eye');

      const state = createInitialGameState('match-1', home, away, 42);

      expect(state.id).toBe('match-1');
      expect(state.teams).toHaveLength(2);
      expect(state.teams[0].name).toBe('Reikland Reavers');
      expect(state.teams[1].name).toBe('Gouged Eye');
      expect(state.phase).toBe('coin_toss');
      expect(state.half).toBe(1);
      expect(state.activeTeamIndex).toBe(0);
      expect(state.weather).toBe('nice');
      expect(state.ballPosition).toBeNull();
      expect(state.ballCarrier).toBeNull();
      expect(state.rerollUsedThisTurn).toBe(false);
      expect(state.seed).toBe(42);
      expect(state.log).toEqual([]);
    });
  });
});
