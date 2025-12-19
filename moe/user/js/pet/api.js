/**
 * API Communication Layer
 * MOE Pet System
 * 
 * Handles all API calls to the backend router
 */

import { API_BASE } from './config.js';

// TODO: Extract API functions from main pet.js
// This will contain all fetch() calls for clean separation

export async function apiRequest(action, method = 'GET', body = null) {
    const url = method === 'GET' && body
        ? `${API_BASE}?${new URLSearchParams(body)}`
        : API_BASE;

    const options = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };

    if (method === 'POST' && body) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url);
    return await response.json();
}

// Pet APIs
export async function fetchPets() {
    // TODO: Implement
}

export async function fetchActivePet() {
    // TODO: Implement
}

export async function selectPetAPI(petId) {
    // TODO: Implement
}

// More API functions to be added...
