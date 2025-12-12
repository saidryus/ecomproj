//chatbot ui behavior for the floating help widget
document.addEventListener('DOMContentLoaded', () => {
    //core elements we need to control
    const toggle   = document.getElementById('chatbotToggle');
    const panel    = document.getElementById('chatbotPanel');
    const form     = document.getElementById('chatbotForm');
    const input    = document.getElementById('chatbotInput');
    const messages = document.getElementById('chatbotMessages');

    //if any required element is missing, just stop silently
    if (!toggle || !panel || !form || !input || !messages) return;

    // ====== OPEN/CLOSE PANEL ======

    //clicking the bubble toggles the panel open/closed
    toggle.addEventListener('click', () => {
        panel.classList.toggle('open');

        //when opened, put focus on the input so user can type immediately
        if (panel.classList.contains('open')) {
            input.focus();
        }

        //small pop animation on the toggle button
        toggle.classList.remove('chatbot-pop');
        //force reflow so the animation can restart each click
        void toggle.offsetWidth;
        toggle.classList.add('chatbot-pop');
    });

    // ====== MESSAGE HANDLING ======

    //handle user submitting a question
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        //read and trim current input
        const text = input.value.trim();
        if (!text) return;

        //show the user's message in the chat window
        addMessage('user', text);
        input.value = '';

        //generate a simple canned reply from helper function
        const reply = getBotReply(text);

        //small delay to feel more natural
        setTimeout(() => addMessage('bot', reply), 250);
    });

    //append a message div to the chat log
    function addMessage(from, text) {
        const div = document.createElement('div');
        div.className = `chatbot-message chatbot-message--${from}`;
        div.textContent = text;
        messages.appendChild(div);

        //auto-scroll to the newest message
        messages.scrollTop = messages.scrollHeight;
    }

    // ====== SIMPLE FAQ LOGIC ======

    //very small rule-based “bot” that answers common questions
    function getBotReply(text) {
        const q = text.toLowerCase();

        //listing items for sale
        if (q.includes('list') && (q.includes('item') || q.includes('sell'))) {
            return 'To list an item, click "LIST ITEM" in the top navigation, fill in the product details, upload a photo, and submit the form.';
        }

        //filters, tags, search bar
        if (q.includes('filter') || q.includes('tag') || q.includes('search')) {
            return 'On the Marketplace page you can use the tags on the left and the search bar on the right to narrow down products by type or keywords.';
        }

        //cart or checkout questions
        if (q.includes('cart') || q.includes('checkout')) {
            return 'Use the cart icon in the header to review your items. On the cart page you can increase quantities, remove items, or proceed to checkout.';
        }

        //account/login/register help
        if (q.includes('account') || q.includes('login') || q.includes('sign in') || q.includes('register')) {
            return 'Use the Log In and Register links in the header. Once logged in you’ll see your dashboard and can list items.';
        }

        //dashboard overview
        if (q.includes('dashboard')) {
            return 'Your dashboard shows a summary of your listings and analytics. From there you can edit or delete your products.';
        }

        //fallback help text when no rule matches
        return 'I can help you with listing items, using filters and search, your cart, and your account. Try asking: "How do I list an item?" or "How do filters work?"';
    }
});
