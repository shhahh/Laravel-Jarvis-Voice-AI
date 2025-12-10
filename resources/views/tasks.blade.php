<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JARVIS Task Manager</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: 'Roboto', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .jarvis-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid #0ea5e9;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(14, 165, 233, 0.2);
            width: 90%;
            max-width: 500px;
            height: 85vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Holographic Header */
        .header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(14, 165, 233, 0.3);
            background: linear-gradient(180deg, rgba(14,165,233,0.1) 0%, transparent 100%);
        }
        .header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #38bdf8;
            letter-spacing: 3px;
            text-shadow: 0 0 10px #38bdf8;
            margin: 0;
            font-size: 24px;
        }

        /* Task List */
        .task-list {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .task-item {
            background: rgba(30, 41, 59, 0.6);
            border-left: 3px solid #38bdf8;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 0 5px 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.4s ease;
        }

        /* AI TERMINAL BOX */
        .ai-terminal {
            background: rgba(0, 0, 0, 0.6);
            border-top: 1px solid #38bdf8;
            padding: 15px;
            font-family: 'Courier New', monospace;
            color: #38bdf8;
            font-size: 14px;
            min-height: 60px;
            display: flex;
            align-items: center;
        }
        .cursor {
            display: inline-block;
            width: 8px;
            height: 15px;
            background: #38bdf8;
            animation: blink 1s infinite;
            margin-left: 5px;
        }

        /* Controls */
        .control-panel {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            border-top: 1px solid rgba(14, 165, 233, 0.3);
        }

        .mic-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: radial-gradient(circle, #0ea5e9 0%, #0284c7 100%);
            border: 2px solid #7dd3fc;
            color: white;
            font-size: 28px;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.5);
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mic-btn:hover { transform: scale(1.1); box-shadow: 0 0 30px #38bdf8; }
        .mic-btn.listening {
            animation: pulse Red 1.5s infinite;
            background: #ef4444;
            border-color: #fca5a5;
            box-shadow: 0 0 20px #ef4444;
        }

        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.7); } 70% { box-shadow: 0 0 0 15px rgba(239,68,68,0); } }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #0ea5e9; }
    </style>
</head>
<body>

<div class="jarvis-card">
    <div class="header">
        <h2>J.A.R.V.I.S</h2>
        <small class="text-info" style="font-size: 10px; letter-spacing: 1px;">SYSTEM ONLINE</small>
    </div>

    <!-- Task List -->
    <div class="task-list" id="task-container">
        @foreach($tasks as $task)
            <div class="task-item">
                <div>
                    <h6 class="mb-0">{{ $task->title }}</h6>
                    <small class="text-muted" style="font-size: 10px;">{{ $task->created_at->format('H:i') }}</small>
                </div>
                <i class="bi bi-check2-circle text-info"></i>
            </div>
        @endforeach
        
        @if($tasks->isEmpty())
            <div class="text-center text-muted mt-5" id="empty-msg">
                <i class="bi bi-shield-check display-4 mb-3 d-block" style="opacity: 0.3"></i>
                No Pending Protocols
            </div>
        @endif
    </div>

    <!-- AI Output Screen -->
    <div class="ai-terminal">
        <span id="ai-text">Waiting for command...</span><span class="cursor"></span>
    </div>

    <!-- Controls -->
    <div class="control-panel">
        <button class="mic-btn" id="mic-btn">
            <i class="bi bi-mic-fill"></i>
        </button>
        <p class="text-info small mb-0" id="status-text" style="font-family: 'Orbitron'">STANDBY</p>
    </div>
</div>

<!-- LOGIC -->
<script>
    const micBtn = document.getElementById('mic-btn');
    const statusText = document.getElementById('status-text');
    const aiText = document.getElementById('ai-text');
    const taskContainer = document.getElementById('task-container');
    const emptyMsg = document.getElementById('empty-msg');

    // --- Typewriter Effect ---
    function typeWriter(text) {
        aiText.innerHTML = "";
        let i = 0;
        function type() {
            if (i < text.length) {
                aiText.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, 25);
            }
        }
        type();
    }

    // --- Voice Output (Speak) ---
    function speak(text) {
        window.speechSynthesis.cancel();
        let spokenText = text.replace(/J\.A\.R\.V\.I\.S/g, "Jarvis");
        const speech = new SpeechSynthesisUtterance(spokenText);
        speech.lang = 'en-US'; 
        speech.pitch = 0.9; 
        speech.rate = 1.1;
        window.speechSynthesis.speak(speech);
    }

    // --- Mic Input (Listen) ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.lang = 'en-US';

        micBtn.addEventListener('click', () => {
            if (micBtn.classList.contains('listening')) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });

        recognition.onstart = () => {
            micBtn.classList.add('listening');
            statusText.innerText = "LISTENING...";
            aiText.innerText = "Listening...";
        };

        recognition.onend = () => {
            micBtn.classList.remove('listening');
            statusText.innerText = "PROCESSING...";
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            statusText.innerText = "ANALYZING...";
            aiText.innerText = `"${transcript}"`;
            sendCommandToAI(transcript);
        };
    } else {
        aiText.innerText = "Browser doesn't support Voice.";
    }

    // --- Backend Logic ---
    async function sendCommandToAI(message) {
        try {
            const response = await fetch("{{ route('ai.command') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            // 1. ADD TASK
            if (data.status === 'task_added') {
                statusText.innerText = "TASK ADDED";
                const cleanText = data.reply.replace("✅ Added: ", "");
                
                speak("Task stored, Sir.");
                typeWriter("Protocol Saved: " + cleanText);
                addTaskToUI(cleanText);
            } 
            // 2. OPEN URL
            else if (data.status === 'open_url') {
                statusText.innerText = "EXECUTING";
                speak(data.reply);
                typeWriter("Opening Interface: " + data.url);
                
                let newTab = window.open(data.url, '_blank');
                if(!newTab || newTab.closed || typeof newTab.closed == 'undefined') {
                    typeWriter("Browser Blocked Popup. Click link below.");
                    aiText.innerHTML += `<br><a href="${data.url}" target="_blank" class="text-warning">[ CLICK TO OPEN ]</a>`;
                }
            } 
            // 3. CHAT
            else {
                statusText.innerText = "RESPONSE";
                speak(data.reply);
                typeWriter(data.reply);
            }

        } catch (error) {
            console.error(error);
            statusText.innerText = "ERROR";
            typeWriter("Server Connection Failed.");
        }
    }

    function addTaskToUI(title) {
        if(emptyMsg) emptyMsg.style.display = 'none';
        const div = document.createElement('div');
        div.className = 'task-item';
        div.innerHTML = `<div><h6 class="mb-0">${title}</h6><small class="text-muted">Just now</small></div><i class="bi bi-check2-circle text-info"></i>`;
        taskContainer.prepend(div);
    }
</script>

</body>
</html>