/*
 * NK AI Chatbot Logic
 * Handles Chat UI, Theater Mode, and Product Rendering
 */

document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("nk-chatbot-container");
  const toggleBtn = document.getElementById("nk-chatbot-toggle");
  const closeBtn = document.getElementById("nk-chat-close");
  const theaterBtn = document.getElementById("nk-theater-toggle");
  const resetBtn = document.getElementById("nk-reset-menu"); // New Menu Button
  const input = document.getElementById("nk-chat-input");
  const sendBtn = document.getElementById("nk-chat-send");
  const messagesArea = document.getElementById("nk-chat-messages");
  const pillsContainer = document.querySelector('.nk-chat-pills');
  const quickActionsContainer = document.querySelector('.nk-quick-actions');
  let hasUserSentMessage = false;

  function updateInterfaceVisibility() {
      const botAvatar = document.querySelector('.nk-bot-avatar');
      
      // 1. Handle Back Button vs Avatar (Left side swap)
      if (resetBtn) {
          if (hasUserSentMessage) {
              resetBtn.style.setProperty('display', 'flex', 'important');
              if (botAvatar) botAvatar.style.display = 'none'; // Hide avatar when back is visible
          } else {
              resetBtn.style.setProperty('display', 'none', 'important');
              if (botAvatar) botAvatar.style.display = 'flex'; // Show avatar when at start
          }
      }

      // 2. Handle Quick Actions (Tiles)
      if (quickActionsContainer) {
          if (hasUserSentMessage) {
              quickActionsContainer.style.display = 'none';
          } else {
              quickActionsContainer.style.display = 'grid';
          }
      }

      // 3. Handle Pills
      if (pillsContainer) {
          if (hasUserSentMessage) {
              pillsContainer.style.display = 'none';
          } else if (input.value.length > 0) {
              pillsContainer.style.display = 'none';
          } else {
              pillsContainer.style.display = 'flex';
          }
      }
  }

  // Handle Menu/Reset Button Click (Back to Menu + Clear)
  if (resetBtn) {
      resetBtn.addEventListener("click", () => {
          // 1. Reset State & History
          hasUserSentMessage = false;
          chatHistory = []; 
          input.value = "";

          // 2. Clear Chat UI (Keep ONLY Welcome message)
          const messages = messagesArea.querySelectorAll('.nk-message');
          messages.forEach((msg, index) => {
              if (index > 0) msg.remove(); // Keep first bot message (Welcome)
          });
          
          // Remove loading indicators
          document.querySelectorAll('[id^="nk-loading-"]').forEach(el => el.remove());

          // 3. Restore Visibility (Swaps back button for avatar)
          updateInterfaceVisibility();
          
          // 4. Scroll to top
          messagesArea.scrollTop = 0;
      });
  }

  // Toggle Chat
  function toggleChat() {
    container.classList.toggle("nk-chatbot-open");
  }

  toggleBtn.addEventListener("click", toggleChat);
  closeBtn.addEventListener("click", () => {
    container.classList.remove("nk-chatbot-open");
    // Also exit theater mode on close
    container.classList.remove("nk-mode-theater");
  });

  // Toggle Theater Mode (Deep Explore)
  if (theaterBtn) {
    theaterBtn.addEventListener("click", () => {
        container.classList.toggle("nk-mode-theater");
        // Scroll to bottom when mode changes
        setTimeout(() => {
        messagesArea.scrollTop = messagesArea.scrollHeight;
        }, 300);
    });
  }

    // Handle Quick Actions & Pills (Dynamic Menu Logic)
    const triggerButtons = document.querySelectorAll('.nk-quick-actions .nk-chip, .nk-chat-pills .nk-pill');
    
    triggerButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const button = e.currentTarget;
            const action = button.getAttribute('data-action');
            const dynamicQuery = button.getAttribute('data-query');
            const shouldSendImmediately = (button.getAttribute('data-send') === 'true');
            
            // Priority: Dynamic Query > Text Label
            let queryText = dynamicQuery || button.innerText.trim();
            
            if (shouldSendImmediately) {
                // Direct Send (Tiles)
                hasUserSentMessage = true;
                updateInterfaceVisibility();
                
                addUserMessage(queryText);
                handleBotResponse(queryText, action);
            } else {
                 // Populate input (Pills)
                input.value = queryText;
                input.focus();
                updateInterfaceVisibility();
            }
        });
    });

  let isBotThinking = false;

  function setLoadingState(isLoading) {
      isBotThinking = isLoading;
      if (isLoading) {
          sendBtn.disabled = true;
          input.disabled = true;
          sendBtn.style.opacity = "0.5";
          sendBtn.style.cursor = "not-allowed";
      } else {
          sendBtn.disabled = false;
          input.disabled = false;
          sendBtn.style.opacity = "1";
          sendBtn.style.cursor = "pointer";
          input.focus();
      }
  }

  // Send Message
  function sendMessage() {
    if (isBotThinking) return; // Prevent double sending
    
    const text = input.value.trim();
    if (!text) return;

    // Mark as started so pills never show again
    hasUserSentMessage = true;
    updateInterfaceVisibility();

    addUserMessage(text);
    input.value = "";
    handleBotResponse(text);
  }

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
  
  // Real-time visibility check
  input.addEventListener("input", updateInterfaceVisibility);

  // UI Helpers
  function addUserMessage(text) {
    const msgDiv = document.createElement("div");
    msgDiv.className = "nk-message nk-message-user";
    msgDiv.innerHTML = `<div class="nk-message-content">${text}</div>`;
    messagesArea.appendChild(msgDiv);
    scrollToBottom();
  }

  function addBotMessage(content, type = "text", data = null) {
    const msgDiv = document.createElement("div");
    msgDiv.className = "nk-message nk-message-bot";

    // Base content (Text)
    let html = `<div class="nk-message-content">${content}</div>`;

    // Product Carousel
    if (type === "products" && data) {
      html += `<div class="nk-product-carousel">`;
      data.forEach((product) => {
        html += `
                <div class="nk-product-card">
                    <img src="${product.image}" alt="${product.title}" class="nk-product-image">
                    <div class="nk-product-details">
                        <!-- Linked Title (Black, Underlined) -->
                        <a href="${product.link}" class="nk-product-title-link" target="_blank">
                             ${product.title}
                        </a>
                        
                        <span class="nk-product-price">${product.price}</span>
                        
                        <!-- Description/Details -->
                        <div class="nk-product-desc">${product.desc}</div>
                        
                        <a href="${product.link}" class="nk-product-btn">View Product</a>
                    </div>
                </div>`;
      });
      html += `</div>`;
    }

    msgDiv.innerHTML = html;
    messagesArea.appendChild(msgDiv);
    scrollToBottom();
  }

  function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
  }

    // Message History for Context
    let chatHistory = [];

    // Real AJAX Logic
    function handleBotResponse(userText, action = null) {
        // Show Typing Indicator (Animated Dots)
        const loadingId = 'nk-loading-' + Date.now();
        const msgDiv = document.createElement('div');
        msgDiv.className = 'nk-message nk-message-bot';
        msgDiv.id = loadingId;
        msgDiv.innerHTML = `<div class="nk-message-content">
            <div class="nk-typing">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>`;
        messagesArea.appendChild(msgDiv);
        scrollToBottom();

        // Update History
        chatHistory.push({ role: 'user', content: userText });

        // Prepare Data
        const formData = new FormData();
        formData.append('action', 'nk_chat_response');
        formData.append('nonce', nkChatbotConfig.nonce);
        formData.append('message', userText);
        formData.append('history', JSON.stringify(chatHistory));

        // Note: 'action' param (dosing/protocols/etc) can be handled client-side for speed
        // or effectively passed to system prompt. For now, we relay standard logic.
        
        // Client-side aesthetic overrides (Instant response for known actions)
        if (action === 'dosing') {
             removeLoading(loadingId);
             const reply = '### Common Dosing Protocols\n\n**BPC-157:** 500mcg daily.\n**TB-500:** 2.5mg twice weekly.\n\n*Always consult a professional before starting.*';
             addBotMessage(reply);
             chatHistory.push({ role: 'assistant', content: reply });
             return;
        }

        // Disable Input/Button
        setLoadingState(true);

        fetch(nkChatbotConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            removeLoading(loadingId);
            setLoadingState(false);
            
            if (data.success) {
                const aiContent = data.data.content;
                const type = data.data.type || 'text';
                const products = data.data.products || null;
                
                // Format formatting: Bold, Numbers, and normalized Line Breaks (<br>)
                let formattedContent = aiContent.trim()
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold
                    .replace(/\n*\s*(\d+\.)\s/g, '\n$1 ') // Ensure numbers start on new line
                    .replace(/\n/g, '<br><br>') // Convert all \n to <br>
                    .replace(/(<br>\s*){3,}/g, '<br><br>') // Max 2 <br> at a time
                    .trim();

                addBotMessage(formattedContent, type, products);
                chatHistory.push({ role: 'assistant', content: aiContent });
            } else {
                addBotMessage("Reviewing research... Please check your API settings or try again.");
            }
        })
        .catch(err => {
            console.error(err);
            removeLoading(loadingId);
            setLoadingState(false);
            addBotMessage("Connection error. Please try again.");
        });
    }

    function removeLoading(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }
});
