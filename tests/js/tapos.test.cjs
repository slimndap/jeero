const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');
const script = fs.readFileSync(require('node:path').join(__dirname, '../../assets/js/theaters/Tapos.js'), 'utf8');

function run(cookie = '', blocked = false) {
	const listeners = [];
	const actions = [];
	const frame = { dataset: { jeeroTaposUrl: 'https://tickets.example/book?perfcode=21907' }, contentWindow: {}, scrollIntoView() { actions.push('scroll'); } };
	const document = { readyState: 'complete', querySelectorAll() { return [frame]; } };
	Object.defineProperty(document, 'cookie', { get() { return cookie; }, set(value) { if (!blocked) cookie = value.split(';')[0]; } });
	const window = { crypto: webcrypto, addEventListener(type, callback) { listeners.push(callback); }, history: { back() { actions.push('back'); } }, iFrameResize(options) { assert.deepEqual(Array.from(options.checkOrigin), ['https://tickets.example']); } };
	vm.runInNewContext(script, { window, document, location: { protocol: 'https:' }, URL, Uint8Array });
	return { frame, cookie, actions, message: listeners[0] };
}

test('sessions survive navigation but are unique between visitors', () => {
	const first = run();
	const id = new URL(first.frame.src).searchParams.get('tapos_id');
	assert.match(id, /^[a-f0-9]{32}$/);
	assert.equal(new URL(run(first.cookie).frame.src).searchParams.get('tapos_id'), id);
	assert.notEqual(new URL(run().frame.src).searchParams.get('tapos_id'), id);
});

test('only the expected frame and origin can navigate or scroll', () => {
	const r = run();
	r.message({ origin: 'https://evil.example', source: r.frame.contentWindow, data: 'iframe_go_back' });
	r.message({ origin: 'https://tickets.example', source: {}, data: 'iframe_go_back' });
	assert.deepEqual(r.actions, []);
	for (const data of ['iframe_change', 'iframe_scroll_to_top_of_page', 'iframe_go_back', 'unknown']) r.message({ origin: 'https://tickets.example', source: r.frame.contentWindow, data });
	assert.deepEqual(r.actions, ['scroll', 'scroll', 'back']);
});

test('blocked cookies leave the direct-booking fallback available', () => {
	const r = run('', true);
	assert.equal(r.frame.src, undefined);
	assert.equal(r.frame.hidden, true);
});
