<?php
/**
 * Debug configuration script
 * Place this in your FreshRSS root directory and access via browser
 * to see what configuration values are actually stored
 */

// Include FreshRSS bootstrap
require_once('constants.php');
require_once(LIB_PATH . '/lib_rss.php');

// Initialize FreshRSS context
FreshRSS_Context::init();

echo "<h2>ArticleSummary Extension Configuration Debug</h2>";

echo "<h3>Raw Configuration Values:</h3>";
echo "<pre>";
echo "oai_url: '" . (FreshRSS_Context::$user_conf->oai_url ?? 'NULL') . "'\n";
echo "oai_key: '" . (FreshRSS_Context::$user_conf->oai_key ?? 'NULL') . "'\n";
echo "oai_model: '" . (FreshRSS_Context::$user_conf->oai_model ?? 'NULL') . "'\n";
echo "oai_prompt: '" . (FreshRSS_Context::$user_conf->oai_prompt ?? 'NULL') . "'\n";
echo "oai_provider: '" . (FreshRSS_Context::$user_conf->oai_provider ?? 'NULL') . "'\n";
echo "</pre>";

echo "<h3>Validation Check:</h3>";
echo "<pre>";

$oai_url = FreshRSS_Context::$user_conf->oai_url;
$oai_key = FreshRSS_Context::$user_conf->oai_key;
$oai_model = FreshRSS_Context::$user_conf->oai_model;
$oai_prompt = FreshRSS_Context::$user_conf->oai_prompt;
$oai_provider = FreshRSS_Context::$user_conf->oai_provider ?: 'openai';

function isEmpty($item) {
    return $item === null || trim($item) === '';
}

echo "Provider: $oai_provider\n";
echo "URL empty: " . (isEmpty($oai_url) ? 'YES' : 'NO') . "\n";
echo "Key empty: " . (isEmpty($oai_key) ? 'YES' : 'NO') . "\n";
echo "Model empty: " . (isEmpty($oai_model) ? 'YES' : 'NO') . "\n";
echo "Prompt empty: " . (isEmpty($oai_prompt) ? 'YES' : 'NO') . "\n";

$required_fields = array(
    'url' => $oai_url, 
    'model' => $oai_model, 
    'prompt' => $oai_prompt
);

if ($oai_provider === 'openai') {
    $required_fields['key'] = $oai_key;
}

$missing_fields = array();
foreach ($required_fields as $field_name => $field_value) {
    if (isEmpty($field_value)) {
        $missing_fields[] = $field_name;
    }
}

if (!empty($missing_fields)) {
    echo "\nMISSING FIELDS: " . implode(', ', $missing_fields) . "\n";
} else {
    echo "\nAll required fields are present!\n";
}

echo "</pre>";

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Check the raw values above to see what's actually stored</li>";
echo "<li>If any required field shows as NULL or empty, reconfigure the extension</li>";
echo "<li>Make sure you selected 'LMStudio' as the provider</li>";
echo "<li>Delete this file after debugging for security</li>";
echo "</ol>";
?>
