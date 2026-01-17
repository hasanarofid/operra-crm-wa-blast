(function() {
    // 1. Get Configuration from Script Tag
    const script = document.currentScript;
    const appKey = script.getAttribute('data-key');
    const baseUrl = 'https://crm.hasanarofid.site';

    if (!appKey) {
        console.error('WhatsApp Widget: Missing data-key attribute.');
        return;
    }

    // 2. Fetch Widget Settings from Backend
    fetch(`${baseUrl}/api/whatsapp/widget/config?key=${appKey}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('WhatsApp Widget Error:', data.error);
                return;
            }
            initWidget(data);
        })
        .catch(err => console.error('WhatsApp Widget: Failed to load config', err));

    function initWidget(config) {
        const settings = {
            primaryColor: config.settings.primary_color || '#25D366',
            position: config.settings.position || 'right',
            welcomeText: config.settings.welcome_text || 'Halo! Ada yang bisa kami bantu?',
            whatsappNumber: config.whatsapp_number
        };

        // 3. Create Widget Styles
        const style = document.createElement('style');
        style.innerHTML = `
            .wa-widget-bubble {
                position: fixed;
                bottom: 20px;
                ${settings.position}: 20px;
                width: 60px;
                height: 60px;
                background-color: ${settings.primaryColor};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                cursor: pointer;
                z-index: 99999;
                transition: transform 0.3s ease;
            }
            .wa-widget-bubble:hover {
                transform: scale(1.1);
            }
            .wa-widget-bubble svg {
                width: 35px;
                height: 35px;
                fill: white;
            }
            .wa-widget-tooltip {
                position: absolute;
                ${settings.position === 'right' ? 'right' : 'left'}: 75px;
                background: white;
                padding: 10px 15px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                white-space: nowrap;
                font-family: sans-serif;
                font-size: 14px;
                color: #333;
                pointer-events: none;
                opacity: 0;
                transform: translateX(${settings.position === 'right' ? '10px' : '-10px'});
                transition: all 0.3s ease;
            }
            .wa-widget-bubble:hover .wa-widget-tooltip {
                opacity: 1;
                transform: translateX(0);
            }
        `;
        document.head.appendChild(style);

        // 4. Create Widget Elements
        const bubble = document.createElement('div');
        bubble.className = 'wa-widget-bubble';
        bubble.innerHTML = `
            <div class="wa-widget-tooltip">${settings.welcomeText}</div>
            <svg viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217s.231.006.332.009c.109.004.258-.041.405.314.159.386.541 1.317.588 1.412.047.095.078.205.014.331-.064.127-.095.205-.189.314-.094.109-.193.243-.275.326-.091.092-.187.193-.08.377.107.183.476.784 1.021 1.27.702.625 1.294.819 1.478.911.184.092.29.078.397-.047.107-.125.462-.534.585-.717.123-.183.246-.154.415-.091s1.068.503 1.256.597c.188.094.313.141.358.22.045.078.045.45-.099.855z"/>
            </svg>
        `;

        bubble.onclick = function() {
            window.open(`https://wa.me/${settings.whatsappNumber}`, '_blank');
        };

        document.body.appendChild(bubble);
    }
})();
