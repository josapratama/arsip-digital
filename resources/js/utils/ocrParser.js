/**
 * OCR Auto-detect utility
 * Detects document number, date, and title from OCR text
 */

// ── Indonesian Month Mapping ─────────────────────────────────────────────────
const BULAN_ID = {
    januari: '01', jan: '01', january: '01',
    februari: '02', feb: '02', pebruari: '02', peb: '02', eebruari: '02', eeb: '02', febr: '02', february: '02',
    maret: '03', mar: '03', mrt: '03', march: '03',
    april: '04', apr: '04',
    mei: '05', may: '05',
    juni: '06', jun: '06', june: '06',
    juli: '07', jul: '07', july: '07',
    agustus: '08', agu: '08', ags: '08', agst: '08', agust: '08', august: '08',
    september: '09', sep: '09', sept: '09',
    oktober: '10', okt: '10', october: '10',
    november: '11', nov: '11', nopember: '11', nop: '11',
    desember: '12', des: '12', december: '12',
};

const MONTH_NAMES_REGEX = Object.keys(BULAN_ID)
    .sort((a, b) => b.length - a.length)
    .join('|');

// ── Nomor Surat Patterns ─────────────────────────────────────────────────────
// Matches Indonesian official letter patterns:
//   400.1.2.2/ 326 /SKR-RB / 2026
//   001/DS-SKR/I/2026
//   No. 001/PKH/VI/2025
//   Nomor: 12/KADES/2024
//   Nomor « O/Sk/TP PKK Desa-Pog/ 172021 Bangngan, 10 Eebruari 2021
const NOMOR_SURAT_PATTERNS = [
    // Line starting with Nomor/No followed by number code
    /(?:nomor|no\.?|nomer)\s*[:\s«_=-]\s*([A-Za-z0-9][A-Za-z0-9./\-_ ()]{3,60}\d{4})/i,
    /(?:nomor|no\.?|nomer)\s*[:\s«_=-]\s*([^\n\r]{3,70})/i,
    /\b(\d{1,4}[\/.][A-Za-z0-9.\-_]{1,20}[\/.](?:[IVX]{1,4}[\/.])?\d{4})\b/i,
];

function cleanNomorSurat(raw) {
    if (!raw) return '';
    return raw
        // Remove trailing date / city e.g. "Panongan, 10 Februari 2021" or "Bangngan, 10 Eebruari 2021"
        .replace(new RegExp(`\\s+(?:[A-Za-z. -]+[\\s,]+)?\\d{1,2}\\s+(?:${MONTH_NAMES_REGEX})\\s+\\d{4}.*$`, 'i'), '')
        // Remove trailing letter metadata labels
        .replace(/\s+(lampiran|perihal|berihal|hal|sifat|kepada|yth).*$/i, '')
        // Remove leading punctuation artifacts
        .replace(/^[«=_:\s.-]+/, '')
        // Fix common OCR digit/letter mixups at start of number
        .replace(/^O(\d)/, '0$1')
        .replace(/^Ol\//i, '01/')
        .replace(/[^\w\s./\-()]/g, '')
        .replace(/\s+/g, ' ')
        .replace(/\s*\/\s*/g, '/')
        .trim();
}

// ── Tanggal Patterns ─────────────────────────────────────────────────────────
const TANGGAL_PATTERNS = [
    // "10 Februari 2021", "10 Eebruari 2021", "14 Oktober 2026", "14 Okt 2026"
    new RegExp(`\\b(\\d{1,2})\\s+(${MONTH_NAMES_REGEX})\\s+(\\d{4})\\b`, 'i'),
    // "2026-10-14" ISO
    /\b(\d{4})-(\d{2})-(\d{2})\b/,
    // "14/10/2026" or "14-10-2026"
    /\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/,
];

/**
 * Parse OCR text and extract document metadata
 * @param {string} text - Raw OCR text
 * @returns {{ nomorSurat: string, tanggal: string, judul: string }}
 */
export function parseOcrText(text) {
    if (!text || text.trim().length < 5) {
        return { nomorSurat: '', tanggal: '', judul: '' };
    }

    const result = {
        documentNumber: '',
        nomorSurat: '',
        tanggal: '',
        judul: '',
    };

    // ── Detect Nomor Surat / Document Number ──────────────────────────────────
    for (const pattern of NOMOR_SURAT_PATTERNS) {
        const match = text.match(pattern);
        if (match) {
            const rawVal = match[1] || match[0];
            const cleaned = cleanNomorSurat(rawVal);
            if (cleaned.length >= 3) {
                result.documentNumber = cleaned;
                result.nomorSurat = cleaned;
                break;
            }
        }
    }

    // ── Detect Tanggal ───────────────────────────────────────────────────────
    for (const pattern of TANGGAL_PATTERNS) {
        const match = text.match(pattern);
        if (match) {
            if (pattern === TANGGAL_PATTERNS[0]) {
                const day   = match[1].padStart(2, '0');
                const month = BULAN_ID[match[2].toLowerCase()] || '01';
                const year  = match[3];
                result.tanggal = `${year}-${month}-${day}`;
            } else if (pattern === TANGGAL_PATTERNS[1]) {
                result.tanggal = match[0];
            } else {
                const d = match[1].padStart(2, '0');
                const m = match[2].padStart(2, '0');
                const y = match[3];
                result.tanggal = `${y}-${m}-${d}`;
            }
            break;
        }
    }

    // ── Detect Judul / Perihal ───────────────────────────────────────────────
    const perihalMatch = text.match(/(?:perihal|berihal|hal|subject|tentang)\s*[:\s«_=-]\s*([^\n\r]+)(?:\r?\n\s*([^\n\r]+))?/i);
    if (perihalMatch) {
        const line1 = (perihalMatch[1] || '').trim();
        const line2 = (perihalMatch[2] || '').trim();
        let combined = line1;
        if (
            line2 &&
            !line2.match(/^(?:kepada|yth|di|nomor|lampiran|lamp|sifat|menimbang|mengingat|dengan)/i) &&
            line2.length > 2 &&
            line2.length < 80
        ) {
            combined += ' ' + line2;
        }
        result.judul = combined
            .replace(/\s+/g, ' ')
            .replace(/[^\w\s,.()\-\/]/g, '')
            .trim()
            .substring(0, 120);
    } else {
        const suratMatch = text.match(/\b(surat\s+(?:keputusan|keterangan|undangan|pemberitahuan|permohonan|tugas|izin|pengantar|edaran)[^\n\r]{0,80})/i);
        if (suratMatch) {
            result.judul = suratMatch[1]
                .replace(/\s+/g, ' ')
                .replace(/[^\w\s,.()\-\/]/g, '')
                .trim()
                .substring(0, 120);
        }
    }

    return result;
}
