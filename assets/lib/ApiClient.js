/**
 * ApiClient — client fetch pour l'API v1 (/api/v1/*).
 *
 * Comprend la convention d'enveloppe (voir docs/API_V1.md) :
 *   Succes : { success: true, data: {...} }
 *   Erreur : { success: false, error: { code, message, details? } }
 *
 * Les methodes resolvent avec `data` en cas de succes et rejettent avec une
 * ApiError (code, message, status, details) sinon — y compris pour les rejets
 * metier 409 action_rejected du combat.
 *
 * Usage :
 *   import { apiGet, apiPost, ApiError } from '../lib/ApiClient.js';
 *   const state = await apiGet('/api/v1/fight');
 *   try {
 *       await apiPost('/api/v1/fight/attack', { targetId, targetType: 'mob' });
 *   } catch (e) {
 *       if (e instanceof ApiError && e.code === 'action_rejected') { ... }
 *   }
 */

export class ApiError extends Error {
    constructor(code, message, status, details = null) {
        super(message);
        this.name = 'ApiError';
        this.code = code;
        this.status = status;
        this.details = details;
    }
}

async function request(url, options = {}) {
    let response;
    try {
        response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });
    } catch (networkError) {
        throw new ApiError('network_error', networkError.message, 0);
    }

    let payload = null;
    try {
        payload = await response.json();
    } catch {
        throw new ApiError('invalid_response', `Reponse non JSON (HTTP ${response.status})`, response.status);
    }

    if (payload && payload.success === true) {
        return payload.data;
    }

    const error = (payload && payload.error) || {};
    throw new ApiError(
        error.code || 'server_error',
        error.message || `Erreur HTTP ${response.status}`,
        response.status,
        error.details || null,
    );
}

export function apiGet(url) {
    return request(url, { method: 'GET' });
}

export function apiPost(url, body = null) {
    return request(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body !== null ? JSON.stringify(body) : null,
    });
}

export default { apiGet, apiPost, ApiError };
