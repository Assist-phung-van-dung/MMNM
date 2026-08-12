#!/usr/bin/env node
/**
 * png-do.mjs — đọc PNG thuần và ĐO trên ảnh, không cần thư viện ngoài.
 *
 * Dùng khi endpoint đọc node của Figma bị chặn tốc độ (429): xuất PNG rồi
 * đo pixel là cách đi vòng đã dùng thành công, xem docs/07-ban-giao.md.
 *
 *   node tools/png-do.mjs <file.png> --diem 100,250
 *       Màu tại một điểm.
 *
 *   node tools/png-do.mjs <file.png> --cot 40
 *       Quét dọc theo cột x=40, in ra các đoạn màu liền nhau
 *       -> ranh giới và CHIỀU CAO từng khối trên trang.
 *
 *   node tools/png-do.mjs <file.png> --hang 300
 *       Quét ngang theo hàng y=300 -> ranh giới và BỀ RỘNG các cột.
 *
 * Tuỳ chọn:
 *   --min 12      bỏ qua đoạn ngắn hơn 12px (lọc nhiễu viền/chữ)
 *   --sai 6       hai màu lệch nhau <= 6/255 mỗi kênh thì coi là cùng màu
 *   --ty-le 1     ảnh xuất ở scale mấy (chia toạ độ về đơn vị thiết kế)
 *
 * CHỈ ĐỌC. Không sửa ảnh, không sửa thiết kế.
 */
import fs from 'node:fs/promises';
import zlib from 'node:zlib';

/** Giải mã PNG 8-bit (colour type 2 = RGB, 6 = RGBA) về mảng pixel thuần. */
function giaiMaPng(buf) {
	if (buf.readUInt32BE(0) !== 0x89504e47) throw new Error('Không phải tệp PNG');

	let pos = 8;
	let rong = 0, cao = 0, sauBit = 0, loaiMau = 0;
	const idat = [];

	while (pos < buf.length) {
		const len = buf.readUInt32BE(pos);
		const loai = buf.toString('ascii', pos + 4, pos + 8);
		const data = buf.subarray(pos + 8, pos + 8 + len);

		if (loai === 'IHDR') {
			rong = data.readUInt32BE(0);
			cao = data.readUInt32BE(4);
			sauBit = data[8];
			loaiMau = data[9];
			if (data[12] !== 0) throw new Error('PNG xen kẽ (interlaced) — chưa hỗ trợ');
		} else if (loai === 'IDAT') {
			idat.push(data);
		} else if (loai === 'IEND') {
			break;
		}
		pos += 12 + len;
	}

	if (sauBit !== 8) throw new Error(`Chỉ hỗ trợ PNG 8-bit, tệp này ${sauBit}-bit`);
	const kenh = loaiMau === 6 ? 4 : loaiMau === 2 ? 3 : 0;
	if (!kenh) throw new Error(`Chỉ hỗ trợ colour type 2/6, tệp này ${loaiMau}`);

	const raw = zlib.inflateSync(Buffer.concat(idat));
	const buocHang = rong * kenh;
	const px = Buffer.alloc(cao * buocHang);

	// Gỡ bộ lọc từng dòng theo đặc tả PNG.
	let o = 0;
	for (let y = 0; y < cao; y++) {
		const loc = raw[o++];
		const dong = raw.subarray(o, o + buocHang);
		o += buocHang;
		const ra = px.subarray(y * buocHang, (y + 1) * buocHang);
		const tren = y > 0 ? px.subarray((y - 1) * buocHang, y * buocHang) : null;

		for (let i = 0; i < buocHang; i++) {
			const x = dong[i];
			const a = i >= kenh ? ra[i - kenh] : 0;          // trái
			const b = tren ? tren[i] : 0;                     // trên
			const c = tren && i >= kenh ? tren[i - kenh] : 0;  // chéo trên-trái
			let v;
			switch (loc) {
				case 0: v = x; break;
				case 1: v = x + a; break;
				case 2: v = x + b; break;
				case 3: v = x + ((a + b) >> 1); break;
				case 4: {
					const p = a + b - c;
					const pa = Math.abs(p - a), pb = Math.abs(p - b), pc = Math.abs(p - c);
					v = x + (pa <= pb && pa <= pc ? a : pb <= pc ? b : c);
					break;
				}
				default: throw new Error(`Bộ lọc PNG lạ: ${loc}`);
			}
			ra[i] = v & 0xff;
		}
	}

	return { rong, cao, kenh, px };
}

const hex = (r, g, b) =>
	'#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();

function layDiem(anh, x, y) {
	const i = (y * anh.rong + x) * anh.kenh;
	return [anh.px[i], anh.px[i + 1], anh.px[i + 2]];
}

const gan = (a, b, sai) =>
	Math.abs(a[0] - b[0]) <= sai && Math.abs(a[1] - b[1]) <= sai && Math.abs(a[2] - b[2]) <= sai;

/** Gom các pixel liền nhau cùng màu thành đoạn. */
function gomDoan(layMau, tong, sai, min) {
	const doan = [];
	let dau = 0;
	let mau = layMau(0);

	for (let i = 1; i <= tong; i++) {
		const m = i < tong ? layMau(i) : null;
		if (m === null || !gan(m, mau, sai)) {
			const dai = i - dau;
			if (dai >= min) doan.push({ tu: dau, den: i - 1, dai, mau: hex(...mau) });
			dau = i;
			mau = m;
		}
	}
	return doan;
}

async function main() {
	const argv = process.argv.slice(2);
	const val = (n, d) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : d; };
	const tep = argv.find(a => a.toLowerCase().endsWith('.png'));
	if (!tep) throw new Error('Thiếu đường dẫn tệp .png');

	const anh = giaiMaPng(await fs.readFile(tep));
	const sai = Number(val('--sai', 6));
	const min = Number(val('--min', 12));
	const tyLe = Number(val('--ty-le', 1));
	const q = n => Math.round(n / tyLe);

	process.stdout.write(`${tep}\nẢnh ${anh.rong}x${anh.cao}` +
		(tyLe !== 1 ? `  (tỷ lệ ${tyLe} -> thiết kế ${q(anh.rong)}x${q(anh.cao)})` : '') + '\n\n');

	const diem = val('--diem', null);
	if (diem) {
		const [x, y] = diem.split(',').map(Number);
		const m = layDiem(anh, x, y);
		process.stdout.write(`Điểm (${x}, ${y}) = ${hex(...m)}  rgb(${m.join(', ')})\n`);
		return;
	}

	const cot = val('--cot', null);
	if (cot !== null) {
		const x = Number(cot);
		process.stdout.write(`Quét DỌC tại x=${x} (bỏ đoạn < ${min}px, sai số ${sai})\n`);
		process.stdout.write(`   y bắt đầu   y kết thúc   cao    màu\n`);
		for (const d of gomDoan(y => layDiem(anh, x, y), anh.cao, sai, min)) {
			process.stdout.write(
				`${String(q(d.tu)).padStart(10)}${String(q(d.den)).padStart(13)}${String(q(d.dai)).padStart(7)}    ${d.mau}\n`
			);
		}
		return;
	}

	const hang = val('--hang', null);
	if (hang !== null) {
		const y = Number(hang);
		process.stdout.write(`Quét NGANG tại y=${y} (bỏ đoạn < ${min}px, sai số ${sai})\n`);
		process.stdout.write(`   x bắt đầu   x kết thúc   rộng   màu\n`);
		for (const d of gomDoan(x => layDiem(anh, x, y), anh.rong, sai, min)) {
			process.stdout.write(
				`${String(q(d.tu)).padStart(10)}${String(q(d.den)).padStart(13)}${String(q(d.dai)).padStart(7)}    ${d.mau}\n`
			);
		}
		return;
	}

	process.stdout.write('Chưa chọn phép đo. Dùng --diem x,y | --cot x | --hang y\n');
}

main().catch(err => { console.error('LỖI:', err.message); process.exit(1); });
