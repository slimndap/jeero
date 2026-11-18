const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const script = fs.readFileSync(`${__dirname}/../../assets/js/admin.js`, 'utf8');

for (const state of ['success', 'denied', 'unavailable', 'hidden']) {
    test(`MCP Copy URL: ${state}`, async () => {
        let click;
        let copied;
        const status = { textContent: '' };
        const url = { value: 'https://venue.example/wordpress/wp-json/mcp/mcp-adapter-default-server' };
        const button = {
            dataset: { success: 'URL copied.', error: 'Copy manually.' },
            addEventListener(event, handler) { assert.equal(event, 'click'); click = handler; },
        };
        const emptyFields = { length: 0, find() { return this; }, filter() { return this; }, on() {} };
        vm.runInNewContext(script, {
            jQuery(arg) { return typeof arg === 'function' ? arg() : emptyFields; },
            document: { getElementById(id) {
                return { 'jeero-mcp-copy': state === 'hidden' ? null : button, 'jeero-mcp-url': url, 'jeero-mcp-copy-status': status }[id];
            } },
            navigator: state === 'unavailable' ? {} : { clipboard: { async writeText(value) {
                if (state === 'denied') throw new Error('Permission denied');
                copied = value;
            } } },
        });
        if (state === 'hidden') return assert.equal(click, undefined);
        await click();
        assert.equal(status.textContent, state === 'success' ? button.dataset.success : button.dataset.error);
        assert.equal(copied, state === 'success' ? url.value : undefined);
    });
}
