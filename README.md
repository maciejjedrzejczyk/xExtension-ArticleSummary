# FreshRSS Article Summary Extension

- [中文 README](README_zh.md)
- [English README](README.md)

This enhanced extension for FreshRSS allows users to generate summaries of articles using various AI providers including **OpenAI**, **LMStudio**, and **Ollama**. The extension provides a user-friendly interface to configure different AI services and generates summaries with a single click.

## Features

- **Multiple AI Providers**: Support for OpenAI, LMStudio (local), and Ollama (local)
- **Manual & Automatic Modes**: Choose between manual button-click or automatic summarization
- **Easy Configuration**: Simple form-based configuration for each provider
- **One-Click Summarization**: Adds a "summarize" button to each article (manual mode)
- **Auto-Summarization**: Automatically generates summaries when articles are opened (automatic mode)
- **Streaming Support**: Real-time streaming responses for faster user experience
- **Markdown Support**: Converts HTML content to Markdown before sending to AI
- **Local LLM Support**: Perfect for privacy-conscious users who want to run AI locally
- **Error Handling**: Comprehensive error handling with helpful debugging information

## Supported AI Providers

### OpenAI
- **Use case**: Cloud-based AI with high-quality results
- **Requirements**: OpenAI API key
- **Models**: gpt-3.5-turbo, gpt-4, etc.

### LMStudio
- **Use case**: Local AI models with OpenAI-compatible API
- **Requirements**: LMStudio installed and running
- **Models**: Any model supported by LMStudio
- **Default URL**: http://localhost:1234

### Ollama
- **Use case**: Local AI models with native Ollama API
- **Requirements**: Ollama installed and running
- **Models**: llama2, mistral, codellama, etc.
- **Default URL**: http://localhost:11434

## Installation

1. **Download the Extension**: Clone or download this repository to your FreshRSS extensions directory:
   ```bash
   cd /path/to/freshrss/extensions
   git clone https://github.com/your-repo/xExtension-ArticleSummary.git
   ```

2. **Enable the Extension**: 
   - Go to FreshRSS → Settings → Extensions
   - Find "ArticleSummary" and click "Enable"

3. **Configure the Extension**: 
   - Click "Configure" next to the ArticleSummary extension
   - Choose your AI provider and enter the required settings

## Configuration

### For OpenAI
1. **Provider**: Select "OpenAI"
2. **Summarization Mode**: Choose "Manual" or "Automatic"
3. **Base URL**: `https://api.openai.com`
4. **API Key**: Your OpenAI API key
5. **Model**: `gpt-3.5-turbo` or `gpt-4`
6. **Prompt**: `Please provide a concise summary of the following article:`

### For LMStudio
1. **Provider**: Select "LMStudio"
2. **Summarization Mode**: Choose "Manual" or "Automatic"
3. **Base URL**: `http://localhost:1234` (or your custom port)
4. **API Key**: Leave empty (not required)
5. **Model**: The model name shown in LMStudio
6. **Prompt**: `Please provide a concise summary of the following article:`

**LMStudio Setup**:
- Download and install LMStudio
- Load a model (e.g., Llama 2, Mistral)
- Start the local server (usually on port 1234)

### For Ollama
1. **Provider**: Select "Ollama"
2. **Summarization Mode**: Choose "Manual" or "Automatic"
3. **Base URL**: `http://localhost:11434`
4. **API Key**: Leave empty (not required)
5. **Model**: `llama2`, `mistral`, `codellama`, etc.
6. **Prompt**: `Please provide a concise summary of the following article:`

**Ollama Setup**:
```bash
# Install Ollama
curl -fsSL https://ollama.ai/install.sh | sh

# Pull a model
ollama pull llama2

# Start Ollama (usually runs automatically)
ollama serve
```

### Summarization Modes

**Manual Mode** (Default):
- Shows a "Summarize" button on each article
- Click the button to generate a summary on demand
- Lower API usage, more control

**Automatic Mode**:
- Automatically generates summaries when articles are opened
- No button clicking required
- ⚠️ **Warning**: Higher API usage - summaries are generated for every article you view

## Usage

1. **Configure** the extension with your preferred AI provider
2. **Navigate** to any article in FreshRSS
3. **Click** the "Summarize" button that appears at the top of each article
4. **Wait** for the AI to generate and stream the summary
5. **Read** the generated summary displayed below the button

## Testing Your Setup

Use the included test script to verify your configuration:

```bash
cd /path/to/extension
php test_api.php
```

Edit the configuration variables in `test_api.php` to match your setup before running.

## Troubleshooting

### Common Issues

1. **"Missing required configuration"**
   - Ensure all required fields are filled in the configuration
   - For local services (LMStudio/Ollama), API key is optional

2. **"Request failed" or connection errors**
   - Check if your AI service is running
   - Verify the base URL and port number
   - Test connectivity using the test script

3. **"Model not found" errors**
   - For LMStudio: Ensure a model is loaded in the interface
   - For Ollama: Run `ollama list` to see available models
   - Check that the model name matches exactly

4. **Streaming issues**
   - Some models may not support streaming
   - Check browser console for JavaScript errors
   - Verify your AI service supports streaming responses

### LMStudio Specific
- Make sure the server is started in LMStudio
- Check that the model is fully loaded
- Verify the port number (default: 1234)

### Ollama Specific
- Ensure Ollama service is running: `ollama serve`
- Check available models: `ollama list`
- Pull models if needed: `ollama pull model-name`

## Dependencies

- **Axios**: HTTP client for API requests
- **Marked**: Markdown parser for displaying formatted summaries
- **FreshRSS**: Version 1.20.0 or higher recommended

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Changelog

### Version 0.3.0 (2025-08-10)
- ✨ Added automatic summarization mode
- ✨ Enhanced UI with mode-specific styling
- 🎨 Improved button states and visual feedback
- 📝 Added comprehensive mode documentation

### Version 0.2.0 (2025-08-10)
- ✨ Added LMStudio support
- ✨ Enhanced Ollama integration
- 🐛 Fixed streaming response handling
- 🐛 Fixed URL construction issues
- 🔧 Improved error handling and debugging
- 🎨 Enhanced configuration UI
- 📝 Added comprehensive documentation

### Version 0.1.1 (2024-11-20)
- 🐛 Fixed summary button display issues in title list

## Acknowledgments

- Thanks to the FreshRSS community for providing a robust RSS platform
- Original extension by Liang
- Enhanced for local LLM support and improved reliability