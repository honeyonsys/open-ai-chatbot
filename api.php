<?php

// OpenAI API endpoint



// Function to send message to OpenAI API and get response
function getOpenAIResponse($message) {
    // API endpoint
    $url = "https://api.openai.com/v1/chat/completions";
    // Your OpenAI API key
    $apiKey = '[YOUR-API-KEY]';
    // Request payload
    $data = array(
        "model" => "gpt-3.5-turbo",
        "messages" => array(
            array(
                "role" => "system",
                "content" => $message
            )
        )
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
    //echo $response;



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
        $botResponse = getOpenAIResponse($userInput);
        echo json_encode(['message' => $botResponse]);
        exit();
    }
}

// Return 404 for invalid requests
http_response_code(404);
echo '404 Not Found';
