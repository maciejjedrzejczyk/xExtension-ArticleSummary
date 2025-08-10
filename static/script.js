if (document.readyState && document.readyState !== 'loading') {
  configureSummarizeButtons();
} else {
  document.addEventListener('DOMContentLoaded', configureSummarizeButtons, false);
}

function configureSummarizeButtons() {
  document.getElementById('global').addEventListener('click', function (e) {
    for (var target = e.target; target && target != this; target = target.parentNode) {
      
      if (target.matches('.flux_header')) {
        const summaryBtn = target.nextElementSibling.querySelector('.oai-summary-btn');
        if (summaryBtn) {
          const autoMode = summaryBtn.dataset.auto;
          if (autoMode === 'automatic') {
            summaryBtn.innerHTML = 'Auto-summarizing...';
            // Trigger automatic summarization after a short delay
            setTimeout(() => {
              if (summaryBtn.dataset.request) {
                summarizeButtonClick(summaryBtn);
              }
            }, 500);
          } else {
            summaryBtn.innerHTML = 'Summarize';
          }
        }
      }

      if (target.matches('.oai-summary-btn')) {
        e.preventDefault();
        e.stopPropagation();
        if (target.dataset.request) {
          summarizeButtonClick(target);
        }
        break;
      }
    }
  }, false);

  // Handle automatic summarization for articles that are already visible
  initializeVisibleArticles();
}

function initializeVisibleArticles() {
  // Find all visible summary buttons and initialize them
  const summaryButtons = document.querySelectorAll('.oai-summary-btn');
  summaryButtons.forEach(button => {
    const autoMode = button.dataset.auto;
    const container = button.parentNode;
    
    if (autoMode === 'automatic') {
      // Check if this article is currently visible/expanded
      const article = button.closest('.flux');
      if (article && !article.classList.contains('flux_header_only')) {
        button.innerHTML = 'Auto-summarizing...';
        // Trigger automatic summarization
        setTimeout(() => {
          if (button.dataset.request && !container.classList.contains('oai-loading')) {
            summarizeButtonClick(button);
          }
        }, 1000);
      } else {
        button.innerHTML = 'Auto-summarize';
      }
    } else {
      button.innerHTML = 'Summarize';
    }
  });
}

function setOaiState(container, statusType, statusMsg, summaryText) {
  const button = container.querySelector('.oai-summary-btn');
  const content = container.querySelector('.oai-summary-content');
  const autoMode = button.dataset.auto;
  
  // Set different states based on statusType
  if (statusType === 1) { // Loading
    container.classList.add('oai-loading');
    container.classList.remove('oai-error');
    content.innerHTML = statusMsg || 'Loading...';
    button.disabled = true;
    if (autoMode === 'automatic') {
      button.innerHTML = 'Auto-summarizing...';
    } else {
      button.innerHTML = 'Summarizing...';
    }
  } else if (statusType === 2) { // Error
    container.classList.remove('oai-loading');
    container.classList.add('oai-error');
    content.innerHTML = statusMsg || 'Error occurred';
    button.disabled = false;
    if (autoMode === 'automatic') {
      button.innerHTML = 'Auto-summarize (Error)';
    } else {
      button.innerHTML = 'Summarize (Retry)';
    }
  } else { // Success/Normal
    container.classList.remove('oai-loading');
    container.classList.remove('oai-error');
    if (statusMsg === 'finish') {
      button.disabled = false;
      if (autoMode === 'automatic') {
        button.innerHTML = 'Auto-summarized ✓';
      } else {
        button.innerHTML = 'Summarized ✓';
      }
    }
  }

  if (summaryText) {
    content.innerHTML = summaryText.replace(/(?:\r\n|\r|\n)/g, '<br>');
  }
}

async function summarizeButtonClick(target) {
  var container = target.parentNode;
  if (container.classList.contains('oai-loading')) {
    return;
  }

  setOaiState(container, 1, 'Loading...', null);

  // Get the PHP endpoint URL
  var url = target.dataset.request;
  var data = {
    ajax: true,
    _csrf: context.csrf
  };

  try {
    const response = await axios.post(url, data, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    const xresp = response.data;
    console.log('PHP Response:', xresp);

    if (response.status !== 200 || !xresp.response || !xresp.response.data) {
      throw new Error('Request failed - Invalid response from server');
    }

    if (xresp.response.error) {
      setOaiState(container, 2, xresp.response.data, null);
      return;
    }

    // Parse parameters returned by PHP
    const oaiParams = xresp.response.data;
    const oaiProvider = xresp.response.provider;
    
    console.log('Provider:', oaiProvider, 'Params:', oaiParams);
    
    if (oaiProvider === 'ollama') {
      await sendOllamaRequest(container, oaiParams);
    } else {
      // Default to OpenAI-compatible (includes OpenAI and LMStudio)
      await sendOpenAIRequest(container, oaiParams);
    }
  } catch (error) {
    console.error('Request error:', error);
    setOaiState(container, 2, 'Request failed: ' + error.message, null);
  }
}

async function sendOpenAIRequest(container, oaiParams) {
  try {
    setOaiState(container, 1, 'Connecting to AI service...', null);
    
    // Prepare request body (remove URL and key from body)
    let requestBody = { ...oaiParams };
    delete requestBody.oai_url;
    delete requestBody.oai_key;

    console.log('OpenAI Request URL:', oaiParams.oai_url);
    console.log('OpenAI Request Body:', requestBody);

    const headers = {
      'Content-Type': 'application/json'
    };

    // Add authorization header if API key is provided
    if (oaiParams.oai_key && oaiParams.oai_key.trim() !== '') {
      headers['Authorization'] = `Bearer ${oaiParams.oai_key}`;
    }

    const response = await fetch(oaiParams.oai_url, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('OpenAI API Error:', response.status, errorText);
      throw new Error(`API request failed (${response.status}): ${errorText}`);
    }

    setOaiState(container, 1, 'Generating summary...', null);

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let fullText = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        setOaiState(container, 0, 'finish', null);
        break;
      }

      const chunk = decoder.decode(value, { stream: true });
      const lines = chunk.split('\n');

      for (const line of lines) {
        if (line.trim() === '') continue;
        if (line.startsWith('data: ')) {
          const data = line.slice(6);
          if (data === '[DONE]') {
            setOaiState(container, 0, 'finish', null);
            return;
          }
          
          try {
            const json = JSON.parse(data);
            const content = json.choices?.[0]?.delta?.content || '';
            if (content) {
              fullText += content;
              setOaiState(container, 0, null, marked.parse(fullText));
            }
          } catch (e) {
            console.warn('Failed to parse streaming JSON:', e, 'Data:', data);
          }
        }
      }
    }
  } catch (error) {
    console.error('OpenAI request error:', error);
    setOaiState(container, 2, 'AI service error: ' + error.message, null);
  }
}

async function sendOllamaRequest(container, oaiParams) {
  try {
    setOaiState(container, 1, 'Connecting to Ollama...', null);
    
    console.log('Ollama Request URL:', oaiParams.oai_url);
    console.log('Ollama Request Body:', oaiParams);

    const headers = {
      'Content-Type': 'application/json'
    };

    // Only add authorization if API key is provided and not empty
    if (oaiParams.oai_key && oaiParams.oai_key.trim() !== '') {
      headers['Authorization'] = `Bearer ${oaiParams.oai_key}`;
    }

    // Prepare request body (remove URL and key from body)
    let requestBody = { ...oaiParams };
    delete requestBody.oai_url;
    delete requestBody.oai_key;

    const response = await fetch(oaiParams.oai_url, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Ollama API Error:', response.status, errorText);
      throw new Error(`Ollama request failed (${response.status}): ${errorText}`);
    }

    setOaiState(container, 1, 'Generating summary...', null);

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let fullText = '';
    let buffer = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        setOaiState(container, 0, 'finish', null);
        break;
      }

      buffer += decoder.decode(value, { stream: true });
      
      // Process complete JSON objects from the buffer
      let endIndex;
      while ((endIndex = buffer.indexOf('\n')) !== -1) {
        const jsonString = buffer.slice(0, endIndex).trim();
        if (jsonString) {
          try {
            const json = JSON.parse(jsonString);
            if (json.response) {
              fullText += json.response;
              setOaiState(container, 0, null, marked.parse(fullText));
            }
            if (json.done) {
              setOaiState(container, 0, 'finish', null);
              return;
            }
          } catch (e) {
            console.warn('Failed to parse Ollama JSON:', e, 'JSON:', jsonString);
          }
        }
        // Remove the processed part from the buffer
        buffer = buffer.slice(endIndex + 1);
      }
    }
  } catch (error) {
    console.error('Ollama request error:', error);
    setOaiState(container, 2, 'Ollama error: ' + error.message, null);
  }
}
