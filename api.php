<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Define global constants for API key and assistant ID
define('API_KEY', '');
define('ASSISTANT_ID', 'asst_nfEcwmczLGDpmrCmoJk4MVFS');

// Function to create a new thread
function createNewThread() {
    $url = "https://api.openai.com/v1/threads";

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . API_KEY,
        "OpenAI-Beta: assistants=v2"
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array()));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (isset($responseData['id'])) {
        return $responseData['id']; // Return the newly created thread ID
    } else {
        error_log("Error creating thread: " . json_encode($responseData));
        return null;
    }
}

// Function to add a message to the thread
function addMessageToThread($threadId, $message) {
    $url = "https://api.openai.com/v1/threads/{$threadId}/messages";

    $data = array(
        "role" => "user",
        "content" => $message
    );

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . API_KEY,
        "OpenAI-Beta: assistants=v2"
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (isset($responseData['id'])) {
        return $responseData['id']; // Return the message ID
    } else {
        error_log("Error adding message to thread: " . json_encode($responseData));
        return null;
    }
}

// Function to run the assistant on the thread
function runAssistantOnThread($threadId) {
    $url = "https://api.openai.com/v1/threads/{$threadId}/runs";

    $data = array(
        "assistant_id" => ASSISTANT_ID
        
    );

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . API_KEY,
        "OpenAI-Beta: assistants=v2"
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);
    // $responseData = json_decode($response, true);

    // if (isset($responseData['id'])) {
    //     return $responseData['id']; // Return the run ID
    // } else {
    //     error_log("Error running assistant on thread: " . json_encode($responseData));
    //     return null;
    // }
}

// Function to get messages from the thread
function getThreadMessages($threadId) {
    //$url = "https://api.openai.com/v1/threads/{$threadId}";
    $url = "https://api.openai.com/v1/threads/{$threadId}/messages";

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . API_KEY,
        "OpenAI-Beta: assistants=v2"
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    //  echo $response;
    //  exit();
    curl_close($ch);
    
    $responseData = json_decode($response, true);

    if (isset($responseData['data'])) {
        return $responseData['data']; // Return the messages
    } else {
        error_log("Error getting thread ({$threadId}) messages: " . json_encode($responseData));
        return null;
    }
}

// Handle incoming message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    if (isset($requestData['message'])) {
        $userInput = $requestData['message'];

        // Initialize a thread ID for the session if not already set
        if (!isset($_SESSION['thread_id'])) {
            // Create a new thread for the session and get the thread ID
            $_SESSION['thread_id'] = createNewThread();
        }

        if ($_SESSION['thread_id']) {
            $threadId = $_SESSION['thread_id'];
            addMessageToThread($threadId, $userInput);
            runAssistantOnThread($threadId);
            sleep(3);
            $messages = getThreadMessages($threadId);
            if ($messages != null) {
                // Find the last assistant message
                // echo json_encode($messages);
                // exit();  
                $assistantMessage = null;
                $messageFound = false;
                foreach ($messages as $message) {

                    if ($message['role'] == 'assistant' && !empty($message['content'])) {
                        foreach ($message['content'] as $contentItem) {
                            if ($contentItem['type'] == 'text' && isset($contentItem['text']['value'])) {
                                //echo json_encode($contentItem['text']['value']);
                                //exit();
                                $assistantMessage = $contentItem['text']['value'];
                                $messageFound = true;
                                break;
                            }
                        }
                    }
                    if ($messageFound) {
                        break; // Break the outer loop
                    }


                }
                echo json_encode(['message' => $assistantMessage]);
            } else {
                echo json_encode(['message' => 'Error getting thread messages.']);
            }
        } else {
            echo json_encode(['message' => 'Error creating new thread.']);
        }
        exit();
    }
}

// Handle closing a chat (optional)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    if (isset($requestData['close_chat']) && $requestData['close_chat'] === true) {
        if (isset($_SESSION['thread_id'])) {
            $threadId = $_SESSION['thread_id'];
            // Optionally, you could delete the thread or mark it as closed
            unset($_SESSION['thread_id']);

            echo json_encode(['message' => 'Chat closed and thread terminated.']);
            exit();
        }
    }
}

// Return 404 for invalid requests
http_response_code(404);
echo '404 Not Found';
?>
