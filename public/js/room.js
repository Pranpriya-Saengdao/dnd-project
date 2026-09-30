(() => {
  const app = document.getElementById('app');
  const base = app.dataset.base, lobby = app.dataset.lobby;
  const $ = (id) => document.getElementById(id);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  let S = null, fetchedAt = 0, mode = null, busy = false, ovTimer = null;
  let combatFx = null;
  const previousHp = new Map(), hitUntil = new Map();

  async function api(path, body) {
    const r = await fetch(`${base}/${path}`, {
      method: body === undefined ? 'GET' : 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    if (r.status === 419) {
      // Login/logout regenerates the CSRF token. Recover stale game tabs by
      // refreshing the session token before allowing another game action.
      location.reload();
      throw new Error('Your session was refreshed. Reloading the game...');
    }
    const j = await r.json().catch(() => ({ error: 'Server error' }));
    if (!r.ok) throw new Error(j.error || j.message || 'Something went wrong');
    return j;
  }

  function toast(msg) {
    const t = $('toast');
    t.textContent = msg; t.style.display = 'block';
    clearTimeout(t._t); t._t = setTimeout(() => (t.style.display = 'none'), 2600);
  }

  async function act(path, body, then) {
    if (busy) return;
    busy = true;
    try {
      const res = await api(path, body || {});
      if (then) await then(res);
    } catch (e) { toast(e.message); }
    busy = false;
    poll();
  }

  function overlay(html, ms = 1800) {
    $('ovc').innerHTML = html;
    $('overlay').classList.add('show');
    clearTimeout(ovTimer);
    ovTimer = setTimeout(() => $('overlay').classList.remove('show'), ms);
  }
  $('overlay').onclick = () => $('overlay').classList.remove('show');

  const bigDice = (n) => `<div class="dice rolling">${n}</div>`;

  /* ---------------- polling ---------------- */
  async function poll() {
    try {
      S = await api('state');
      fetchedAt = Date.now();
      render();
    } catch (e) { /* ignore transient errors */ }
  }
  setInterval(poll, 1500);
  poll();

  /* ---------------- rendering ---------------- */
  function render() {
    const status = S.room.status;
    if (status === 'closed') return (location.href = lobby);
    $('waiting').style.display = status === 'waiting' ? 'grid' : 'none';
    $('game').style.display = status === 'waiting' ? 'none' : 'block';
    status === 'waiting' ? renderWaiting() : renderGame();
  }

  function chatHtml(list) {
    return list.map((c) => {
      const name = esc(c.name || c.sender || 'Player');
      const text = esc(c.text || c.message || '');
      const time = c.time ? `<span style="color:#a8a29e;font-size:11px">[${esc(c.time)}]</span> ` : '';
      const color = c.type === 'system' ? '#eab308' : (c.team === 'red' ? '#f87171' : (c.team === 'blue' ? '#60a5fa' : ''));
      const icon = c.icon ? `${esc(c.icon)} ` : '';
      const style = color ? ` style="color:${color}"` : '';
      return `<div style="margin-bottom:4px;word-break:break-word;line-height:1.4">${time}<b${style}>${icon}${name}</b>: <span>${text}</span></div>`;
    }).join('');
  }
  function setChat(id, list) {
    const el = $(id);
    if (!el) return;
    const stick = el.scrollTop + el.clientHeight >= el.scrollHeight - 20;
    const html = chatHtml(list);
    if (el._html !== html) { el.innerHTML = html; el._html = html; if (stick) el.scrollTop = el.scrollHeight; }
  }

  function appendChat(c) {
    const html = chatHtml([c]);
    ['wchat', 'gchat'].forEach((id) => {
      const el = $(id);
      if (!el) return;
      const stick = el.scrollTop + el.clientHeight >= el.scrollHeight - 30;
      el.insertAdjacentHTML('beforeend', html);
      el._html = el.innerHTML;
      if (stick) el.scrollTop = el.scrollHeight;
    });
  }

  const roomMatch = base.match(/\/rooms\/(\d+)/);
  const roomId = roomMatch ? roomMatch[1] : null;

  function initEchoListener() {
    if (!roomId) return;
    if (window.Echo) {
      window.Echo.channel(`room.${roomId}`).listen('.chat.message', (e) => {
        appendChat(e);
      });
    } else {
      setTimeout(initEchoListener, 100);
    }
  }
  initEchoListener();

  function miniGrid(grid) {
    const cls = { '#': 'w', b: 'b', c: 'c' };
    return `<span class="mini-grid" style="width:130px">${grid.map((r) => [...r].map((c) => `<i class="${cls[c] || ''}"></i>`).join('')).join('')}</span>`;
  }

  function renderWaiting() {
    const me = S.me, n = S.members.length;
    const mine = S.members.find((m) => m.me);
    const isHost = Boolean(mine && mine.host);
    $('winfo').innerHTML = `<div class="row" style="align-items:flex-start;flex-wrap:nowrap">${miniGrid(S.map.grid)}
      <div style="font-size:14px;line-height:1.6">Room: <b>${esc(S.room.name)}</b><br>Map: <b>${esc(S.map.name)}</b><br>Mode: <b>Team deathmatch</b><br>
      Turn limit: <b>${S.map.turn_time}s</b><br>Password: <b>${esc(S.room.password || '-')}</b></div></div>`;
    $('wcount').textContent = `Players (${n}/${S.max_players})`;

    let slots = S.members.map((m) => `<div class="slot"><div class="em">${m.emoji}${m.host ? '👑' : ''}</div><b>${esc(m.name)}</b><br>${esc(m.class)}<br>${m.host ? 'Host' : (m.ready ? '✔ Ready' : 'Not ready')}</div>`);
    while (slots.length < S.max_players) slots.push('<div class="slot empty">Waiting for player...</div>');
    $('slots').innerHTML = slots.join('');

    const main = $('wmain');
    if (isHost) {
      const others = S.members.filter((m) => !m.host);
      const ok = n >= 2 && others.every((m) => m.ready);
      main.textContent = 'Start'; main.disabled = !ok; main.onclick = () => act('start');
      $('whint').textContent = ok ? 'Teams A and B are assigned automatically when the game starts.' : 'Need at least 2 players, and everyone must be ready.';
    } else {
      const mine = S.members.find((m) => m.me);
      main.textContent = mine.ready ? 'Not ready' : 'Ready'; main.disabled = false; main.onclick = () => act('ready');
      $('whint').textContent = 'Waiting for the host to start.';
    }
    setChat('wchat', S.chat);
  }
  $('wleave').onclick = () => act('leave', {}, () => (location.href = lobby));
  $('gleave').onclick = () => { if (confirm('Surrender and leave this match?')) act('leave', {}, () => (location.href = lobby)); };
  $('gend').onclick = () => {
    if (confirm('End this match for everyone and return to the lobby?')) {
      act('end-match', {}, () => (location.href = lobby));
    }
  };

  const sendChat = (input) => {
    if (!input) return;
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    api('chat', { text }).then(poll).catch((e) => toast(e.message));
  };

  const bindChat = (inputId, btnId, formId) => {
    const input = $(inputId);
    const btn = $(btnId);
    const form = $(formId);
    if (input) {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          sendChat(input);
        }
      });
    }
    if (btn) {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        sendChat(input);
      });
    }
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        sendChat(input);
      });
    }
  };

  bindChat('winput', 'wsend', 'wchat-form');
  bindChat('ginput', 'gsend', 'gchat-form');

  function dist(a, b) { return Math.max(Math.abs(a.x - b.x), Math.abs(a.y - b.y)); }

  function renderGame() {
    const me = S.me, mine = S.members.find((m) => m.me), finished = S.room.status === 'finished';
    const now = Date.now();
    S.members.forEach((member) => {
      const previous = previousHp.get(member.id);
      if (previous !== undefined && member.hp < previous) hitUntil.set(member.id, now + 850);
      previousHp.set(member.id, member.hp);
    });
    const active = S.members.find((m) => m.id === S.active);
    const myTurn = !finished && me.myTurn;
    if (!myTurn) mode = null;

    $('order').innerHTML = S.members.map((m) => `<div class="${m.team === 'Team A' ? 'A' : 'B'} ${m.id === S.active ? 'active' : ''} ${m.alive ? '' : 'dead'}" title="${esc(m.name)}">${m.emoji}</div>`).join('');
    $('round').textContent = `Round ${S.round}`;

    // stats
    $('stats').innerHTML = `<h3>${esc(mine.name)}</h3><div class="portrait" style="font-size:56px">${mine.emoji}</div>
      <div class="stat"><span>Class</span><b>${esc(mine.class)}</b></div>
      <div class="stat"><span>Team</span><b>${esc(me.team || '')}</b></div>
      <div class="stat"><span>HP</span><b>${mine.hp}/${mine.max_hp}</b></div>
      <div class="bar"><i style="width:${Math.max(0, mine.hp / mine.max_hp * 100)}%"></i></div>
      <div class="stat"><span>ATK</span><b>${me.attack}${me.bonus ? ` +${me.bonus}` : ''}</b></div>
      <div class="stat"><span>Range</span><b>${me.range}</b></div>
      <div class="stat"><span>Movement</span><b>${myTurn ? (me.rolled ? me.moves : 'roll D10') : '-'}</b></div>`;

    // board
    const occ = {};
    S.members.forEach((m) => { if (m.alive) occ[`${m.x},${m.y}`] = m; });
    const hl = new Set();
    if (myTurn && mode === 'move' && me.rolled && me.moves > 0) {
      for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) {
        const x = Number(mine.x) + dx, y = Number(mine.y) + dy;
        if ((dx || dy) && S.map.grid[y] && S.map.grid[y][x] && S.map.grid[y][x] !== '#' && !occ[`${x},${y}`]) hl.add(`${x},${y}`);
      }
    }
    const atk = new Set();
    if (myTurn && mode === 'attack' && !me.attacked) {
      S.members.forEach((m) => { if (m.alive && m.team !== me.team && dist(m, mine) <= me.range) atk.add(`${m.x},${m.y}`); });
    }
    const icons = { '#': '🧱', b: '🌿', c: '📦' };
    let html = '';
    S.map.grid.forEach((row, y) => [...row].forEach((c, x) => {
      const k = `${x},${y}`, u = occ[k];
      const cls = ['cell', c === '#' ? 'wall' : c === 'b' ? 'bush' : '', hl.has(k) ? 'hl' : '', atk.has(k) ? 'atk' : ''].join(' ');
      const inner = u
        ? `<div class="unit ${u.team === 'Team A' ? 'A' : 'B'} ${u.me ? 'me' : ''} ${combatFx && combatFx.attacker === u.id && combatFx.until > now ? 'attacking' : ''} ${hitUntil.get(u.id) > now ? 'hit' : ''}" title="${esc(u.name)} ${u.hp}/${u.max_hp}">${u.emoji}<span class="hp"><i style="width:${u.hp / u.max_hp * 100}%"></i></span></div>`
        : (icons[c] || '');
      html += `<div class="${cls}" data-x="${x}" data-y="${y}">${inner}</div>`;
    }));
    $('board').innerHTML = html;

    // banner + actions
    $('banner').textContent = finished ? 'Game over' : (myTurn ? 'Your turn!' : `${active ? active.name : ''}'s turn`);
    $('a-roll').disabled = !myTurn || me.rolled;
    $('a-move').disabled = !myTurn || !me.rolled || me.moves < 1;
    $('a-attack').disabled = !myTurn || me.attacked;
    $('a-end').disabled = !myTurn;
    $('gend').style.display = me.host && !finished ? '' : 'none';
    $('a-move').classList.toggle('on', mode === 'move');
    $('a-attack').classList.toggle('on', mode === 'attack');

    // inventory (4 slots)
    let inv = me.inventory.map((i) => `<div class="inv-slot"><span class="ic">${i.type === 'Buff' ? '🧪' : '❤️'}</span>
      <div style="flex:1"><b>${esc(i.name)}</b> ×${i.qty}<br>${esc(i.desc || '')}</div>
      <button class="btn sm go" data-item="${i.id}" ${myTurn ? '' : 'disabled'}>Use</button></div>`);
    while (inv.length < 4) inv.push('<div class="inv-slot">Empty slot</div>');
    $('inv').innerHTML = inv.slice(0, Math.max(4, me.inventory.length)).join('');

    $('log').innerHTML = S.log.map((l) => `<div>${esc(l)}</div>`).join('');
    setChat('gchat', S.chat);
    if (finished) showResult();
    tickTimer();
  }

  function tickTimer() {
    if (!S || S.time_left === null || S.room.status !== 'playing') return ($('timer').textContent = '');
    const left = Math.max(0, Math.ceil(S.time_left - (Date.now() - fetchedAt) / 1000));
    $('timer').textContent = `⏱ ${left}s`;
  }
  setInterval(tickTimer, 300);

  function showResult() {
    const r = S.result; if (!r) return;
    const rows = r.players.map((p) => `<div class="inv-slot"><span class="ic">${p.emoji}</span><div style="flex:1"><b>${esc(p.name)}</b> ${p.win ? '🏆' : ''}<br>
      ${esc(p.team || '')} · HP ${p.hp}/${p.max_hp} · Damage dealt ${p.damage}</div></div>`).join('');
    clearTimeout(ovTimer);
    $('ovc').innerHTML = `<div class="box"><h3>${esc(r.winner || 'Nobody')} wins!</h3>${rows}<div style="text-align:center;margin-top:10px"><button class="btn go" id="back">Back to lobby</button></div></div>`;
    $('overlay').classList.add('show');
    $('overlay').onclick = null;
    $('back').onclick = () => act('leave', {}, () => (location.href = lobby));
  }

  /* ---------------- actions ---------------- */
  $('a-roll').onclick = () => act('roll', {}, (r) => { overlay(bigDice(r.dice), 1500); mode = 'move'; });
  $('a-move').onclick = () => { mode = mode === 'move' ? null : 'move'; render(); };
  $('a-attack').onclick = () => { mode = mode === 'attack' ? null : 'attack'; render(); };
  $('a-end').onclick = () => act('end');

  $('board').onclick = (e) => {
    const cell = e.target.closest('.cell'); if (!cell || !S || !S.me.myTurn) return;
    const x = +cell.dataset.x, y = +cell.dataset.y;
    if (mode === 'move' && cell.classList.contains('hl')) {
      act('move', { x, y }, (r) => { if (r.found) toast(`You found: ${r.found}`); });
    } else if (mode === 'attack' && cell.classList.contains('atk')) {
      const t = S.members.find((m) => m.alive && Number(m.x) === x && Number(m.y) === y);
      act('attack', { target: t.id }, (r) => {
        mode = null;
        combatFx = { attacker: S.me.id, until: Date.now() + 700 };
        hitUntil.set(t.id, Date.now() + 850);
        render();
        toast(r.miss ? `Attack missed ${r.target}. D10: ${r.dice}` : `${r.target} takes ${r.damage} damage!${r.killed ? ' Defeated!' : ''}`);
        setTimeout(() => { combatFx = null; if (S) render(); }, 750);
      });
    }
  };

  $('inv').onclick = (e) => {
    const b = e.target.closest('[data-item]'); if (!b) return;
    act('item', { id: +b.dataset.item }, (r) => toast(`Used ${r.name}`));
  };
})();
