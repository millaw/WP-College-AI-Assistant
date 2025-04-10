document.addEventListener('DOMContentLoaded', () => {
    const chatMessages = document.getElementById('chat-messages');
    const questionInput = document.getElementById('question-input');
    const askButton = document.getElementById('ask-button');
    const loader = document.getElementById('loader');

    const toggle = document.getElementById('theme-toggle');
    const modeTextSpan = document.querySelector('.mode-text');

    // Initialize text
    modeTextSpan.textContent = document.body.classList.contains('dark-mode')
      ? 'Dark Mode'
      : 'Light Mode';

    toggle.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode');
      document.body.classList.toggle('light-mode');
        
      // Update the text
      modeTextSpan.textContent = document.body.classList.contains('dark-mode')
        ? 'Dark Mode'
        : 'Light Mode';
    });

  
    function addMessage(sender, text) {
      const div = document.createElement('div');
      div.className = sender + '-message';
      div.textContent = text;
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
      return div;
    }
  
    askButton.addEventListener('click', () => {
      const question = questionInput.value.trim();
      if (!question) return;
  
      const userMsg = addMessage('user', question);
      const botMsg = addMessage('bot', '');
  
      questionInput.value = '';
  
      // Send the query to the AI endpoint
      fetch(`${window.location.origin}?ai_query=${encodeURIComponent(question)}`)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            botMsg.textContent = 'An error occurred: ' + data.error;
          } else if (data.response) {
            botMsg.innerHTML = data.response
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/\n/g, "<br>");
          }
          chatMessages.scrollTop = chatMessages.scrollHeight;
        })
        .catch(() => {
          botMsg.textContent = 'An error occurred while processing your query.';
        });
    });
  
    questionInput.addEventListener('keypress', e => {
      if (e.key === 'Enter') askButton.click();
    });
  });
