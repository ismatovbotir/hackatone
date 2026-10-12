// QR code for the Telegram deep link (panel Telegram tab). qrcode-generator is loaded from the CDN in the panel layout, as in the prototype.
window.qrSvg = (text) => {
    if (typeof window.qrcode !== 'function') {
        return '';
    }
    const qr = window.qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
};
