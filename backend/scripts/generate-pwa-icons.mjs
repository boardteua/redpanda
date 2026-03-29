/**
 * T164: генерує PWA / Apple Touch / favicon PNG з джерельного значка.
 * Джерело: `public/brand/pwa-icon-source.png` (основний бренд-арт для PWA).
 * Запуск: `npm run pwa:icons` (потрібен devDependency `sharp`).
 */
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const backendRoot = path.resolve(__dirname, '..');
const srcPath = path.join(backendRoot, 'public/brand/pwa-icon-source.png');
const outDir = path.join(backendRoot, 'public/pwa');
/** Полотно maskable (зона безпеки): темний фон, щоб збігався з краями 3D-значка. */
const MASKABLE_CANVAS_BG = '#2a1814';

async function rasterForSize(innerPx) {
    return sharp(srcPath)
        .resize(innerPx, innerPx, {
            fit: 'cover',
            position: 'center',
        })
        .png()
        .toBuffer();
}

/** Maskable: логотип ~62% сторони полотна по центрі (зона безпеки під системні маски). */
async function writeMaskable(size, filename) {
    const inner = Math.round(size * 0.62);
    const fg = await rasterForSize(inner);
    await sharp({
        create: {
            width: size,
            height: size,
            channels: 4,
            background: MASKABLE_CANVAS_BG,
        },
    })
        .composite([{ input: fg, gravity: 'center' }])
        .png()
        .toFile(path.join(outDir, filename));
}

async function writeAny(size, filename) {
    const buf = await rasterForSize(size);
    await sharp(buf).png().toFile(path.join(outDir, filename));
}

async function main() {
    await fs.mkdir(outDir, { recursive: true });
    await fs.access(srcPath);

    await writeAny(192, 'icon-192.png');
    await writeAny(512, 'icon-512.png');
    await writeMaskable(192, 'icon-192-maskable.png');
    await writeMaskable(512, 'icon-512-maskable.png');
    await writeAny(180, 'apple-touch-icon-180.png');
    await writeAny(32, 'favicon-32.png');

    // Додаткові розміри для старих launcher / табів (легкі файли).
    await writeAny(48, 'icon-48.png');
    await writeAny(96, 'icon-96.png');

    console.log('Wrote PWA icons to', path.relative(backendRoot, outDir));
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
