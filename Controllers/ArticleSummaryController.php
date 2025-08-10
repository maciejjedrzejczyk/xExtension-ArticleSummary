<?php

class FreshExtension_ArticleSummary_Controller extends Minz_ActionController
{
  public function debugAction()
  {
    $this->view->_layout(false);
    header('Content-Type: application/json');

    $config = array(
      'oai_url' => FreshRSS_Context::$user_conf->oai_url,
      'oai_key' => FreshRSS_Context::$user_conf->oai_key ? '[SET]' : '[EMPTY]',
      'oai_model' => FreshRSS_Context::$user_conf->oai_model,
      'oai_prompt' => FreshRSS_Context::$user_conf->oai_prompt,
      'oai_provider' => FreshRSS_Context::$user_conf->oai_provider ?: 'openai',
      'oai_auto_summarize' => FreshRSS_Context::$user_conf->oai_auto_summarize ?: 'manual'
    );

    echo json_encode(array(
      'config' => $config,
      'status' => 200
    ));
    return;
  }

  public function summarizeAction()
  {
    $this->view->_layout(false);
    // Set response header to JSON
    header('Content-Type: application/json');

    $oai_url = FreshRSS_Context::$user_conf->oai_url;
    $oai_key = FreshRSS_Context::$user_conf->oai_key;
    $oai_model = FreshRSS_Context::$user_conf->oai_model;
    $oai_prompt = FreshRSS_Context::$user_conf->oai_prompt;
    $oai_provider = FreshRSS_Context::$user_conf->oai_provider ?: 'openai';

    // Debug logging - remove this after fixing
    error_log("ArticleSummary Debug - Provider: " . $oai_provider);
    error_log("ArticleSummary Debug - URL: " . ($oai_url ?: 'EMPTY'));
    error_log("ArticleSummary Debug - Model: " . ($oai_model ?: 'EMPTY'));
    error_log("ArticleSummary Debug - Prompt: " . (strlen($oai_prompt ?: '') > 0 ? 'SET' : 'EMPTY'));
    error_log("ArticleSummary Debug - Key: " . (strlen($oai_key ?: '') > 0 ? 'SET' : 'EMPTY'));

    // Validate configuration based on provider
    $validation_errors = array();
    
    if ($this->isEmpty($oai_url)) {
      $validation_errors[] = 'Base URL is required';
    }
    
    if ($this->isEmpty($oai_model)) {
      $validation_errors[] = 'Model name is required';
    }
    
    if ($this->isEmpty($oai_prompt)) {
      $validation_errors[] = 'System prompt is required';
    }
    
    // Only require API key for OpenAI
    if ($oai_provider === 'openai' && $this->isEmpty($oai_key)) {
      $validation_errors[] = 'API key is required for OpenAI';
    }

    if (!empty($validation_errors)) {
      $error_message = 'Configuration errors: ' . implode(', ', $validation_errors) . ' (Provider: ' . $oai_provider . ')';
      error_log("ArticleSummary Validation Error: " . $error_message);
      
      echo json_encode(array(
        'response' => array(
          'data' => $error_message,
          'error' => 'configuration'
        ),
        'status' => 200
      ));
      return;
    }

    $entry_id = Minz_Request::param('id');
    $entry_dao = FreshRSS_Factory::createEntryDao();
    $entry = $entry_dao->searchById($entry_id);

    if ($entry === null) {
      echo json_encode(array(
        'response' => array(
          'data' => 'Article not found',
          'error' => 'not_found'
        ),
        'status' => 404
      ));
      return;
    }

    $content = $entry->content();
    $markdown_content = $this->htmlToMarkdown($content);

    // Build response based on provider
    if ($oai_provider === "ollama") {
      $successResponse = $this->buildOllamaResponse($oai_url, $oai_key, $oai_model, $oai_prompt, $markdown_content);
    } else {
      // Default to OpenAI-compatible (includes OpenAI and LMStudio)
      $provider_name = ($oai_provider === "lmstudio") ? "lmstudio" : "openai";
      $successResponse = $this->buildOpenAIResponse($oai_url, $oai_key, $oai_model, $oai_prompt, $markdown_content, $provider_name);
    }

    echo json_encode($successResponse);
    return;
  }

  private function buildOpenAIResponse($oai_url, $oai_key, $oai_model, $oai_prompt, $content, $provider_name = 'openai')
  {
    // Clean and build URL
    $base_url = rtrim($oai_url, '/');
    
    // Check if URL already has version path
    if (!preg_match('/\/v\d+$/', $base_url)) {
      $base_url .= '/v1';
    }
    
    $endpoint_url = $base_url . '/chat/completions';

    return array(
      'response' => array(
        'data' => array(
          "oai_url" => $endpoint_url,
          "oai_key" => $oai_key,
          "model" => $oai_model,
          "messages" => array(
            array(
              "role" => "system",
              "content" => $oai_prompt
            ),
            array(
              "role" => "user",
              "content" => $content
            )
          ),
          "max_tokens" => 2048,
          "temperature" => 0.7,
          "stream" => true
        ),
        'provider' => $provider_name,
        'error' => null
      ),
      'status' => 200
    );
  }

  private function buildOllamaResponse($oai_url, $oai_key, $oai_model, $oai_prompt, $content)
  {
    // Clean URL and build Ollama endpoint
    $base_url = rtrim($oai_url, '/');
    $endpoint_url = $base_url . '/api/generate';

    // Combine system prompt with content
    $full_prompt = $oai_prompt . "\n\nArticle content:\n" . $content;

    return array(
      'response' => array(
        'data' => array(
          "oai_url" => $endpoint_url,
          "oai_key" => $oai_key, // May be empty for Ollama
          "model" => $oai_model,
          "prompt" => $full_prompt,
          "stream" => true
        ),
        'provider' => 'ollama',
        'error' => null
      ),
      'status' => 200
    );
  }

  private function isEmpty($item)
  {
    return $item === null || trim($item) === '';
  }

  private function htmlToMarkdown($content)
  {
    // 创建 DOMDocument 对象 - Creating DOMDocument objects
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // 忽略 HTML 解析错误 - Ignore HTML parsing errors
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    libxml_clear_errors();

    // 创建 XPath 对象 - Create XPath objects
    $xpath = new DOMXPath($dom);

    // 定义一个匿名函数来处理节点 - Define an anonymous function to process the node
    $processNode = function ($node, $indentLevel = 0) use (&$processNode, $xpath) {
      $markdown = '';

      // 处理文本节点 - Processing text nodes
      if ($node->nodeType === XML_TEXT_NODE) {
        $markdown .= trim($node->nodeValue);
      }

      // 处理元素节点 - Processing element nodes
      if ($node->nodeType === XML_ELEMENT_NODE) {
        switch ($node->nodeName) {
          case 'p':
          case 'div':
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child);
            }
            $markdown .= "\n\n";
            break;
          case 'h1':
            $markdown .= "# ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h2':
            $markdown .= "## ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h3':
            $markdown .= "### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h4':
            $markdown .= "#### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h5':
            $markdown .= "##### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h6':
            $markdown .= "###### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'a':
            // $markdown .= "[";
            // $markdown .= $processNode($node->firstChild);
            // $markdown .= "](" . $node->getAttribute('href') . ")";
            $markdown .= "`";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "`";
            break;
          case 'img':
            $alt = $node->getAttribute('alt');
            $markdown .= "img: `" . $alt . "`";
            break;
          case 'strong':
          case 'b':
            $markdown .= "**";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "**";
            break;
          case 'em':
          case 'i':
            $markdown .= "*";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "*";
            break;
          case 'ul':
          case 'ol':
            $markdown .= "\n";
            foreach ($node->childNodes as $child) {
              if ($child->nodeName === 'li') {
                $markdown .= str_repeat("  ", $indentLevel) . "- ";
                $markdown .= $processNode($child, $indentLevel + 1);
                $markdown .= "\n";
              }
            }
            $markdown .= "\n";
            break;
          case 'li':
            $markdown .= str_repeat("  ", $indentLevel) . "- ";
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child, $indentLevel + 1);
            }
            $markdown .= "\n";
            break;
          case 'br':
            $markdown .= "\n";
            break;
          case 'audio':
          case 'video':
            $alt = $node->getAttribute('alt');
            $markdown .= "[" . ($alt ? $alt : 'Media') . "]";
            break;
          default:
            // 未考虑到的标签，只保留内部文字内容 - Tags not considered, only the text inside is kept
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child);
            }
            break;
        }
      }

      return $markdown;
    };

    // 获取所有节点 - Get all nodes
    $nodes = $xpath->query('//body/*');

    // 处理所有节点 - Process all nodes
    $markdown = '';
    foreach ($nodes as $node) {
      $markdown .= $processNode($node);
    }

    // 去除多余的换行符 - Remove extra line breaks
    $markdown = preg_replace('/(\n){3,}/', "\n\n", $markdown);
    
    return $markdown;
  }

}
