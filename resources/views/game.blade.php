<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mini D&D Multiplayer</title>

    @vite(['resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #17151f;
            color: white;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        .panel {
            background: #242131;
            border: 1px solid #443d59;
            border-radius: 12px;
            padding: 15px;
            margin: 12px 0;
        }

        .status {
            font-size: 20px;
            font-weight: bold;
        }

        #board {
            display: grid;
            grid-template-columns: repeat(10, 42px);
            gap: 2px;
            justify-content: center;
            margin: 20px auto;
        }

        .tile {
            width: 42px;
            height: 42px;
            background: #315f40;
            border: 1px solid #1f3d2a;
            position: relative;
            cursor: pointer;
        }

        .tile:nth-child(odd) {
            background: #2d5b3d;
        }

        .tile.block {
            background: #48434c;
            cursor: not-allowed;
        }

        .tile.reachable {
            outline: 3px solid #e7c84b;
            outline-offset: -3px;
        }

        .player,
        .enemy,
        .other-player {
            position: absolute;
            inset: 4px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            font-size: 25px;
        }

        .player {
            background: #315aa8;
        }

        .other-player {
            background: #7d61c9;
        }

        .enemy {
            background: #8e3d3d;
        }

        .controls {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            background: #7d61c9;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .log {
            min-height: 60px;
            white-space: pre-line;
        }

        .chat-box {
            max-width: 500px;
            margin: 20px auto;
            background: #242131;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #443d59;
        }

        #messages {
            height: 250px;
            overflow-y: auto;
            background: #15131c;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .message {
            padding: 8px 10px;
            background: #2c293a;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.4;
            display: flex;
            align-items: baseline;
            gap: 6px;
            border-left: 3px solid #6b7280;
        }

        .message.team-red {
            border-left-color: #ef4444;
            background: #2d1c24;
        }

        .message.team-blue {
            border-left-color: #3b82f6;
            background: #192336;
        }

        .message.message-system {
            border-left-color: #eab308;
            background: #28241d;
            font-style: italic;
            color: #fef08a;
        }

        .chat-time {
            font-size: 11px;
            color: #9ca3af;
            font-family: monospace;
            flex-shrink: 0;
        }

        .chat-sender {
            font-weight: bold;
            flex-shrink: 0;
        }

        .chat-sender.team-red {
            color: #f87171;
        }

        .chat-sender.team-blue {
            color: #60a5fa;
        }

        .chat-sender.team-system {
            color: #facc15;
        }

        .chat-text {
            word-break: break-word;
            flex: 1;
        }

        #chat-form {
            display: flex;
            gap: 10px;
        }

        #message {
            flex: 1;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #443d59;
            background: #15131c;
            color: white;
            outline: none;
        }

        #message:focus {
            border-color: #7c3aed;
        }

        .player-info {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.8;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>🎲 Mini D&D Multiplayer</h1>

    <div class="panel">

        <div class="status" id="status">
            Player Turn
        </div>

        <div>
            👤 คุณคือ Player:
            <span id="player-id">กำลังเชื่อมต่อ...</span>
        </div>

        <div>
            ❤️ HP:
            <span id="hp">100</span>
        </div>

        <div>
            👣 Movement:
            <span id="move">5</span>
        </div>

        <div>
            👹 Enemy HP:
            <span id="enemyHp">50</span>
        </div>

    </div>


    <!-- Game Board -->

    <div id="board"></div>


    <!-- Controls -->

    <div class="panel controls">

        <button id="end">
            จบ Turn
        </button>

        <button id="reset">
            เริ่มใหม่
        </button>

    </div>


    <!-- Game Log -->

    <div class="panel log" id="log"></div>


    <!-- Chat -->

    <div class="chat-box">

        <h2>💬 Chat</h2>

        <div id="messages"></div>

        <form id="chat-form">

            @csrf

            <input
                id="message"
                type="text"
                placeholder="พิมพ์ข้อความ..."
                autocomplete="off"
            >

            <button type="submit">
                ส่ง
            </button>

        </form>

    </div>

</div>

</body>

</html>