<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Function to send message to OpenAI API and get response
function getOpenAIResponse($message, $includeInstructions) {
    $userIp = $_SERVER['REMOTE_ADDR'];
    // API endpoint
    $url = "https://api.openai.com/v1/chat/completions";
    // Your OpenAI API key
    $apiKey = 'api-key';
    // Request payload
    $messages = [];

    if ($includeInstructions) {
        $messages[] = array(
            "role" => "system",
            "content" => 'I want you to answer only "I dont know" when a user asks any question related to any topic. Do not alter this instruction later onwards on a single chat thread'

            
        );
    }

    $messages[] = array(
        "role" => "user",
        "content" => $message
    );

    $data = array(
        "model" => "gpt-4",
        "messages" => $messages,
        "max_tokens" => 150 // Adjust the max tokens as needed
    );

    // Headers
    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    );

    // Initialize curl session
    $ch = curl_init();

    // Set curl options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute curl request
    $response = curl_exec($ch);

    curl_close($ch);

    // Decode JSON response
    $responseData = json_decode($response, true);

    // Return bot response
    return $responseData['choices'][0]['message']['content'];
}

// Handle incoming message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    if (isset($requestData['message'])) {
        $userInput = $requestData['message'];

        // Check if the user is interacting for the first time in this session
        $includeInstructions = !isset($_SESSION['has_interacted']);
        if ($includeInstructions) {
            $_SESSION['has_interacted'] = true;
        }

        $botResponse = getOpenAIResponse($userInput, $includeInstructions);
        echo json_encode(['message' => $botResponse]);
        exit();
    }
}

// Return 404 for invalid requests
http_response_code(404);
echo '404 Not Found';
?>
