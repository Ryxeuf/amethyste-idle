import { describe, it, expect } from 'vitest';
import { getSkillCategory } from './skill';

describe('Skill', () => {
  describe('getSkillCategory', () => {
    it('categorizes general skills', () => {
      expect(getSkillCategory('block')).toBe('general');
      expect(getSkillCategory('dodge')).toBe('general');
      expect(getSkillCategory('tackle')).toBe('general');
    });

    it('categorizes agility skills', () => {
      expect(getSkillCategory('catch')).toBe('agility');
      expect(getSkillCategory('leap')).toBe('agility');
      expect(getSkillCategory('sprint')).toBe('agility');
    });

    it('categorizes strength skills', () => {
      expect(getSkillCategory('mighty_blow')).toBe('strength');
      expect(getSkillCategory('guard')).toBe('strength');
      expect(getSkillCategory('claw')).toBe('mutation');
    });

    it('categorizes passing skills', () => {
      expect(getSkillCategory('accurate')).toBe('passing');
      expect(getSkillCategory('safe_pass')).toBe('passing');
    });

    it('categorizes mutation skills', () => {
      expect(getSkillCategory('big_hand')).toBe('mutation');
      expect(getSkillCategory('horns')).toBe('mutation');
      expect(getSkillCategory('tentacles')).toBe('mutation');
    });
  });
});
