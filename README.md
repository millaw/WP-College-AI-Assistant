# WP College AI Assistant

## Description
The WP College AI Assistant is a WordPress plugin that provides AI-driven assistance for college-related tasks. It integrates with WordPress content, PDFs, and external AI models to answer user queries effectively.

## Features
- **AI-Powered Assistance**: Uses an AI model to process user queries.
- **WordPress Content Search**: Searches posts and pages for relevant information.
- **PDF Search**: Analyzes PDFs linked in WordPress content for relevant data.
- **Admin Settings**: Configure API URL and API Key via the WordPress admin dashboard.
- **Shortcode**: Use `[college_ai_assistant]` to display the assistant on any page or post.

## Installation
1. Download the plugin files.
2. Upload the plugin folder to the `wp-content/plugins/` directory.
3. Activate the plugin through the WordPress admin dashboard.

## Usage
1. Add the shortcode `[college_ai_assistant]` to any page or post.
2. Configure the API settings under **Settings > AI Assistant**.
3. Interact with the assistant on the frontend.

## File Structure
```
wp-college-ai-assistant/
├── wp-college-ai-assistant.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   └── js/
│       └── script.js
├── classes/
│   ├── OllamaStream.php
│   └── PDF/
│       └── PDFHelper.php
```

## AI Model Integration
This plugin is designed to work with a local installation of the `mistral:instruct` AI model. Ensure the AI model is properly installed and accessible via the configured API URL.

## Multisite Compatibility
The plugin is compatible with both single-site and multisite WordPress installations:

- **Single-Site**: Settings are stored and retrieved using `get_option` and `update_option`.
- **Multisite**: When installed in a multisite network, the plugin uses `get_site_option` and `update_site_option` to share settings across the network or manage them independently for each site.

## Security
- All user inputs are sanitized and validated.
- API requests and file operations include error handling.

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- `pdftotext` command-line tool installed on the server

## Future Improvements
- Add support for multilingual queries.
- Enhance AI model integration for better contextual understanding.
- Optimize performance for large datasets.

## License
This plugin is licensed under the GPLv3.

## Author
Milla Wynn ([GitHub](https://github.com/millaw))