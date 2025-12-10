<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::latest()->get();
        return view('tasks', ['tasks' => $tasks]);
    }

    public function handleCommand(Request $request)
    {
        $userMessage = $request->input('message');
        
        // 🔥 Port check kar lena (65175 ya jo bhi chal raha ho)
        $url = 'http://127.0.0.1:55592/v1/chat/completions';

        // Prompt ko thoda aur strict banaya hai
        $systemPrompt = "
        You are Jarvis.
        Your ONLY job is to output JSON. Do not speak.
        
        Rules:
        1. If user says 'Add task' or 'Buy milk' -> Output: {\"type\": \"add_task\", \"content\": \"Buy Milk\"}
        2. If user says 'Open YouTube' -> Output: {\"type\": \"open_url\", \"url\": \"https://www.youtube.com\", \"content\": \"Opening YouTube\"}
        3. If user says 'Hello' -> Output: {\"type\": \"chat\", \"content\": \"Hello Sir.\"}

        IMPORTANT: Output ONLY the JSON object. No markdown. No explanations.
        ";

        $data = [
            'model' => 'Phi-3.5-mini-instruct-generic-cpu:1',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'stream' => false,
            'temperature' => 0.1
        ];

        // cURL Request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        if(curl_errno($ch)){
            return response()->json(['status' => 'chat', 'reply' => 'Connection Error: ' . curl_error($ch)]);
        }
        curl_close($ch);

        $result = json_decode($response, true);
        $aiRawReply = $result['choices'][0]['message']['content'] ?? '';

        // --- 🔥 SMART CLEANING LOGIC (Ye error fix karega) ---

        // 1. Markdown (```json) hatao
        $cleanJson = str_replace(['```json', '```'], '', $aiRawReply);
        
        // 2. Sirf { se } tak ka hissa nikalo (Agar AI ne extra text likha hai)
        if (preg_match('/\{.*\}/s', $cleanJson, $matches)) {
            $cleanJson = $matches[0];
        }

        // 3. Decode karo
        $parsedReply = json_decode($cleanJson, true);

        // 4. Agar ab bhi Fail hua, toh Raw message dikhao (Debugging ke liye)
        if ($parsedReply === null) {
            // Fallback: Agar JSON nahi hai, toh seedha chat maan lo
            return response()->json(['status' => 'chat', 'reply' => $aiRawReply]);
        }

        // --- ACTIONS ---

        // Task Add
        if (isset($parsedReply['type']) && $parsedReply['type'] === 'add_task') {
            try {
                Task::create(['title' => $parsedReply['content']]);
                return response()->json(['status' => 'task_added', 'reply' => "✅ Added: " . $parsedReply['content']]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'chat', 'reply' => "Database Error: " . $e->getMessage()]);
            }
        }

        // Open URL
        if (isset($parsedReply['type']) && $parsedReply['type'] === 'open_url') {
            return response()->json([
                'status' => 'open_url', 
                'reply' => $parsedReply['content'], 
                'url' => $parsedReply['url']
            ]);
        }

        // Normal Chat
        return response()->json(['status' => 'chat', 'reply' => $parsedReply['content'] ?? '...']);
    }
}