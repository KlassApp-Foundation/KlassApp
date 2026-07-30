/**
 * Shared Vue event bus. Kept outside app.js so ESM components can import
 * without circular TDZ (app.js imports components that need the bus).
 */
import Vue from './vue-compat';

export const bus = new Vue();
