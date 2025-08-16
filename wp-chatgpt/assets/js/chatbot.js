(function(){
	const config = window.WPChatGPTConfig || {};

	function createElement(tag, className, children){
		const el = document.createElement(tag);
		if (className) el.className = className;
		(children || []).forEach(c => el.appendChild(c));
		return el;
	}

	function createButton(text){
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.textContent = text;
		return btn;
	}

	function renderFloatingWidget(){
		if (!config || !config.restUrl) return;

		const wrapper = createElement('div', 'wp-chatgpt-floating');
		wrapper.classList.add(config.position === 'bottom-left' ? 'bottom-left' : 'bottom-right');

		const header = createElement('div', 'wp-chatgpt-header');
		header.style.backgroundColor = config.accentColor || '#4f46e5';
		header.textContent = config.widgetTitle || 'Ask AI';

		const body = createElement('div', 'wp-chatgpt-body');
		const messagesEl = createElement('div', 'wp-chatgpt-messages');
		const inputRow = createElement('div', 'wp-chatgpt-input-row');
		const input = document.createElement('textarea');
		input.placeholder = 'Type your message...';
		input.rows = 2;
		const sendBtn = createButton('Send');

		inputRow.appendChild(input);
		inputRow.appendChild(sendBtn);
		body.appendChild(messagesEl);
		body.appendChild(inputRow);

		wrapper.appendChild(header);
		wrapper.appendChild(body);
		document.body.appendChild(wrapper);

		const historyKey = 'wp_chatgpt_history';
		let history = [];
		if (config.enableChatHistory && window.localStorage){
			try {
				history = JSON.parse(localStorage.getItem(historyKey) || '[]');
			} catch(e){}
		}

		function persist(){
			if (config.enableChatHistory && window.localStorage){
				localStorage.setItem(historyKey, JSON.stringify(history.slice(-20)));
			}
		}

		function addMessage(role, content){
			const item = createElement('div', 'wp-chatgpt-message ' + (role === 'assistant' ? 'assistant' : 'user'));
			item.textContent = content;
			messagesEl.appendChild(item);
			messagesEl.scrollTop = messagesEl.scrollHeight;
		}

		function setLoading(isLoading){
			sendBtn.disabled = !!isLoading;
			sendBtn.textContent = isLoading ? 'Sending...' : 'Send';
		}

		function send(){
			const value = input.value.trim();
			if (!value) return;
			input.value = '';
			addMessage('user', value);
			history.push({ role: 'user', content: value });
			persist();
			setLoading(true);

			fetch(config.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce || ''
				},
				body: JSON.stringify({ message: value, history })
			}).then(async (res) => {
				if (!res.ok){
					const t = await res.text();
					throw new Error(t || 'Request failed');
				}
				return res.json();
			}).then((data) => {
				const msg = (data && data.message) || '';
				if (msg){
					addMessage('assistant', msg);
					history.push({ role: 'assistant', content: msg });
					persist();
				}
			}).catch((err) => {
				addMessage('assistant', 'Error: ' + (err && err.message ? err.message : 'Unknown error'));
			}).finally(() => setLoading(false));
		}

		sendBtn.addEventListener('click', send);
		input.addEventListener('keydown', function(e){
			if ((e.key === 'Enter' || e.keyCode === 13) && (e.shiftKey === false)){
				e.preventDefault();
				send();
			}
		});

		// Restore history
		if (history.length){
			history.forEach(turn => addMessage(turn.role, turn.content));
		}
	}

	function renderEmbeds(){
		const nodes = document.querySelectorAll('.wp-chatgpt-embed');
		nodes.forEach(node => {
			if (node.__rendered) return;
			node.__rendered = true;

			const container = createElement('div', 'wp-chatgpt-embed-inner');
			const messagesEl = createElement('div', 'wp-chatgpt-messages');
			const inputRow = createElement('div', 'wp-chatgpt-input-row');
			const input = document.createElement('textarea');
			input.placeholder = 'Type your message...';
			input.rows = 2;
			const sendBtn = createButton('Send');

			inputRow.appendChild(input);
			inputRow.appendChild(sendBtn);
			container.appendChild(messagesEl);
			container.appendChild(inputRow);
			node.appendChild(container);

			let history = [];

			function addMessage(role, content){
				const item = createElement('div', 'wp-chatgpt-message ' + (role === 'assistant' ? 'assistant' : 'user'));
				item.textContent = content;
				messagesEl.appendChild(item);
				messagesEl.scrollTop = messagesEl.scrollHeight;
			}

			function setLoading(isLoading){
				sendBtn.disabled = !!isLoading;
				sendBtn.textContent = isLoading ? 'Sending...' : 'Send';
			}

			function send(){
				const value = input.value.trim();
				if (!value) return;
				input.value = '';
				addMessage('user', value);
				history.push({ role: 'user', content: value });
				setLoading(true);

				fetch(config.restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce || '' },
					body: JSON.stringify({ message: value, history })
				}).then(async (res) => {
					if (!res.ok){
						const t = await res.text();
						throw new Error(t || 'Request failed');
					}
					return res.json();
				}).then((data) => {
					const msg = (data && data.message) || '';
					if (msg){
						addMessage('assistant', msg);
						history.push({ role: 'assistant', content: msg });
					}
				}).catch((err) => {
					addMessage('assistant', 'Error: ' + (err && err.message ? err.message : 'Unknown error'));
				}).finally(() => setLoading(false));
			}

			sendBtn.addEventListener('click', send);
			input.addEventListener('keydown', function(e){
				if ((e.key === 'Enter' || e.keyCode === 13) && (e.shiftKey === false)){
					e.preventDefault();
					send();
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function(){
		if (document.querySelector('.wp-chatgpt-embed')){
			renderEmbeds();
		}
		if (config && config.restUrl){
			renderFloatingWidget();
		}
	});
})();