const board = document.getElementById('board');


// ถ้าไม่ได้อยู่หน้ามินิเกม ไม่ต้องทำอะไร
if (!board || !document.getElementById('enemyHp')) {
    // ไม่ใช่หน้ามินิเกม
} else {

    /*
    |--------------------------------------------------------------------------
    | Game Config
    |--------------------------------------------------------------------------
    */

    const WIDTH = 10;
    const HEIGHT = 10;

    const MAX_MOVEMENT = 5;


    /*
    |--------------------------------------------------------------------------
    | Player ID
    |--------------------------------------------------------------------------
    |
    | ตอนนี้ใช้ random เพื่อทดสอบ Multiplayer ก่อน
    | ภายหลังเราจะเปลี่ยนเป็น User ID จาก Laravel
    |
    */

    const myPlayerId =
        Math.random() > 0.5 ? 1 : 2;

    const myTeam = myPlayerId === 1 ? 'red' : 'blue';
    const myIcon = myPlayerId === 1 ? '🧙' : '🧝';

    const pIdEl = document.getElementById('player-id');
    if (pIdEl) {
        const teamLabel = myPlayerId === 1 ? 'ทีมแดง (🧙 พ่อมด)' : 'ทีมน้ำเงิน (🧝 เอลฟ์)';
        const teamColor = myPlayerId === 1 ? '#f87171' : '#60a5fa';
        pIdEl.innerHTML = `<b style="color: ${teamColor}">${myPlayerId} - ${teamLabel}</b>`;
    }

    // แจ้งเตือน WebSocket เมื่อผู้เล่นเปิดหน้าเกม
    fetch('/game/join', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            player_id: myPlayerId,
            team: myTeam,
            icon: myIcon
        })
    }).catch(() => {});


    /*
    |--------------------------------------------------------------------------
    | Multiplayer Players
    |--------------------------------------------------------------------------
    */

    const players = {

        1: {
            x: 1,
            y: 1,
            icon: '🧙'
        },

        2: {
            x: 8,
            y: 8,
            icon: '🧝'
        }

    };


    /*
    |--------------------------------------------------------------------------
    | Local Player
    |--------------------------------------------------------------------------
    */

    let player = {

        x: players[myPlayerId].x,

        y: players[myPlayerId].y,

        hp: 100

    };


    /*
    |--------------------------------------------------------------------------
    | Enemy
    |--------------------------------------------------------------------------
    */

    let enemy = {

        x: 7,

        y: 6,

        hp: 50

    };


    /*
    |--------------------------------------------------------------------------
    | Game State
    |--------------------------------------------------------------------------
    */

    let movement = MAX_MOVEMENT;

    let playerTurn = true;


    /*
    |--------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------
    */

    const blocks = new Set([

        "3,1",

        "3,2",

        "3,3",

        "4,3",

        "5,3",

        "6,3",

        "6,4",

        "2,7",

        "3,7",

        "4,7",

        "4,8",

        "8,2",

        "8,3"

    ]);


    /*
    |--------------------------------------------------------------------------
    | DOM
    |--------------------------------------------------------------------------
    */

    const playerIdElement =
        document.getElementById('player-id');

    playerIdElement.textContent =
        myPlayerId;


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    function key(x, y) {

        return `${x},${y}`;

    }


    function distance(a, b) {

        return Math.abs(a.x - b.x)
            + Math.abs(a.y - b.y);

    }


    function log(message) {

        document.getElementById('log')
            .textContent = message;

    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    function render() {

        board.innerHTML = '';


        for (let y = 0; y < HEIGHT; y++) {

            for (let x = 0; x < WIDTH; x++) {

                const tile =
                    document.createElement('div');

                tile.classList.add('tile');


                /*
                | Block
                */

                if (
                    blocks.has(key(x, y))
                ) {

                    tile.classList.add('block');

                }


                /*
                | Reachable
                */

                if (

                    playerTurn &&

                    !blocks.has(key(x, y)) &&

                    distance(player, { x, y })
                        <= movement &&

                    !(x === player.x &&
                        y === player.y)

                ) {

                    tile.classList.add(
                        'reachable'
                    );

                }


                /*
                | Local Player
                */

                if (

                    x === player.x &&

                    y === player.y

                ) {

                    const p =
                        document.createElement('div');

                    p.classList.add('player');

                    p.textContent =
                        players[myPlayerId].icon;

                    tile.appendChild(p);

                }


                /*
                | Other Players
                */

                for (const id in players) {

                    const other =
                        players[id];


                    if (
                        Number(id) === myPlayerId
                    ) {

                        continue;

                    }


                    if (

                        x === other.x &&

                        y === other.y

                    ) {

                        const p =
                            document.createElement('div');

                        p.classList.add(
                            'other-player'
                        );

                        p.textContent =
                            other.icon;

                        tile.appendChild(p);

                    }

                }


                /*
                | Enemy
                */

                if (

                    x === enemy.x &&

                    y === enemy.y

                ) {

                    const e =
                        document.createElement('div');

                    e.classList.add('enemy');

                    e.textContent = '👹';

                    tile.appendChild(e);

                }


                /*
                | Click
                */

                tile.addEventListener(
                    'click',
                    () => clickTile(x, y)
                );


                board.appendChild(tile);

            }

        }


        updateUI();

    }


    /*
    |--------------------------------------------------------------------------
    | Click Tile
    |--------------------------------------------------------------------------
    */

    function clickTile(x, y) {

        if (!playerTurn) {

            log(
                '⏳ ตอนนี้เป็น Turn ของ Enemy'
            );

            return;

        }


        /*
        | Block
        */

        if (
            blocks.has(key(x, y))
        ) {

            log(
                '🧱 ช่องนี้มีสิ่งกีดขวาง'
            );

            return;

        }


        /*
        | Distance
        */

        const d =
            distance(player, { x, y });


        if (d > movement) {

            log(
                '❌ เดินไกลเกินไป!'
            );

            return;

        }


        /*
        | Same Position
        */

        if (
            x === player.x &&
            y === player.y
        ) {

            return;

        }


        /*
        | Enemy Attack
        */

        if (

            x === enemy.x &&

            y === enemy.y

        ) {

            attack();

            return;

        }


        /*
        | Move
        */

        player.x = x;

        player.y = y;

        movement -= d;


        /*
        | Update Multiplayer Position
        */

        players[myPlayerId].x =
            player.x;

        players[myPlayerId].y =
            player.y;


        log(

            `🧙 เดินไป (${x}, ${y})\n` +

            `ใช้ Movement ${d} ช่อง\n` +

            `เหลือ Movement ${movement} ช่อง`

        );


        render();


        /*
        | Send to Laravel
        */

        sendPlayerPosition();

    }


    /*
    |--------------------------------------------------------------------------
    | Send Player Position
    |--------------------------------------------------------------------------
    */

    async function sendPlayerPosition() {

        try {

            const response =
                await fetch('/game/move', {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                .content,

                        'Accept':
                            'application/json'

                    },

                    body: JSON.stringify({

                        player_id:
                            myPlayerId,

                        x:
                            player.x,

                        y:
                            player.y

                    })

                });


            const data =
                await response.json();


            console.log(
                'Move response:',
                data
            );


        } catch (error) {

            console.error(
                'Move error:',
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Receive Other Player Movement
    |--------------------------------------------------------------------------
    */

    if (window.Echo) {

        console.log(
            '✅ Laravel Echo พร้อมใช้งาน'
        );


        window.Echo
            .channel('game')
            .listen(
                '.player.moved',
                (event) => {

                    console.log(
                        '🎮 PLAYER MOVED:',
                        event
                    );


                    const id =
                        Number(event.playerId);


                    /*
                    | Ignore own event
                    */

                    if (
                        id === myPlayerId
                    ) {

                        return;

                    }


                    /*
                    | Create player if needed
                    */

                    if (!players[id]) {

                        players[id] = {

                            x: event.x,

                            y: event.y,

                            icon: '🧝'

                        };

                    }


                    /*
                    | Update position
                    */

                    players[id].x =
                        event.x;

                    players[id].y =
                        event.y;


                    log(

                        `🎮 Player ${id} เดินไป ` +

                        `(${event.x}, ${event.y})`

                    );


                    render();

                }
            );


    } else {

        console.error(
            '❌ Laravel Echo ยังไม่ถูกโหลด'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Attack
    |--------------------------------------------------------------------------
    */

    function attack() {

        if (
            distance(player, enemy) > 1
        ) {

            log(
                '⚔️ ต้องอยู่ติดกับ Enemy เพื่อโจมตี'
            );

            return;

        }


        const damage =
            Math.floor(
                Math.random() * 11
            ) + 10;


        enemy.hp -= damage;


        log(

            `⚔️ โจมตีโดน ${damage} Damage!\n` +

            `👹 Enemy HP เหลือ ` +
            `${Math.max(0, enemy.hp)}`

        );


        if (enemy.hp <= 0) {

            log(
                '🏆 ชนะแล้ว! Enemy ถูกกำจัด!'
            );

            playerTurn = false;

            render();

            return;

        }


        endTurn();

    }


    /*
    |--------------------------------------------------------------------------
    | Enemy Turn
    |--------------------------------------------------------------------------
    */

    function enemyTurn() {

        if (
            enemy.hp <= 0
        ) {

            return;

        }


        /*
        | Enemy Attack
        */

        if (
            distance(enemy, player) <= 1
        ) {

            const damage =
                Math.floor(
                    Math.random() * 8
                ) + 5;


            player.hp -= damage;


            log(

                `👹 Enemy โจมตี ${damage} Damage!\n` +

                `❤️ Player HP = ` +

                `${Math.max(0, player.hp)}`

            );


            if (
                player.hp <= 0
            ) {

                playerTurn = false;

                log(
                    '💀 Game Over'
                );

            }

        }


        /*
        | Enemy Move
        */

        else {

            const dx =
                Math.sign(
                    player.x - enemy.x
                );


            const dy =
                Math.sign(
                    player.y - enemy.y
                );


            let nx =
                enemy.x + dx;


            let ny =
                enemy.y;


            if (

                !blocks.has(
                    key(nx, ny)
                ) &&

                nx >= 0 &&

                nx < WIDTH

            ) {

                enemy.x = nx;

            }


            else if (

                !blocks.has(
                    key(
                        enemy.x,
                        enemy.y + dy
                    )
                ) &&

                enemy.y + dy >= 0 &&

                enemy.y + dy < HEIGHT

            ) {

                enemy.y += dy;

            }


            log(
                '👹 Enemy เดินเข้าหาผู้เล่น'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | End Turn
    |--------------------------------------------------------------------------
    */

    function endTurn() {

        if (!playerTurn) {

            return;

        }


        playerTurn = false;

        render();


        setTimeout(
            function () {

                enemyTurn();


                if (

                    player.hp > 0 &&

                    enemy.hp > 0

                ) {

                    playerTurn = true;

                    movement =
                        MAX_MOVEMENT;

                }


                render();

            },
            700
        );

    }


    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */

    function updateUI() {

        document.getElementById('status')
            .textContent =

            playerTurn
                ? '🟢 Player Turn'
                : '🔴 Enemy Turn';


        document.getElementById('hp')
            .textContent =
            player.hp;


        document.getElementById('move')
            .textContent =

            playerTurn
                ? movement
                : 0;


        document.getElementById('enemyHp')
            .textContent =
            enemy.hp;


        document.getElementById('end')
            .disabled =
            !playerTurn;

    }


    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    function resetGame() {

        player = {

            x: players[myPlayerId].x,

            y: players[myPlayerId].y,

            hp: 100

        };


        enemy = {

            x: 7,

            y: 6,

            hp: 50

        };


        movement =
            MAX_MOVEMENT;


        playerTurn = true;


        log(

            `🎮 เริ่มเกม!\n` +

            `👤 คุณคือ Player ${myPlayerId}\n` +

            `คลิกช่องสีเหลืองเพื่อเดิน`

        );


        render();

    }


    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('end')
        .addEventListener(
            'click',
            endTurn
        );


    document
        .getElementById('reset')
        .addEventListener(
            'click',
            resetGame
        );


    /*
    |--------------------------------------------------------------------------
    | Chat
    |--------------------------------------------------------------------------
    */

    const chatForm =
        document.getElementById('chat-form');

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[c]));
    }

    function addMessage(data) {
        const messages = document.getElementById('messages');
        if (!messages) return;

        const div = document.createElement('div');

        if (typeof data === 'string') {
            div.className = 'message';
            div.textContent = data;
        } else {
            const time = data.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const type = data.type || 'chat';
            const team = data.team || 'default';
            const icon = data.icon || '';
            const sender = data.sender || 'Player';
            const msg = data.message || '';

            if (type === 'system') {
                div.className = 'message message-system';
                div.innerHTML = `<span class="chat-time">[${esc(time)}]</span> <span class="chat-sender team-system">${esc(icon)} ${esc(sender)}:</span> <span class="chat-text">${esc(msg)}</span>`;
            } else {
                div.className = `message team-${team}`;
                div.innerHTML = `<span class="chat-time">[${esc(time)}]</span> <span class="chat-sender team-${team}">${esc(icon)} ${esc(sender)}:</span> <span class="chat-text">${esc(msg)}</span>`;
            }
        }

        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    if (window.Echo) {
        window.Echo
            .channel('chat')
            .listen(
                '.chat.message',
                (event) => {
                    addMessage(event);
                }
            );
    }

    if (chatForm) {
        chatForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const input = document.getElementById('message');
            const message = input.value.trim();

            if (!message) return;

            try {
                await fetch('/chat/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        player_id: myPlayerId,
                        message: message,
                        team: myTeam,
                        icon: myIcon
                    })
                });

                input.value = '';
            } catch (error) {
                console.error('Chat error:', error);
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Start
    |--------------------------------------------------------------------------
    */

    resetGame();

}