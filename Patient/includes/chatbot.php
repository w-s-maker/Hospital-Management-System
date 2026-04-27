<!-- Chatbot -->
<div class="chatbot-container" id="chatbot-container">
    <div class="chatbot-header">
        <h3>Afya Hospital Assistant</h3>
        <button id="chatbot-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="chatbot-messages" id="chatbot-messages">
        <div class="message bot-message">
            <div class="message-content">
                Hello! I'm your Afya Hospital virtual assistant. How can I help you today?
            </div>
            <div class="message-time">Just now</div>
        </div>
    </div>
    <div class="chatbot-input">
        <input type="text" id="chatbot-input-field" placeholder="Type your message...">
        <button id="chatbot-send"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<div class="chatbot-button" id="chatbot-button">
    <i class="fas fa-comment-dots"></i>
</div>

<style>
    /* Chatbot Styles */
    .chatbot-container {
        position: fixed;
        bottom: 5rem;
        right: 2rem;
        width: 350px;
        height: 450px;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1000;
        display: none;
    }

    .chatbot-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background-color: #0087ff;
        color: #ffffff;
    }

    .chatbot-header h3 {
        margin: 0;
        font-size: 1rem;
    }

    .chatbot-header button {
        background: none;
        border: none;
        color: #ffffff;
        cursor: pointer;
        font-size: 1rem;
    }

    .chatbot-messages {
        flex: 1;
        padding: 1rem;
        overflow-y: auto;
    }

    .message {
        margin-bottom: 1rem;
        max-width: 80%;
    }

    .user-message {
        margin-left: auto;
    }

    .bot-message {
        margin-right: auto;
    }

    .message-content {
        padding: 0.75rem;
        border-radius: 0.5rem;
    }

    .user-message .message-content {
        background-color: #0087ff;
        color: #ffffff;
        border-top-right-radius: 0;
    }

    .bot-message .message-content {
        background-color: #e5e7eb;
        color: #333333;
        border-top-left-radius: 0;
    }

    .message-time {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
        text-align: right;
    }

    .chatbot-input {
        display: flex;
        padding: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }

    .chatbot-input input {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        margin-right: 0.5rem;
    }

    .chatbot-input button {
        background-color: #0087ff;
        color: #ffffff;
        border: none;
        border-radius: 0.375rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
    }

    .chatbot-button {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 3.5rem;
        height: 3.5rem;
        background-color: #0087ff;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 999;
        font-size: 1.5rem;
    }

    @media (max-width: 768px) {
        .chatbot-container {
            width: 300px;
            height: 400px;
            bottom: 4.5rem;
            right: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chatbot functionality
    const chatbotButton = document.getElementById('chatbot-button');
    const chatbotContainer = document.getElementById('chatbot-container');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotMessages = document.getElementById('chatbot-messages');
    const chatbotInput = document.getElementById('chatbot-input-field');
    const chatbotSend = document.getElementById('chatbot-send');
    
    chatbotButton.addEventListener('click', function() {
        chatbotContainer.style.display = 'flex';
        chatbotButton.style.display = 'none';
    });
    
    chatbotClose.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
        chatbotButton.style.display = 'flex';
    });
    
    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (message === '') return;
        
        // Add user message
        addMessage(message, 'user');
        chatbotInput.value = '';
        
        // Process the message and get a response
        const response = getResponse(message);
        
        // Simulate bot response with a slight delay
        setTimeout(function() {
            addMessage(response, 'bot');
        }, 800);
    }
    
    function addMessage(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = message;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = 'Just now';
        
        messageDiv.appendChild(contentDiv);
        messageDiv.appendChild(timeDiv);
        
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    
    function getResponse(message) {
        // Convert message to lowercase for easier matching
        const lowerMessage = message.toLowerCase();
        
        // Common greetings
        if (lowerMessage.includes('hello') || lowerMessage.includes('hi') || lowerMessage.includes('hey')) {
            return "Hello! How can I assist you with Afya Hospital services today?";
        }
        
        // Appointment related
        if (lowerMessage.includes('appointment') || lowerMessage.includes('book') || lowerMessage.includes('schedule')) {
            return "You can book an appointment by visiting our 'Book Appointment' page. Would you like me to direct you there?";
        }
        
        // Doctor related
        if (lowerMessage.includes('doctor') || lowerMessage.includes('specialist') || lowerMessage.includes('physician')) {
            return "Afya Hospital has specialists in various fields including Cardiology, Neurology, Pediatrics, Orthopedics, and General Medicine. Is there a specific department you're interested in?";
        }
        
        // Services related
        if (lowerMessage.includes('service') || lowerMessage.includes('treatment') || lowerMessage.includes('procedure')) {
            return "We offer a wide range of services including consultations, diagnostics, surgeries, and preventive care. What specific service are you looking for?";
        }
        
        // Location related
        if (lowerMessage.includes('location') || lowerMessage.includes('address') || lowerMessage.includes('where')) {
            return "Afya Hospital is located at 123 Hospital Road, Nairobi, Kenya. We're open 24/7 for emergencies and from 8 AM to 8 PM for regular appointments.";
        }
        
        // Contact related
        if (lowerMessage.includes('contact') || lowerMessage.includes('phone') || lowerMessage.includes('call') || lowerMessage.includes('email')) {
            return "You can reach us at +254 712 345 678 or email us at info@afyahospital.com. Our customer service team is available 24/7.";
        }
        
        // Insurance related
        if (lowerMessage.includes('insurance') || lowerMessage.includes('cover') || lowerMessage.includes('payment')) {
            return "We accept most major insurance providers. For specific inquiries about your insurance coverage, please contact our billing department at billing@afyahospital.com or call +254 712 345 679.";
        }
        
        // COVID-19 related
        if (lowerMessage.includes('covid') || lowerMessage.includes('coronavirus') || lowerMessage.includes('pandemic') || lowerMessage.includes('vaccine')) {
            return "We offer COVID-19 testing and vaccination services. Please visit our COVID-19 page for more information on protocols, testing hours, and vaccination appointments.";
        }
        
        // Emergency related
        if (lowerMessage.includes('emergency') || lowerMessage.includes('urgent') || lowerMessage.includes('critical')) {
            return "For medical emergencies, please call our emergency hotline at 911 or proceed directly to our Emergency Department which is open 24/7. If you need immediate assistance, please call rather than using this chat.";
        }
        
        // Billing related
        if (lowerMessage.includes('bill') || lowerMessage.includes('invoice') || lowerMessage.includes('pay')) {
            return "You can view and pay your bills through our Billing page. We accept various payment methods including cash, credit/debit cards, M-Pesa, and bank transfers.";
        }
        
        // Medical records related
        if (lowerMessage.includes('record') || lowerMessage.includes('history') || lowerMessage.includes('report')) {
            return "You can access your medical records through our Medical History page. You'll need to provide your email or phone number for verification.";
        }
        
        // Feedback related
        if (lowerMessage.includes('feedback') || lowerMessage.includes('complaint') || lowerMessage.includes('suggestion')) {
            return "We value your feedback! You can share your experience, suggestions, or concerns through our Feedback page.";
        }
        
        // Thank you responses
        if (lowerMessage.includes('thank') || lowerMessage.includes('thanks') || lowerMessage.includes('appreciate')) {
            return "You're welcome! Is there anything else I can help you with?";
        }
        
        // Goodbye responses
        if (lowerMessage.includes('bye') || lowerMessage.includes('goodbye') || lowerMessage.includes('see you')) {
            return "Thank you for chatting with Afya Hospital Assistant. Have a great day!";
        }
        
        // Default response for unrecognized queries
        return "Thank you for your message. If you have specific questions about our services, appointments, or billing, please let me know. You can also call us at 1-800-AFYA for immediate assistance.";
    }
    
    chatbotSend.addEventListener('click', sendMessage);
    
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>