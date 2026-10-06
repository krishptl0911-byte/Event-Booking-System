/*
 * qr.js - a small QR code generator written in plain JavaScript (no library).
 *
 * It makes a "Version 1" QR code (21 x 21 squares) with error-correction level L.
 * That can store up to 17 characters, enough for our booking code like EVT-9F3A1C7B.
 *
 * Steps:
 *   1. Turn the text into bits (byte mode) and pad to 19 data bytes
 *   2. Add 7 Reed-Solomon error-correction bytes (maths in GF(256))
 *   3. Draw the fixed patterns (3 corner squares, timing lines)
 *   4. Place the data bits in a zigzag pattern
 *   5. Try all 8 masks, keep the one with the lowest penalty
 *   6. Write the format bits (error level + mask number) and draw on <canvas>
 */
function drawQR(text, canvas, scale) {
  var SIZE = 21, DATA_BYTES = 19, ECC_BYTES = 7;
  scale = scale || 8;

  // ---------- Step 1: text -> data bytes ----------
  var bytes = [];
  for (var i = 0; i < text.length; i++) bytes.push(text.charCodeAt(i) & 255);
  if (bytes.length > 17) throw new Error('Text too long for this small QR');

  var bits = [];
  function put(value, length) {                 // push `length` bits of value
    for (var b = length - 1; b >= 0; b--) bits.push((value >>> b) & 1);
  }
  put(4, 4);                                    // mode: byte mode = 0100
  put(bytes.length, 8);                         // character count
  bytes.forEach(function (v) { put(v, 8); });   // the characters
  var cap = DATA_BYTES * 8;
  put(0, Math.min(4, cap - bits.length));       // terminator
  while (bits.length % 8) bits.push(0);         // fill to a whole byte
  var pad = [0xEC, 0x11], p = 0;
  while (bits.length < cap) put(pad[p++ % 2], 8); // pad bytes

  var data = [];
  for (var k = 0; k < bits.length; k += 8) {
    var byte = 0;
    for (var m = 0; m < 8; m++) byte = (byte << 1) | bits[k + m];
    data.push(byte);
  }

  // ---------- Step 2: Reed-Solomon error correction ----------
  var EXP = [], LOG = [], x = 1;                // GF(256) tables, polynomial 0x11D
  for (var n = 0; n < 255; n++) {
    EXP[n] = x; LOG[x] = n;
    x <<= 1; if (x & 256) x ^= 0x11D;
  }
  function mul(a, b) { return (a && b) ? EXP[(LOG[a] + LOG[b]) % 255] : 0; }

  var gen = [1];                                // generator polynomial
  for (var d = 0; d < ECC_BYTES; d++) {
    var next = new Array(gen.length + 1).fill(0);
    for (var g = 0; g < gen.length; g++) {
      next[g] ^= gen[g];
      next[g + 1] ^= mul(gen[g], EXP[d]);
    }
    gen = next;
  }
  var ecc = new Array(ECC_BYTES).fill(0);       // polynomial long division
  data.forEach(function (byteVal) {
    var factor = byteVal ^ ecc.shift();
    ecc.push(0);
    for (var e = 0; e < ECC_BYTES; e++) ecc[e] ^= mul(gen[e + 1], factor);
  });
  var codewords = data.concat(ecc);             // 19 data + 7 ecc = 26 bytes

  // ---------- Step 3: fixed patterns ----------
  var mod = [], isFn = [];                      // mod = dark?  isFn = fixed square?
  for (var r = 0; r < SIZE; r++) { mod.push(new Array(SIZE).fill(false)); isFn.push(new Array(SIZE).fill(false)); }
  function setFn(col, row, dark) { mod[row][col] = dark; isFn[row][col] = true; }

  for (var t = 0; t < SIZE; t++) {              // timing lines
    setFn(6, t, t % 2 === 0);
    setFn(t, 6, t % 2 === 0);
  }
  function finder(cx, cy) {                     // 7x7 corner square + white border
    for (var dy = -4; dy <= 4; dy++) for (var dx = -4; dx <= 4; dx++) {
      var dist = Math.max(Math.abs(dx), Math.abs(dy));
      var xx = cx + dx, yy = cy + dy;
      if (xx >= 0 && xx < SIZE && yy >= 0 && yy < SIZE) setFn(xx, yy, dist !== 2 && dist !== 4);
    }
  }
  finder(3, 3); finder(SIZE - 4, 3); finder(3, SIZE - 4);

  // reserve the format-bit squares (real values written in step 6)
  function drawFormat(maskNo) {
    var fdata = (1 << 3) | maskNo;              // level L = 01, then 3 mask bits
    var rem = fdata;
    for (var q = 0; q < 10; q++) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
    var f = ((fdata << 10) | rem) ^ 0x5412;
    function bit(i) { return ((f >>> i) & 1) !== 0; }
    for (var a = 0; a <= 5; a++) setFn(8, a, bit(a));
    setFn(8, 7, bit(6)); setFn(8, 8, bit(7)); setFn(7, 8, bit(8));
    for (var c = 9; c < 15; c++) setFn(14 - c, 8, bit(c));
    for (var s = 0; s < 8; s++) setFn(SIZE - 1 - s, 8, bit(s));
    for (var u = 8; u < 15; u++) setFn(8, SIZE - 15 + u, bit(u));
    setFn(8, SIZE - 8, true);                   // the always-dark square
  }
  drawFormat(0);

  // ---------- Step 4: place data bits in zigzag ----------
  var idx = 0;
  for (var right = SIZE - 1; right >= 1; right -= 2) {
    if (right === 6) right = 5;                 // skip the vertical timing line
    for (var vert = 0; vert < SIZE; vert++) {
      for (var j = 0; j < 2; j++) {
        var xc = right - j;
        var upward = ((right + 1) & 2) === 0;
        var yc = upward ? SIZE - 1 - vert : vert;
        if (!isFn[yc][xc] && idx < codewords.length * 8) {
          mod[yc][xc] = ((codewords[idx >>> 3] >>> (7 - (idx & 7))) & 1) !== 0;
          idx++;
        }
      }
    }
  }

  // ---------- Step 5: masks ----------
  function maskHit(no, col, row) {
    switch (no) {
      case 0: return (col + row) % 2 === 0;
      case 1: return row % 2 === 0;
      case 2: return col % 3 === 0;
      case 3: return (col + row) % 3 === 0;
      case 4: return (Math.floor(col / 3) + Math.floor(row / 2)) % 2 === 0;
      case 5: return col * row % 2 + col * row % 3 === 0;
      case 6: return (col * row % 2 + col * row % 3) % 2 === 0;
      default: return ((col + row) % 2 + col * row % 3) % 2 === 0;
    }
  }
  function flip(no) {                           // XOR mask over data squares (run twice = undo)
    for (var yy = 0; yy < SIZE; yy++) for (var xx = 0; xx < SIZE; xx++)
      if (!isFn[yy][xx] && maskHit(no, xx, yy)) mod[yy][xx] = !mod[yy][xx];
  }
  function penalty() {
    var score = 0, dark = 0, y, x, run, i;
    for (var pass = 0; pass < 2; pass++) {      // pass 0 = rows, pass 1 = columns
      for (var a = 0; a < SIZE; a++) {
        var line = [];
        for (var b = 0; b < SIZE; b++) line.push(pass ? mod[b][a] : mod[a][b]);
        run = 1;
        for (i = 1; i < SIZE; i++) {            // rule 1: long same-colour runs
          if (line[i] === line[i - 1]) { run++; if (run === 5) score += 3; else if (run > 5) score++; }
          else run = 1;
        }
        var s = line.map(function (v) { return v ? '1' : '0'; }).join('');
        var pos = -1;                           // rule 3: finder-like pattern
        while ((pos = s.indexOf('10111010000', pos + 1)) !== -1) score += 40;
        pos = -1;
        while ((pos = s.indexOf('00001011101', pos + 1)) !== -1) score += 40;
      }
    }
    for (y = 0; y < SIZE; y++) for (x = 0; x < SIZE; x++) {
      if (mod[y][x]) dark++;
      if (x < SIZE - 1 && y < SIZE - 1 &&       // rule 2: 2x2 blocks
          mod[y][x] === mod[y][x + 1] && mod[y][x] === mod[y + 1][x] && mod[y][x] === mod[y + 1][x + 1]) score += 3;
    }
    var percent = dark * 100 / (SIZE * SIZE);   // rule 4: dark/light balance
    score += Math.floor(Math.abs(percent - 50) / 5) * 10;
    return score;
  }

  var bestMask = 0, bestScore = Infinity;
  for (var mk = 0; mk < 8; mk++) {
    flip(mk); drawFormat(mk);
    var sc = penalty();
    if (sc < bestScore) { bestScore = sc; bestMask = mk; }
    flip(mk);
  }
  flip(bestMask);

  // ---------- Step 6: final format bits + draw ----------
  drawFormat(bestMask);
  var quiet = 4;                                // white border around the code
  canvas.width = canvas.height = (SIZE + quiet * 2) * scale;
  var ctx = canvas.getContext('2d');
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = '#000000';
  for (var ry = 0; ry < SIZE; ry++) for (var rx = 0; rx < SIZE; rx++)
    if (mod[ry][rx]) ctx.fillRect((rx + quiet) * scale, (ry + quiet) * scale, scale, scale);
  return mod;                                   // returned so it can be tested
}
