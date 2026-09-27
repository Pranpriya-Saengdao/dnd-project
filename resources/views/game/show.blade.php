<x-layouts::app :title="__('Game')">
    <div
        id="gamePage"
        data-state-url="{{ route('matches.state', $gameMatch) }}"
        data-roll-url="{{ route('matches.movement-roll', $gameMatch) }}"
        data-move-url="{{ route('matches.move', $gameMatch) }}"
        data-attack-url="{{ route('matches.attack', $gameMatch) }}"
    >
        <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
        <h1>D&amp;D Game</h1>
        <p id="gameMessage">กำลังโหลดสถานะเกม...</p>

        <section>
            <h2>Game State</h2>
            <p>รอบที่ <span id="roundNumber">-</span></p>
            <p>เทิร์นของ <span id="currentPlayer">-</span></p>
            <p>สถานะเกม: <span id="matchStatus">-</span></p>
            <ul id="playerList"></ul>
        </section>

        <section>
            <h2>แผนที่ 10 × 10</h2>
            <p>ทอยเต๋าแล้วเลือกช่องปลายทาง ช่องเดินทแยงไม่นับ</p>
            <div id="gameBoard" style="display:grid;grid-template-columns:repeat(10, minmax(28px, 1fr));gap:3px;max-width:500px"></div>
        </section>

        <section>
            <h2>การกระทำของคุณ</h2>
            <button type="button" id="movementButton">ทอย d10 เพื่อเดิน</button>
            <label for="attackType">รูปแบบการโจมตี</label>
            <select id="attackType">
                <option value="normal">โจมตีปกติ</option>
                <option value="skill">ใช้สกิล</option>
            </select>
            <label for="targetMember">เป้าหมาย</label>
            <select id="targetMember"></select>
            <button type="button" id="attackButton">โจมตี</button>
        </section>

        <section>
            <h2>ประวัติการต่อสู้</h2>
            <ol id="combatLog"></ol>
        </section>
    </div>

    <script>
        const gamePage = document.getElementById('gamePage');
        const gameMessage = document.getElementById('gameMessage');
        const playerList = document.getElementById('playerList');
        const targetMember = document.getElementById('targetMember');
        const gameBoard = document.getElementById('gameBoard');
        const csrfToken = document.getElementById('csrfToken').value;
        let currentState = null;
        let lastMessage = '';

        async function loadGameState() {
            try {
                const response = await fetch(gamePage.dataset.stateUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                const state = await response.json();

                if (!response.ok) {
                    throw new Error(state.message || 'โหลดสถานะเกมไม่สำเร็จ');
                }

                currentState = state;
                document.getElementById('roundNumber').textContent = state.match.round;
                document.getElementById('matchStatus').textContent = state.match.status;
                document.getElementById('currentPlayer').textContent = state.match.current_player_name || '-';
                playerList.innerHTML = '';
                targetMember.innerHTML = '';

                let myTeam = null;
                state.players.forEach(function (player) {
                    if (player.is_me) {
                        myTeam = player.team;
                    }

                    const playerRow = document.createElement('li');
                    playerRow.textContent = player.name + ' (' + player.class + ') - ทีม ' + player.team
                        + ' - HP ' + player.hp + ' - สกิลเหลือ ' + player.skill_uses_remaining;
                    playerList.appendChild(playerRow);
                });

                gameBoard.innerHTML = '';
                for (let y = 0; y < 10; y++) {
                    for (let x = 0; x < 10; x++) {
                        const cell = document.createElement('button');
                        cell.type = 'button';
                        cell.dataset.x = x;
                        cell.dataset.y = y;
                        cell.style.minHeight = '34px';
                        cell.style.padding = '2px';
                        const occupant = state.players.find(function (player) {
                            return player.is_alive && player.position.x == x && player.position.y == y;
                        });
                        cell.textContent = occupant ? occupant.name.slice(0, 5) : x + ',' + y;
                        cell.title = occupant ? occupant.name + ' (' + occupant.class + ')' : 'เลือกช่อง ' + x + ',' + y;
                        if (occupant) cell.disabled = true;
                        cell.addEventListener('click', moveToCell);
                        gameBoard.appendChild(cell);
                    }
                }

                state.players.forEach(function (player) {
                    if (player.team != myTeam && player.is_alive) {
                        const option = document.createElement('option');
                        option.value = player.id;
                        option.textContent = player.name + ' (' + player.class + ') - HP ' + player.hp;
                        targetMember.appendChild(option);
                    }
                });

                const combatLog = document.getElementById('combatLog');
                combatLog.innerHTML = '';
                state.recent_combat.forEach(function (entry) {
                    const logRow = document.createElement('li');
                    logRow.textContent = 'รอบ ' + entry.round + ': ' + entry.attacker + ' โจมตี ' + entry.target
                        + ' (' + entry.type + ') ทอยได้ ' + entry.roll + ' ทำความเสียหาย ' + entry.damage;
                    combatLog.appendChild(logRow);
                });
                gameMessage.textContent = lastMessage;
            } catch (error) {
                gameMessage.textContent = error.message;
            }
        }

        async function moveToCell(event) {
            const cell = event.currentTarget;
            try {
                const result = await postGameAction(gamePage.dataset.moveUrl, {
                    position_x: Number(cell.dataset.x),
                    position_y: Number(cell.dataset.y)
                });
                lastMessage = result.message;
                await loadGameState();
            } catch (error) {
                gameMessage.textContent = error.message;
            }
        }

        async function postGameAction(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(body)
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'ทำรายการไม่สำเร็จ');
            }

            return result;
        }

        document.getElementById('movementButton').addEventListener('click', async function () {
            try {
                const result = await postGameAction(gamePage.dataset.rollUrl, {});
                lastMessage = result.message;
                await loadGameState();
            } catch (error) {
                gameMessage.textContent = error.message;
            }
        });

        document.getElementById('attackButton').addEventListener('click', async function () {
            try {
                const result = await postGameAction(gamePage.dataset.attackUrl, {
                    target_member_id: Number(targetMember.value),
                    attack_type: document.getElementById('attackType').value
                });
                lastMessage = 'ทอยได้ ' + result.dice_roll + ' ทำความเสียหาย ' + result.damage;
                await loadGameState();
            } catch (error) {
                gameMessage.textContent = error.message;
            }
        });

        loadGameState();
    </script>
</x-layouts::app>
