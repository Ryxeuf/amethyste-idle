/** Blood Bowl skill categories */
export type SkillCategory =
  | 'general'
  | 'agility'
  | 'strength'
  | 'passing'
  | 'mutation';

/** Core Blood Bowl skills */
export type Skill =
  // General
  | 'block'
  | 'dodge'
  | 'sure_hands'
  | 'sure_feet'
  | 'pass_block'
  | 'frenzy'
  | 'kick'
  | 'tackle'
  | 'dauntless'
  | 'dirty_player'
  | 'fend'
  | 'leader'
  | 'pro'
  | 'strip_ball'
  | 'wrestle'
  // Agility
  | 'catch'
  | 'diving_catch'
  | 'diving_tackle'
  | 'jump_up'
  | 'leap'
  | 'side_step'
  | 'sneaky_git'
  | 'sprint'
  // Strength
  | 'break_tackle'
  | 'grab'
  | 'guard'
  | 'juggernaut'
  | 'mighty_blow'
  | 'multiple_block'
  | 'piling_on'
  | 'stand_firm'
  | 'strong_arm'
  | 'thick_skull'
  // Passing
  | 'accurate'
  | 'cannoneer'
  | 'dump_off'
  | 'hail_mary_pass'
  | 'nerves_of_steel'
  | 'safe_pass'
  // Mutation
  | 'big_hand'
  | 'claw'
  | 'disturbing_presence'
  | 'extra_arms'
  | 'foul_appearance'
  | 'horns'
  | 'prehensile_tail'
  | 'tentacles'
  | 'two_heads'
  | 'very_long_legs';

/** Map skill to its category */
export function getSkillCategory(skill: Skill): SkillCategory {
  const categories: Record<SkillCategory, readonly Skill[]> = {
    general: [
      'block', 'dodge', 'sure_hands', 'sure_feet', 'pass_block', 'frenzy',
      'kick', 'tackle', 'dauntless', 'dirty_player', 'fend', 'leader',
      'pro', 'strip_ball', 'wrestle',
    ],
    agility: [
      'catch', 'diving_catch', 'diving_tackle', 'jump_up', 'leap',
      'side_step', 'sneaky_git', 'sprint',
    ],
    strength: [
      'break_tackle', 'grab', 'guard', 'juggernaut', 'mighty_blow',
      'multiple_block', 'piling_on', 'stand_firm', 'strong_arm', 'thick_skull',
    ],
    passing: [
      'accurate', 'cannoneer', 'dump_off', 'hail_mary_pass',
      'nerves_of_steel', 'safe_pass',
    ],
    mutation: [
      'big_hand', 'claw', 'disturbing_presence', 'extra_arms',
      'foul_appearance', 'horns', 'prehensile_tail', 'tentacles',
      'two_heads', 'very_long_legs',
    ],
  };

  for (const [category, skills] of Object.entries(categories)) {
    if ((skills as readonly string[]).includes(skill)) {
      return category as SkillCategory;
    }
  }

  return 'general';
}
