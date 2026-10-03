import { test as base, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const AUTH_FILE = path.join(__dirname, '.auth', 'super-admin.json');

export const test = base.extend({
    storageState: AUTH_FILE,
});

export { expect };
