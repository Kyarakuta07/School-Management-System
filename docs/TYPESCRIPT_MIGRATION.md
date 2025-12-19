# TypeScript Migration Consideration

## Overview

This document outlines the considerations for migrating the MOE School Management System's JavaScript codebase to TypeScript.

## Current State

- **JavaScript Files**: 10+ modules in `moe/user/js/pet/`
- **Build System**: Vite (already supports TypeScript)
- **Module System**: ES6 Modules
- **JSDoc**: Already documented

## Benefits of TypeScript

| Benefit | Impact |
|---------|--------|
| **Type Safety** | Catch errors at compile time, not runtime |
| **Better IDE Support** | IntelliSense, auto-completion, refactoring |
| **Self-Documenting** | Types serve as documentation |
| **Easier Refactoring** | Safe to rename, move, and restructure |
| **API Contracts** | Clear interfaces for API responses |

## Migration Strategy

### Phase 1: Setup (Low Risk)
1. Add TypeScript configuration
2. Configure Vite for TypeScript
3. Add type definitions for external libraries

### Phase 2: Gradual Migration (Low Risk)
1. Rename `.js` files to `.ts` one at a time
2. Add basic types (any → specific types)
3. Start with utility modules (`config.ts`, `state.ts`)

### Phase 3: Full Types (Medium Effort)
1. Define interfaces for API responses
2. Add strict type checking
3. Remove all `any` types

## Recommended Approach

**Gradual Migration** - Keep JavaScript and TypeScript side by side:

```
moe/user/js/pet/
├── config.ts        ← Migrated
├── state.ts         ← Migrated  
├── ui.js            ← Still JavaScript
├── pets.js          ← Still JavaScript
└── types/
    └── api.d.ts     ← Type definitions
```

## Type Definitions Needed

```typescript
// types/api.d.ts

interface Pet {
  id: number;
  user_id: number;
  species_id: number;
  display_name: string;
  level: number;
  exp: number;
  health: number;
  hunger: number;
  mood: number;
  is_shiny: boolean;
  shiny_hue?: number;
  status: 'ACTIVE' | 'SHELTERED' | 'DEAD';
}

interface ApiResponse<T> {
  success: boolean;
  error?: string;
  data?: T;
}

interface GachaResult {
  species: PetSpecies;
  rarity: 'Common' | 'Rare' | 'Epic' | 'Legendary';
  is_shiny: boolean;
  remaining_gold: number;
}
```

## Files to Migrate (Priority Order)

1. `config.js` → `config.ts` (constants only)
2. `state.js` → `state.ts` (state object)
3. `types/` → Create type definitions
4. `ui.js` → `ui.ts`
5. `pets.js` → `pets.ts`
6. Remaining modules

## Decision

☑️ **Recommended**: Add TypeScript support NOW, migrate gradually LATER

This means:
- Setup TypeScript config
- Create type definitions
- Keep existing JS files working
- Migrate one file at a time when convenient
