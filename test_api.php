<?php
/**
 * Simple test script to verify API connectivity
 * Run this script to test your LMStudio or Ollama setup
 * 
 * Usage: php test_api.php
 */

// Configuration - Update these values to match your setup
$config = [
    'provider' => 'lmstudio', // 'openai', 'lmstudio', or 'ollama'
    'base_url' => 'http://192.168.0.122:1234', // Your API base URL
    'api_key' => '', // API key (optional for local services)
    'model' => 'llama-3.2-3b-instruct', // Your model name
    // 'provider' => 'ollama', // 'openai', 'lmstudio', or 'ollama'
    // 'base_url' => 'http://192.168.0.122:11434', // Your API base URL
    // 'api_key' => '', // API key (optional for local services)
    // 'model' => 'llama3.2:latest', // Your model name
    'prompt' => 'Please summarize the following article in 2-3 sentences:'
];

$test_content = "This is a test article about artificial intelligence. AI has been rapidly advancing in recent years, with large language models becoming increasingly capable of understanding and generating human-like text. These models are now being used for various applications including content summarization, code generation, and conversational AI.";

function testOpenAICompatible($config, $content) {
    $base_url = rtrim($config['base_url'], '/');
    if (!preg_match('/\/v\d+$/', $base_url)) {
        $base_url .= '/v1';
    }
    $url = $base_url . '/chat/completions';
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            ['role' => 'system', 'content' => $config['prompt']],
            ['role' => 'user', 'content' => $content]
        ],
        'max_tokens' => 150,
        'temperature' => 0.7,
        'stream' => false // Non-streaming for testing
    ];
    
    $headers = ['Content-Type: application/json'];
    if (!empty($config['api_key'])) {
        $headers[] = 'Authorization: Bearer ' . $config['api_key'];
    }
    
    return makeRequest($url, $data, $headers);
}

function testOllama($config, $content) {
    $url = rtrim($config['base_url'], '/') . '/api/generate';
    
    $full_prompt = $config['prompt'] . "\n\nArticle content:\n" . $content;
    
    $data = [
        'model' => $config['model'],
        'prompt' => $full_prompt,
        'stream' => false
    ];
    
    $headers = ['Content-Type: application/json'];
    if (!empty($config['api_key'])) {
        $headers[] = 'Authorization: Bearer ' . $config['api_key'];
    }
    
    return makeRequest($url, $data, $headers);
}

function makeRequest($url, $data, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => empty($error) && $httpCode === 200,
        'http_code' => $httpCode,
        'error' => $error,
        'response' => $response
    ];
}

// Main test execution
echo "Testing API connectivity...\n";
echo "Provider: {$config['provider']}\n";
echo "Base URL: {$config['base_url']}\n";
echo "Model: {$config['model']}\n\n";

if ($config['provider'] === 'ollama') {
    $result = testOllama($config, $test_content);
} else {
    $result = testOpenAICompatible($config, $test_content);
}

if ($result['success']) {
    echo "✅ SUCCESS! API is responding correctly.\n\n";
    echo "Response:\n";
    $response_data = json_decode($result['response'], true);
    
    if ($config['provider'] === 'ollama') {
        echo $response_data['response'] ?? 'No response content found';
    } else {
        echo $response_data['choices'][0]['message']['content'] ?? 'No response content found';
    }
    echo "\n\n";
} else {
    echo "❌ FAILED! API test unsuccessful.\n\n";
    echo "HTTP Code: {$result['http_code']}\n";
    if (!empty($result['error'])) {
        echo "cURL Error: {$result['error']}\n";
    }
    if (!empty($result['response'])) {
        echo "Response: {$result['response']}\n";
    }
}

echo "\nTroubleshooting tips:\n";
echo "1. Make sure your AI service is running\n";
echo "2. Check the base URL and port number\n";
echo "3. Verify the model name is correct\n";
echo "4. For LMStudio: Ensure the server is started and a model is loaded\n";
echo "5. For Ollama: Run 'ollama list' to see available models\n";
?>
