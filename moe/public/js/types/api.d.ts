/**
 * MOE Pet System - Type Definitions
 * 
 * Type definitions for the pet system API responses and data structures.
 * These types can be used in both TypeScript and JavaScript (with JSDoc).
 */

// ================================================
// USER & AUTH
// ================================================

interface User {
    id_nethera: number;
    username: string;
    email: string;
    nama: string;
    gold: number;
    status_akun: 'Aktif' | 'Nonaktif' | 'Banned';
    foto_path?: string;
}

// ================================================
// PET SYSTEM
// ================================================

interface PetSpecies {
    id: number;
    name: string;
    element: 'Fire' | 'Water' | 'Earth' | 'Air' | 'Light' | 'Dark' | 'Neutral';
    base_hp: number;
    base_atk: number;
    base_def: number;
    rarity_weight: number;
    evolves_to?: number;
    evolve_level?: number;
    img_egg: string;
    img_baby: string;
    img_teen: string;
    img_adult: string;
}

interface Pet {
    id: number;
    user_id: number;
    species_id: number;
    display_name: string;
    level: number;
    exp: number;
    exp_to_next: number;
    health: number;
    max_health: number;
    hunger: number;
    mood: number;
    atk: number;
    def: number;
    is_shiny: boolean;
    shiny_hue?: number;
    status: 'ACTIVE' | 'SHELTERED' | 'DEAD';
    is_active: boolean;
    created_at: string;
    last_fed?: string;
    last_played?: string;

    // Joined from species
    species_name?: string;
    element?: string;
    img_egg?: string;
    img_baby?: string;
    img_teen?: string;
    img_adult?: string;
}

type EvolutionStage = 'egg' | 'baby' | 'teen' | 'adult';

// ================================================
// SHOP & INVENTORY
// ================================================

interface ShopItem {
    id: number;
    name: string;
    description: string;
    price: number;
    effect_type: 'food' | 'potion' | 'revive' | 'exp_boost' | 'gacha_ticket' | 'shield';
    effect_value: number;
    img_path?: string;
    is_new?: boolean;
}

interface InventoryItem extends ShopItem {
    quantity: number;
    inventory_id: number;
}

// ================================================
// GACHA
// ================================================

interface GachaResult {
    success: boolean;
    species: PetSpecies;
    pet: Pet;
    rarity: 'Common' | 'Rare' | 'Epic' | 'Legendary';
    is_shiny: boolean;
    shiny_hue?: number;
    remaining_gold: number;
}

// ================================================
// BATTLE
// ================================================

interface BattleParticipant {
    pet_id: number;
    name: string;
    level: number;
    hp: number;
    max_hp: number;
    atk: number;
    def: number;
    element: string;
}

interface BattleLogEntry {
    round: number;
    actor: string;
    action: 'attack' | 'skill' | 'defend';
    damage: number;
    critical?: boolean;
}

interface BattleResult {
    success: boolean;
    winner_pet_id: number;
    attacker: BattleParticipant & { final_hp: number };
    defender: BattleParticipant & { final_hp: number };
    battle_log: BattleLogEntry[];
    rewards?: {
        gold: number;
        exp: number;
    };
}

// ================================================
// DAILY REWARDS
// ================================================

interface DailyRewardStatus {
    success: boolean;
    can_claim: boolean;
    current_streak: number;
    reward_amount: number;
    next_claim_time?: string;
}

// ================================================
// API RESPONSES
// ================================================

interface ApiResponse<T = unknown> {
    success: boolean;
    error?: string;
    error_code?: string;
    data?: T;
}

interface PetsResponse extends ApiResponse {
    pets: Pet[];
    count: number;
}

interface ActivePetResponse extends ApiResponse {
    pet: Pet | null;
    user_gold: number;
}

interface ShopResponse extends ApiResponse {
    items: ShopItem[];
    user_gold: number;
}

interface InventoryResponse extends ApiResponse {
    items: InventoryItem[];
    user_gold: number;
}

// ================================================
// APP STATE
// ================================================

interface AppState {
    currentTab: string;
    userPets: Pet[];
    activePet: Pet | null;
    shopItems: ShopItem[];
    userInventory: InventoryItem[];
    selectedItemType: string | null;
    currentBulkItem: InventoryItem | null;
    currentReviveItem: InventoryItem | null;
    currentShopItem: ShopItem | null;
    dailyRewardData: DailyRewardStatus | null;
    isGoldCompact: boolean;
}

// ================================================
// EXPORTS (for module usage)
// ================================================

export {
    User,
    PetSpecies,
    Pet,
    EvolutionStage,
    ShopItem,
    InventoryItem,
    GachaResult,
    BattleParticipant,
    BattleLogEntry,
    BattleResult,
    DailyRewardStatus,
    ApiResponse,
    PetsResponse,
    ActivePetResponse,
    ShopResponse,
    InventoryResponse,
    AppState
};
