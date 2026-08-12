#!/usr/bin/env node
/**
 * figma-sync.mjs — keo design token tu Figma ve, sinh tokens.json + tokens.css
 *
 *   node tools/figma-sync.mjs            # dong bo trang CODING
 *   node tools/figma-sync.mjs --raw      # giu lai ban JSON goc de tra cuu
 *
 * CHI DOC. Figma REST API khong co endpoint nao sua duoc noi dung thiet ke.
 * Khi khach gui them man hinh moi, chay lai lenh nay roi doi chieu.
 */
import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');

async function env() {
  const raw = await fs.readFile(path.join(ROOT, '.env'), 'utf8');
  return Object.fromEntries(
    raw.split(/\r?\n/)
      .filter(l => l.trim() && !l.trim().startsWith('#'))
      .map(l => {
        const i = l.indexOf('=');
        return [l.slice(0, i).trim(), l.slice(i + 1).trim()];
      })
  );
}

const hex = c =>
  '#' + [c.r, c.g, c.b].map(v => Math.round(v * 255).toString(16).padStart(2, '0')).join('').toUpperCase();

/** Ten goi nho cho tung ma mau — doi chieu voi bang trong docs/04-kien-truc.md */
const COLOR_NAMES = {
  '#F7F1DE': 'kem',
  '#F0EEE9': 'nga',
  '#FFFFFF': 'trang',
  '#FCFDFE': 'trang-lanh',
  '#3F3B3B': 'muc',
  '#4F4F4F': 'muc-nhat',
  '#333333': 'than',
  '#000000': 'den',
  '#747766': 'reu',
  '#AAAE99': 'reu-dam',
  '#B4B7A7': 'reu-nhat',
  '#C4ADA7': 'dat-hong',
  '#A47764': 'nau-dat',
  '#FEBE98': 'dao',
  '#1F4E79': 'cham',
  '#F0E7C9': 'kem-dam',
  '#E0E0E0': 'vien',
  '#C4C4C4': 'xam',
  '#D9D9D9': 'xam-nhat',
};

function walk(node, visit, parent = null) {
  visit(node, parent);
  for (const child of node.children || []) walk(child, visit, node);
}

async function main() {
  const e = await env();
  const token = e.FIGMA_TOKEN;
  const file = e.FIGMA_FILE_KEY;
  const page = e.FIGMA_PAGE_CODING;
  if (!token || !file || !page) throw new Error('Thieu FIGMA_TOKEN / FIGMA_FILE_KEY / FIGMA_PAGE_CODING trong .env');

  process.stdout.write(`Doc Figma page ${page} ...\n`);
  const res = await fetch(
    `https://api.figma.com/v1/files/${file}/nodes?ids=${encodeURIComponent(page)}`,
    { headers: { 'X-Figma-Token': token } }
  );
  if (!res.ok) throw new Error(`Figma API ${res.status}: ${await res.text()}`);
  const data = await res.json();
  const root = Object.values(data.nodes)[0].document;

  const colors = new Map();      // hex -> so lan dung
  const textStyles = new Map();  // khoa -> {family,weight,size,lh,count}
  const radii = new Map();
  const spacing = new Map();
  const shadows = new Map();
  const componentSets = [];

  walk(root, node => {
    for (const f of [...(node.fills || []), ...(node.strokes || [])]) {
      if (f.type === 'SOLID' && f.visible !== false && (f.opacity ?? 1) > 0.9) {
        const h = hex(f.color);
        colors.set(h, (colors.get(h) || 0) + 1);
      }
    }
    const s = node.style;
    if (s && node.type === 'TEXT') {
      const key = `${s.fontFamily}|${s.fontWeight}|${Math.round(s.fontSize)}|${Math.round(s.lineHeightPx || 0)}`;
      const prev = textStyles.get(key);
      textStyles.set(key, {
        family: s.fontFamily, weight: s.fontWeight,
        size: Math.round(s.fontSize), lh: Math.round(s.lineHeightPx || 0),
        count: (prev?.count || 0) + 1,
      });
    }
    if (node.cornerRadius != null) radii.set(node.cornerRadius, (radii.get(node.cornerRadius) || 0) + 1);
    if (node.layoutMode === 'HORIZONTAL' || node.layoutMode === 'VERTICAL') {
      for (const k of ['paddingTop', 'paddingBottom', 'paddingLeft', 'paddingRight']) {
        const v = Math.round(node[k] || 0);
        if (v) spacing.set(v, (spacing.get(v) || 0) + 1);
      }
      const g = Math.round(node.itemSpacing || 0);
      if (g) spacing.set(g, (spacing.get(g) || 0) + 1);
    }
    for (const fx of node.effects || []) {
      if (fx.type === 'DROP_SHADOW' && fx.visible !== false) {
        const c = fx.color;
        const v = `${Math.round(fx.offset.x)}px ${Math.round(fx.offset.y)}px ${Math.round(fx.radius)}px rgba(${Math.round(c.r * 255)},${Math.round(c.g * 255)},${Math.round(c.b * 255)},${c.a.toFixed(2)})`;
        shadows.set(v, (shadows.get(v) || 0) + 1);
      }
    }
    if (node.type === 'COMPONENT_SET') {
      componentSets.push({ name: node.name, variants: (node.children || []).map(c => c.name) });
    }
  });

  const bycount = m => [...m.entries()].sort((a, b) => b[1] - a[1]);

  const tokens = {
    generatedFrom: { file, page, at: new Date().toISOString() },
    colors: bycount(colors).map(([value, count]) => ({ name: COLOR_NAMES[value] || null, value, count })),
    text: [...textStyles.values()].sort((a, b) => b.count - a.count),
    radius: bycount(radii),
    spacing: bycount(spacing),
    shadows: bycount(shadows),
    componentSets,
  };

  await fs.mkdir(path.join(ROOT, 'design'), { recursive: true });
  await fs.writeFile(path.join(ROOT, 'design/tokens.json'), JSON.stringify(tokens, null, 2), 'utf8');

  // tokens.css — chi sinh phan mau tu Figma; thang chu va khoang cach da duoc curate tay
  const named = tokens.colors.filter(c => c.name);
  const unnamed = tokens.colors.filter(c => !c.name && c.count >= 5);
  const css = `/* SINH TU DONG boi tools/figma-sync.mjs — DUNG SUA TAY.
   Nguon: Figma ${file} / page ${page}
   Sua mau thi sua trong Figma roi chay lai: node tools/figma-sync.mjs */

:root {
${named.map(c => `  --nntm-c-${c.name}: ${c.value};`).join('\n')}
}
${unnamed.length ? `
/* Mau chua duoc dat ten — kiem tra xem co phai mau thuong hieu that khong,
   neu phai thi them vao COLOR_NAMES trong tools/figma-sync.mjs:
${unnamed.map(c => ` * ${c.value}  (dung ${c.count} lan)`).join('\n')}
 */` : ''}
`;
  await fs.mkdir(path.join(ROOT, 'wp-content/themes/nntm/assets/css'), { recursive: true });
  await fs.writeFile(path.join(ROOT, 'wp-content/themes/nntm/assets/css/tokens.generated.css'), css, 'utf8');

  process.stdout.write(
    `Xong.\n` +
    `  mau       : ${tokens.colors.length} (da dat ten ${named.length})\n` +
    `  kieu chu  : ${tokens.text.length}\n` +
    `  component : ${componentSets.length} bo\n` +
    `  -> design/tokens.json\n` +
    `  -> wp-content/themes/nntm/assets/css/tokens.generated.css\n`
  );
}

main().catch(err => { console.error('LOI:', err.message); process.exit(1); });
