function galle() {
    const wsUrl = 'wss://2upra.com/ws';
    let ws, currentPage = 1, isLoadingMessages = false, currentConversationId, selectedUser = "";
  
    const getElement = id => document.getElementById(id);
    const userInfoElement = getElement('userInfo');
    const currentUser = userInfoElement.dataset.username;
    const mC = getElement('gcm'), mI = getElement('gct'), sB = getElement('gs'), chatContainer = getElement('gc');
    const selectedUserDisplay = getElement('selectedUserDisplay');
  
    const scrollToBottom = () => mC.scrollTop = mC.scrollHeight;
  
    const createMessageElement = (text, isSent) => {
      const messageElement = document.createElement('div');
      messageElement.textContent = text || 'Mensaje no disponible';
      messageElement.classList.add(isSent ? 'message-sent' : 'message-received');
      return messageElement;
    };
  
    const appendMessage = (text, isSent) => {
      mC.appendChild(createMessageElement(text, isSent));
      scrollToBottom();
    };
  
    const prependMessage = (text, isSent) => {
      mC.prepend(createMessageElement(text, isSent));
    };
  
    const connectWebSocket = () => {
      ws = new WebSocket(wsUrl);
      ws.onopen = () => {}; 
      ws.onclose = () => setTimeout(connectWebSocket, 5000);
      ws.onerror = console.error;
      ws.onmessage = ({ data }) => {
        const { conversationId, sender: messageUser, message: messageText } = JSON.parse(data);
        const isSent = messageUser === currentUser;
  
        if (conversationId.toString() === currentConversationId) {
          appendMessage(messageText, isSent);
          return;
        }
  
        const conversationElement = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"] .mensajes-conversacion`);
        if (!conversationElement) return;
  
        const lastMessageElement = conversationElement.querySelector('.mensaje:last-child');
        const lastMessageSender = lastMessageElement?.dataset.sender;
  
        if (lastMessageSender === (isSent ? 'currentUser' : messageUser)) {
          lastMessageElement.querySelector('.texto').textContent = messageText;
          document.querySelector('.icono-notificacion').style.filter = 'invert(34%) sepia(94%) saturate(7484%) hue-rotate(331deg) brightness(79%) contrast(119%)';
        } else {
          const newMessageElement = document.createElement('div');
          newMessageElement.classList.add('mensaje');
          newMessageElement.dataset.sender = isSent ? 'currentUser' : messageUser;
          newMessageElement.innerHTML = `<p class='texto'>${isSent ? 'Tú: ' : ''}${messageText}</p>`;
          conversationElement.appendChild(newMessageElement);
        }
      };
    };
  
    const handleStartChat = userLogin => {
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get_or_start_conversation', user_login: userLogin }),
      })
        .then(response => response.json())
        .then(data => {
          if (!data.success || !data.data?.conversation) return;
  
          selectedUser = userLogin;
          selectedUserDisplay.textContent = userLogin;
          mC.innerHTML = "";
          currentConversationId = data.data.conversation.id;
  
          data.data.conversation.messages.reverse().forEach(message => {
            appendMessage(message.message_text, message.sender_user === currentUser);
          });
  
          chatContainer.classList.add('active');
          scrollToBottom();
        })
        .catch(console.error);
    };
  
    const handleConversationClick = event => {
      const targetElement = event.target.closest('.conversation-item');
      if (!targetElement) return;
  
      handleStartChat(targetElement.getAttribute('data-chat-user-login'));
  
      if (window.innerWidth <= 640) {
        document.querySelector('.galle-chat-text-block').style.display = 'flex';
        document.querySelector('.user-conversations-block').style.display = 'none';
      }
    };
  
    const loadMoreMessages = () => {
      if (isLoadingMessages || !currentConversationId) return;
  
      isLoadingMessages = true;
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'load_more_messages',
          page: currentPage + 1,
          user_id: userInfoElement.dataset.userId,
          conversation_id: currentConversationId,
        }),
      })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.data?.messages?.length > 0) {
            currentPage++;
            data.data.messages.forEach(message => prependMessage(message.message_text, message.sender_user === currentUser));
          }
          isLoadingMessages = false;
        })
        .catch(error => {
          console.error('Error al cargar más mensajes:', error);
          isLoadingMessages = false;
        });
    };
  
    const sendMessage = () => {
      if (!selectedUser) return alert('Por favor, selecciona un usuario antes de enviar un mensaje.');
      if (!currentConversationId || !mI.value.trim() || ws.readyState !== WebSocket.OPEN) return;
  
      const message = mI.value.trim();
      ws.send(JSON.stringify({
        sender: currentUser,
        receiver: selectedUser,
        message,
        conversationId: currentConversationId,
      }));
  
      mI.value = "";
      appendMessage(message, true);
  
      fetch(galleChat.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': galleChat.nonce,
        },
        body: JSON.stringify({
          receiver_id: selectedUser,
          message_text: message,
          conversation_id: currentConversationId,
        }),
      }).catch(console.error);
  
      scrollToBottom();
    };
  
    document.addEventListener('click', event => {
      if (event.target.closest('.custom-start-chat-btn')) {
        handleStartChat(event.target.closest('.custom-start-chat-btn').getAttribute('data-chat-user-login'));
      } else if (event.target.closest('.mensaje-colab')) {
        const button = event.target.closest('.mensaje-colab');
        const postId = button.getAttribute('data-post-id');
  
        fetch('/wp-admin/admin-ajax.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'get_conversation_by_post_id', post_id: postId }),
        })
          .then(response => response.json())
          .then(data => {
            if (!data.success || !data.data?.conversation) return;
  
            mC.innerHTML = "";
            currentConversationId = data.data.conversation.id;
  
            const artistUsername = button.getAttribute('data-artist-username');
            const collaboratorUsername = button.getAttribute('data-collaborator-username');
            selectedUser = currentUser === artistUsername ? collaboratorUsername : artistUsername;
            selectedUserDisplay.textContent = selectedUser;
  
            data.data.conversation.messages.reverse().forEach(message => {
              appendMessage(message.message_text, message.sender_user === currentUser);
            });
  
            chatContainer.classList.add('active');
            scrollToBottom();
          })
          .catch(console.error);
      } else if (event.target.closest('.conversation-item')) {
        handleConversationClick(event);
      }
    });
  
    mC.addEventListener('scroll', () => {
      if (mC.scrollTop === 0 && !isLoadingMessages) {
        loadMoreMessages();
      }
    });
  
    getElement('close-chat').addEventListener('click', () => {
      chatContainer.classList.remove('active');
    });
  
    sB.addEventListener('click', sendMessage);
  
    connectWebSocket();
    document.querySelector('.icono-notificacion')?.addEventListener('click', () => {
      document.querySelector('.icono-notificacion').style.filter = '';
    });
  }
  