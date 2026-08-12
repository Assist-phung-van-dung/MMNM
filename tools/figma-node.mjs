#!/usr/bin/env node
/**
 * figma-node.mjs — doc MOT node Figma va in ra cay bo cuc de code theo.
 *
 *   node tools/figma-node.mjs                 # doc FIGMA_NODE_TARGET trong .env
 *   node tools/figma-node.mjs 4231:941        # doc node chi dinh
 *   node tools/figma-node.mjs 4231:941 --png  # kem xuat anh ra design/figma/
 *   node tools/figma-node.mjs 4231:941 --depth 6
 *   node tools/figma-node.mjs 4231:941 --json # ghi ban JSON goc de tra cuu
 *
 * Doc lai tu JSON da luu, KHONG goi API (khong bao gio bi chan toc do):
 *   node tools/figma-node.mjs 6027:4122 --from design/figma/4231-852.json
 *
 * Khi endpoint doc node bi chan (429) thi xuat anh roi do tren anh —
 * endpoint /v1/images KHONG bi chan:
 *   node tools/figma-node.mjs 6376:6322 --chi-anh --scale 1
 *
 * CHI DOC. Figma REST API khong sua duoc noi dung thiet ke.
 *
 * Vi sao chi doc mot node kem --depth: endpoint doc node bi chan toc do (429)
 * tinh theo file va tai khoan, keo ca trang la dinh ngay. Xem
 * docs/07-ban-giao.md muc "Figma - diem quan trong nhat".
 */
import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');

async function env() {
  let tuTep = {};
  try {
    const raw = await fs.readFile(path.join(ROOT, '.env'), 'utf8');
    tuTep = Object.fromEntries(
      raw.split(/\r?\n/)
        .filter(l => l.trim() && !l.trim().startsWith('#'))
        .map(l => {
          const i = l.indexOf('=');
          return [l.slice(0, i).trim(), l.slice(i + 1).trim()];
        })
    );
  } catch {
    // Chua co .env cung khong sao neu da truyen bien moi truong.
  }

  /*
   * BIEN MOI TRUONG THANG .env.
   *
   * Vi sao can: Figma chan toc do (429) tinh theo file + TAI KHOAN, nen cach
   * duy nhat di tiep la muon token cua tai khoan khac. Ghi token muon duoc
   * xuong .env thi no nam lai tren dia sau khi dung xong, va lan sau chay
   * bang token nguoi khac ma khong biet. Truyen mot lan:
   *
   *   docker compose run --rm -e FIGMA_TOKEN=figd_... --entrypoint node figma \
   *     tools/figma-node.mjs 6376:6322 --depth 2
   *
   * Chi nhan bien co gia tri that, de `-e FIGMA_TOKEN=` rong khong xoa mat
   * gia tri trong .env.
   */
  for (const khoa of ['FIGMA_TOKEN', 'FIGMA_FILE_KEY', 'FIGMA_NODE_TARGET']) {
    if (process.env[khoa]) tuTep[khoa] = process.env[khoa];
  }

  return tuTep;
}

const hex = c =>
  '#' + [c.r, c.g, c.b].map(v => Math.round(v * 255).toString(16).padStart(2, '0')).join('').toUpperCase();

/** Mau nen/chu dau tien nhin thay duoc cua node */
function fillOf(node) {
  for (const f of node.fills || []) {
    if (f.visible === false) continue;
    if (f.type === 'SOLID') return hex(f.color) + ((f.opacity ?? 1) < 1 ? `@${(f.opacity).toFixed(2)}` : '');
    if (f.type.startsWith('GRADIENT')) return f.type.replace('GRADIENT_', 'grad-').toLowerCase();
    if (f.type === 'IMAGE') return 'anh';
  }
  return null;
}

function round(n) { return n == null ? null : Math.round(n); }

/** Mot dong mo ta node: kich thuoc, bo cuc, mau, chu */
function describe(node) {
  const b = node.absoluteBoundingBox || {};
  const bits = [];

  const w = round(b.width), h = round(b.height);
  if (w != null) bits.push(`${w}x${h}`);

  if (node.layoutMode && node.layoutMode !== 'NONE') {
    const dir = node.layoutMode === 'HORIZONTAL' ? 'ngang' : 'doc';
    const pad = [node.paddingTop, node.paddingRight, node.paddingBottom, node.paddingLeft].map(v => round(v) || 0);
    const padTxt = pad.some(v => v) ? ` dem:${pad.join('/')}` : '';
    const gap = round(node.itemSpacing) ? ` cach:${round(node.itemSpacing)}` : '';
    bits.push(`flex-${dir}${gap}${padTxt}`);
    if (node.primaryAxisAlignItems) bits.push(`chinh:${node.primaryAxisAlignItems}`);
    if (node.counterAxisAlignItems) bits.push(`cheo:${node.counterAxisAlignItems}`);
  }

  const fill = fillOf(node);
  if (fill) bits.push(`nen:${fill}`);

  if (node.cornerRadius != null) bits.push(`bo:${round(node.cornerRadius)}`);
  else if (node.rectangleCornerRadii) bits.push(`bo:${node.rectangleCornerRadii.map(round).join('/')}`);

  for (const s of node.strokes || []) {
    if (s.type === 'SOLID' && s.visible !== false) {
      bits.push(`vien:${hex(s.color)} ${round(node.strokeWeight) || 1}px`);
      break;
    }
  }

  for (const fx of node.effects || []) {
    if (fx.visible === false) continue;
    if (fx.type === 'DROP_SHADOW') {
      const c = fx.color;
      bits.push(`bong:${round(fx.offset.x)}/${round(fx.offset.y)}/${round(fx.radius)} rgba(${round(c.r * 255)},${round(c.g * 255)},${round(c.b * 255)},${c.a.toFixed(2)})`);
    } else if (fx.type === 'BACKGROUND_BLUR' || fx.type === 'LAYER_BLUR') {
      bits.push(`mo:${round(fx.radius)}`);
    }
  }

  const st = node.style;
  if (st) {
    const font = [st.fontFamily, st.fontWeight, `${round(st.fontSize)}px`].filter(Boolean).join(' ');
    const lh = round(st.lineHeightPx) ? `/${round(st.lineHeightPx)}` : '';
    const ls = st.letterSpacing ? ` chu-cach:${st.letterSpacing.toFixed(2)}` : '';
    const al = st.textAlignHorizontal && st.textAlignHorizontal !== 'LEFT' ? ` canh:${st.textAlignHorizontal}` : '';
    const tc = st.textCase && st.textCase !== 'ORIGINAL' ? ` ${st.textCase}` : '';
    bits.push(`chu:${font}${lh}${ls}${al}${tc}`);
  }

  if (node.opacity != null && node.opacity < 1) bits.push(`mo-duc:${node.opacity.toFixed(2)}`);
  if (node.visible === false) bits.push('AN');

  return bits.join('  ');
}

function tree(node, out, prefix = '', isLast = true, depth = 0, maxDepth = 99) {
  const branch = depth === 0 ? '' : (isLast ? '`- ' : '|- ');
  out.push(`${prefix}${branch}${node.name}  [${node.type} ${node.id}]`);

  const childPrefix = depth === 0 ? '' : prefix + (isLast ? '   ' : '|  ');
  const info = describe(node);
  if (info) out.push(`${childPrefix}${node.children?.length ? '|' : ' '}    ${info}`);
  if (node.type === 'TEXT' && node.characters != null) {
    out.push(`${childPrefix}     "${node.characters.replace(/\n/g, '\\n')}"`);
  }

  if (depth >= maxDepth) {
    if (node.children?.length) out.push(`${childPrefix}   ... (${node.children.length} con nua, tang --depth de xem)`);
    return;
  }
  const kids = node.children || [];
  kids.forEach((c, i) => tree(c, out, childPrefix, i === kids.length - 1, depth + 1, maxDepth));
}

async function main() {
  const argv = process.argv.slice(2);
  const flag = n => argv.includes(n);
  const val = (n, d) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : d; };

  const e = await env();
  const file = e.FIGMA_FILE_KEY;
  const token = e.FIGMA_TOKEN;
  if (!token || !file) throw new Error('Thieu FIGMA_TOKEN / FIGMA_FILE_KEY trong .env');

  const idArg = argv.find(a => /^\d+[:-]\d+$/.test(a));
  const id = (idArg || e.FIGMA_NODE_TARGET || '').replace('-', ':');
  if (!id) throw new Error('Chua chi dinh node. Truyen vao vi du 4231:941, hoac dat FIGMA_NODE_TARGET trong .env');

  const depth = Number(val('--depth', 4));

  // --- Doc tu file JSON da luu: khong dung API, khong bi chan toc do ---
  const from = val('--from', null);
  if (from) {
    const saved = JSON.parse(await fs.readFile(path.resolve(ROOT, from), 'utf8'));
    let found = null;
    (function seek(n) {
      if (found) return;
      if (n.id === id) { found = n; return; }
      for (const c of n.children || []) seek(c);
    })(saved);
    if (!found) throw new Error(`Khong thay node ${id} trong ${from}`);
    const out = [];
    tree(found, out, '', true, 0, depth);
    process.stdout.write(out.join('\n') + '\n');
    return;
  }

  /*
   * --chi-anh: bo qua han buoc doc node, chi xuat PNG. Dung khi endpoint
   * doc node dang bi chan toc do (429) — gioi han tinh theo file va tai
   * khoan, doi token khong go duoc. Xem docs/07-ban-giao.md.
   */
  const chiAnh = flag('--chi-anh');
  if (chiAnh) {
    await xuatAnh(file, token, id, val('--scale', '2'));
    return;
  }

  /*
   * DUNG /v1/files/:key?ids=... CHU KHONG DUNG /v1/files/:key/nodes
   *
   * Hai endpoint tra ve cung du lieu node, nhung han muc tinh RIENG cho
   * tung endpoint. Do ngay 10/08/2026 tren chinh file nay:
   *   /v1/files/:key/nodes  -> 429, header retry-after = 374553s (4,3 ngay)
   *   /v1/files/:key?ids=   -> 200 OK
   * Doi token cung tai khoan khong go duoc 429 (da thu), nhung doi endpoint
   * thi duoc. Dung sang duong nay la khong con phai do pixel tren anh xuat.
   *
   * KHAC BIET PHAI BU: `depth` o day dem tu GOC TAI LIEU chu khong tu node
   * yeu cau — document(1) -> page(2) -> node(3). Cong them 2 de nguoi dung
   * van truyen `--depth` theo nghia "sau bao nhieu tang KE TU node".
   */
  process.stdout.write(`Doc node ${id} (depth ${depth}) ...\n\n`);
  const res = await fetch(
    `https://api.figma.com/v1/files/${file}?ids=${encodeURIComponent(id)}&depth=${depth + 2}`,
    { headers: { 'X-Figma-Token': token } }
  );
  if (res.status === 429) {
    throw new Error('Figma chan toc do (429) o CA endpoint /v1/files?ids. Dung --chi-anh roi do tren anh xuat.');
  }
  if (!res.ok) throw new Error(`Figma API ${res.status}: ${(await res.text()).slice(0, 300)}`);

  const data = await res.json();
  // Endpoint nay tra ve ca cay tai lieu da tia bot, phai tu di tim node.
  const root = (function seek(n) {
    if (!n) return null;
    if (n.id === id) return n;
    for (const c of n.children || []) {
      const t = seek(c);
      if (t) return t;
    }
    return null;
  })(data.document);
  if (!root) throw new Error(`Khong tim thay node ${id} trong file ${file}`);

  const out = [];
  tree(root, out, '', true, 0, depth);
  process.stdout.write(out.join('\n') + '\n');

  if (flag('--json')) {
    const dir = path.join(ROOT, 'design/figma');
    await fs.mkdir(dir, { recursive: true });
    const f = path.join(dir, `${id.replace(':', '-')}.json`);
    await fs.writeFile(f, JSON.stringify(root, null, 2), 'utf8');
    process.stdout.write(`\n-> ${path.relative(ROOT, f)}\n`);
  }

  if (flag('--png')) {
    await xuatAnh(file, token, id, val('--scale', '2'));
  }
}

/**
 * Xuat mot node ra PNG. Tach ham rieng vi con duoc goi o che do --chi-anh.
 *
 * @param {string} file  Khoa file Figma.
 * @param {string} token Token doc.
 * @param {string} id    Id node, dang 1234:5678.
 * @param {string} scale He so phong to.
 */
async function xuatAnh(file, token, id, scale) {
  const r = await fetch(
    `https://api.figma.com/v1/images/${file}?ids=${encodeURIComponent(id)}&format=png&scale=${scale}`,
    { headers: { 'X-Figma-Token': token } }
  );
  if (!r.ok) throw new Error(`Figma images ${r.status}: ${(await r.text()).slice(0, 200)}`);
  const j = await r.json();
  const url = j.images[id];
  if (!url) throw new Error('Figma khong tra ve anh cho node nay');
  const bin = Buffer.from(await (await fetch(url)).arrayBuffer());
  const dir = path.join(ROOT, 'design/figma');
  await fs.mkdir(dir, { recursive: true });
  const f = path.join(dir, `${id.replace(':', '-')}@${scale}x.png`);
  await fs.writeFile(f, bin);
  process.stdout.write(`-> ${path.relative(ROOT, f)}  (${(bin.length / 1024).toFixed(0)} KB)\n`);
}

main().catch(err => { console.error('LOI:', err.message); process.exit(1); });
