<!--chatbot widget fixed to bottom-right of the screen-->
<div class="chatbot-widget" id="chatbotWidget">
    <!--small round button that opens/closes the panel-->
    <button class="chatbot-toggle" id="chatbotToggle">?</button>

    <!--main chatbot panel (messages + input)-->
    <div class="chatbot-panel" id="chatbotPanel">
        <div class="chatbot-header">
            <h3>GameSense Assistant</h3>
            <p>Ask how to use the marketplace.</p>
        </div>

        <!--scrollable list of chat bubbles-->
        <div class="chatbot-messages" id="chatbotMessages"></div>

        <!--simple text input row for user questions-->
        <form class="chatbot-input-row" id="chatbotForm">
            <input
                type="text"
                id="chatbotInput"
                placeholder="Ask about listings, filters, cart..."
                autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>
</div>

<!--chatbot behavior (inline version)-->
<script>
//chatbot ui behavior for the floating help widget
document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('chatbotToggle');
    const panel    = document.getElementById('chatbotPanel');
    const form     = document.getElementById('chatbotForm');
    const input    = document.getElementById('chatbotInput');
    const messages = document.getElementById('chatbotMessages');

    if (!toggle || !panel || !form || !input || !messages) return;

    toggle.addEventListener('click', () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            input.focus();
        }
        toggle.classList.remove('chatbot-pop');
        void toggle.offsetWidth;
        toggle.classList.add('chatbot-pop');
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        addMessage('user', text);
        input.value = '';
        const reply = getBotReply(text);
        setTimeout(() => addMessage('bot', reply), 250);
    });

    function addMessage(from, text) {
        const div = document.createElement('div');
        div.className = `chatbot-message chatbot-message--${from}`;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function getBotReply(text) {
        const q = text.toLowerCase();
        if (q.includes('list') && (q.includes('item') || q.includes('sell'))) {
            return 'To list an item, click "LIST ITEM" in the top navigation, fill in the product details, upload a photo, and submit the form.';
        }
        if (q.includes('filter') || q.includes('tag') || q.includes('search')) {
            return 'On the Marketplace page you can use the tags on the left and the search bar on the right to narrow down products by type or keywords.';
        }
        if (q.includes('cart') || q.includes('checkout')) {
            return 'Use the cart icon in the header to review your items. On the cart page you can increase quantities, remove items, or proceed to checkout.';
        }
        if (q.includes('account') || q.includes('login') || q.includes('sign in') || q.includes('register')) {
            return 'Use the Log In and Register links in the header. Once logged in you\'ll see your dashboard and can list items.';
        }
        if (q.includes('dashboard')) {
            return 'Your dashboard shows a summary of your listings and analytics. From there you can edit or delete your products.';
        }
        return 'I can help you with listing items, using filters and search, your cart, and your account. Try asking: "How do I list an item?" or "How do filters work?"';
    }
});
</script>


<script>
//user dropdown behavior in the navbar
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('navUserTrigger');
    const menu    = document.getElementById('navUserMenu');

    //if there is no user menu (e.g. logged out), do nothing
    if (!trigger || !menu) return;

    //toggle menu open/close when clicking the username pill
    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = menu.style.display === 'block';
        menu.style.display = open ? 'none' : 'block';
    });

    //clicks anywhere else should close the menu
    document.addEventListener('click', function () {
        menu.style.display = 'none';
    });
});
</script>



</main>

<footer>
    <div class="container">
        <!--simple footer brand label-->
        <p style="text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">
            &copy; <?php echo date('Y'); ?> GameSense
        </p>
    </div>
</footer>

</body>
</html>