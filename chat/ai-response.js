const express = require('express');
const router  = express.Router();

router.post('/', async (req, res) => {
    const { message, history = [] } = req.body;
    if (!message) return res.json({ error: 'رسالة فارغة' });

    try {
        const response = await fetch('http://localhost:5678/webhook-test/ai-chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, history })
        });

        const data = await response.json();
        res.json(data);
    } catch (e) {
        res.json({ error: 'خطأ في الاتصال', details: e.message });
    }
});

module.exports = router;