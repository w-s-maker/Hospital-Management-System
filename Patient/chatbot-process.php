<?php


session_start();

// Initialize chat log if not exists
if (!isset($_SESSION['chat_log'])) {
    $_SESSION['chat_log'] = [];
    $_SESSION['chat_log'][] = ['type' => 'bot', 'message' => 'Hello! How can I help you today?'];
}

// Clear chat log (for testing or a reset button)
if (isset($_POST['clear_chat'])) {
    $_SESSION['chat_log'] = [['type' => 'bot', 'message' => 'Hello! How can I help you today?']];
}

// Process new user message
if (isset($_POST['user_message'])) {
    $userMessage = trim($_POST['user_message']);
    if (!empty($userMessage)) {
        $_SESSION['chat_log'][] = ['type' => 'user', 'message' => htmlspecialchars($userMessage)];

        // Send user message to the processing script
        $url = 'chatbot-process.php';
        $postData = ['user_message' => $userMessage];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $botResponse = file_get_contents($url, false, $context);

        if ($botResponse !== false) {
            $_SESSION['chat_log'][] = ['type' => 'bot', 'message' => htmlspecialchars(trim($botResponse))];
        } else {
            $_SESSION['chat_log'][] = ['type' => 'bot', 'message' => 'Sorry, I encountered an error processing your request.'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Functional Chatbot</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .chatbot-icon {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            line-height: 60px;
            text-align: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        .chatbot-icon:hover {
            background-color: #0056b3;
        }
        .chatbot-container {
            max-width: 600px;
            position: fixed;
            bottom: 80px; 
            right: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            z-index: 999;
            display: none;
        }
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }
        .chat-log {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            height: 300px;
            overflow-y: scroll;
            background-color: #f9f9f9;
        }
        .message {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            clear: both;
        }
        .user-message {
            background-color: #e2f7ff;
            color: #007bff;
            float: right;
        }
        .bot-message {
            background-color: #d4edda;
            color: #28a745;
            float: left;
        }
        .input-area {
            display: flex;
        }
        .input-area input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
        }
        .input-area button[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 1rem;
        }
        .input-area button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .clear-chat-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .clear-chat-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>

    <div class="chatbot-icon" onclick="toggleChatbot()">
        &#128172;
    </div>

    <div class="chatbot-container" id="chatbotContainer">
        <h2>Afya Hospital Chatbot</h2>
        <div class="chat-log">
            <?php if (isset($_SESSION['chat_log'])): ?>
                <?php foreach ($_SESSION['chat_log'] as $message): ?>
                    <div class="message <?php echo $message['type'] === 'user' ? 'user-message' : 'bot-message'; ?>">
                        <?php echo $message['type'] === 'user' ? 'You: ' : 'Bot: '; ?>
                        <?php echo $message['message']; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-area">
                <input type="text" name="user_message" placeholder="Type your message here..." required>
                <button type="submit">Send</button>
            </div>
        </form>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="clear_chat" class="clear-chat-button">Clear Chat</button>
        </form>
    </div>

    <script>
        const chatbotIcon = document.querySelector('.chatbot-icon');
        const chatbotContainer = document.getElementById('chatbotContainer');
        const chatLog = document.querySelector('.chat-log');

        function toggleChatbot() {
            chatbotContainer.style.display = chatbotContainer.style.display === 'none' ? 'block' : 'none';
            
            if (chatbotContainer.style.display === 'block' && chatLog) {
                chatLog.scrollTop = chatLog.scrollHeight;
            }
        }

        
        if (chatLog) {
            chatLog.scrollTop = chatLog.scrollHeight;
        }
    </script>

</body>
</html>